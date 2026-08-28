<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class FeatureService
{
    private static array $businessFeatureCache = [];
    private static ?array $registryCache = null;

    public static function registry(bool $plansOnly = false, bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM platform_features WHERE 1=1';
        $params = [];

        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        if ($plansOnly) {
            $sql .= ' AND available_for_plans = 1';
        }

        $sql .= ' ORDER BY category ASC, sort_order ASC, name ASC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function groupedRegistry(bool $plansOnly = false, bool $activeOnly = true): array
    {
        $grouped = [];
        foreach (self::registry($plansOnly, $activeOnly) as $feature) {
            $grouped[$feature['category']][] = $feature;
        }
        return $grouped;
    }

    public static function registryByIdentifier(): array
    {
        if (self::$registryCache !== null) {
            return self::$registryCache;
        }

        self::$registryCache = [];
        foreach (self::registry(false, false) as $feature) {
            self::$registryCache[$feature['identifier']] = $feature;
        }
        return self::$registryCache;
    }

    public static function featuresForPlan(int $planId): array
    {
        if ($planId <= 0) {
            return [];
        }

        $stmt = Database::pdo()->prepare(
            'SELECT f.*, spf.enabled, spf.limits_json
             FROM subscription_plan_features spf
             INNER JOIN platform_features f ON f.id = spf.feature_id
             WHERE spf.plan_id = ?
             ORDER BY f.category ASC, f.sort_order ASC, f.name ASC'
        );
        $stmt->execute([$planId]);

        $features = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $feature) {
            $feature['limits'] = self::decodeLimits($feature['limits_json'] ?? null);
            $features[$feature['identifier']] = $feature;
        }
        return $features;
    }

    public static function groupedFeaturesForPlan(int $planId): array
    {
        $grouped = [];
        foreach (self::featuresForPlan($planId) as $feature) {
            $grouped[$feature['category']][] = $feature;
        }
        return $grouped;
    }

    public static function syncPlanFeatures(int $planId, array $featureIds, array $limitsByFeatureId = []): void
    {
        $pdo = Database::pdo();
        $delete = $pdo->prepare('DELETE FROM subscription_plan_features WHERE plan_id = ?');
        $delete->execute([$planId]);

        if (empty($featureIds)) {
            self::clearCache();
            return;
        }

        $featureIds = array_values(array_unique(array_map('intval', $featureIds)));
        $allowedFeatureIds = array_map(fn($feature) => (int) $feature['id'], self::registry(true, true));
        $featureIds = array_values(array_intersect($featureIds, $allowedFeatureIds));
        $insert = $pdo->prepare(
            'INSERT INTO subscription_plan_features (plan_id, feature_id, enabled, limits_json, created_at, updated_at)
             VALUES (?, ?, 1, ?, NOW(), NOW())'
        );

        foreach ($featureIds as $featureId) {
            if ($featureId <= 0) {
                continue;
            }
            $limitsJson = self::normalizeLimitsJson($limitsByFeatureId[$featureId] ?? '');
            $insert->execute([$planId, $featureId, $limitsJson]);
        }

        self::clearCache();
    }

    public static function featuresForBusiness(int $businessId, array $business, ?array $subscription): array
    {
        $cacheKey = $businessId . ':' . (int) ($subscription['plan_id'] ?? 0) . ':' . ($subscription['id'] ?? 0) . ':' . ($subscription['expires_at'] ?? '');
        if (isset(self::$businessFeatureCache[$cacheKey])) {
            return self::$businessFeatureCache[$cacheKey];
        }

        if (!SubscriptionService::canUsePortal($business, $subscription) || empty($subscription['plan_id'])) {
            self::$businessFeatureCache[$cacheKey] = [];
            return [];
        }

        $stmt = Database::pdo()->prepare(
            'SELECT f.*, spf.limits_json
             FROM subscription_plan_features spf
             INNER JOIN platform_features f ON f.id = spf.feature_id
             WHERE spf.plan_id = ?
               AND spf.enabled = 1
               AND f.is_active = 1
               AND f.available_for_plans = 1
             ORDER BY f.category ASC, f.sort_order ASC, f.name ASC'
        );
        $stmt->execute([(int) $subscription['plan_id']]);

        $features = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $feature) {
            $feature['limits'] = self::decodeLimits($feature['limits_json'] ?? null);
            $features[$feature['identifier']] = $feature;
        }

        self::$businessFeatureCache[$cacheKey] = $features;
        return $features;
    }

    public static function hasFeature(int $businessId, array $business, ?array $subscription, string $identifier): bool
    {
        return isset(self::featuresForBusiness($businessId, $business, $subscription)[$identifier]);
    }

    public static function hasAnyFeature(int $businessId, array $business, ?array $subscription, array $identifiers): bool
    {
        $features = self::featuresForBusiness($businessId, $business, $subscription);
        foreach ($identifiers as $identifier) {
            if (isset($features[$identifier])) {
                return true;
            }
        }
        return false;
    }

    public static function limit(int $businessId, array $business, ?array $subscription, string $identifier, string $key, $default = null)
    {
        $feature = self::featuresForBusiness($businessId, $business, $subscription)[$identifier] ?? null;
        if (!$feature) {
            return $default;
        }
        $limits = $feature['limits'] ?? [];
        return array_key_exists($key, $limits) ? $limits[$key] : $default;
    }

    public static function featureLabel(string $identifier): string
    {
        $registry = self::registryByIdentifier();
        return $registry[$identifier]['name'] ?? ucwords(str_replace('_', ' ', $identifier));
    }

    public static function decodeLimits(?string $json): array
    {
        if (!$json) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function normalizeLimitsJson($raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        // Convenience: a plain number means a generic max_items limit.
        if (is_numeric($raw)) {
            return json_encode(['max_items' => (int) $raw], JSON_UNESCAPED_SLASHES);
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return null;
        }

        return json_encode($decoded, JSON_UNESCAPED_SLASHES);
    }

    public static function clearCache(): void
    {
        self::$businessFeatureCache = [];
        self::$registryCache = null;
    }
}
