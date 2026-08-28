<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Central evaluation of the separated website states required by the platform:
 *
 *   configured          — the tenant has a website settings record (content exists)
 *   plan-entitled       — the active subscription plan includes `public_website`
 *   enabledByAdmin      — Super Admin has not switched the website off (kill switch)
 *   portalUsable        — business status + subscription allow the tenant to act
 *   published           — the business owner has explicitly published the site
 *   directoryVisible    — published and opted into the public directory
 *   indexingEligible    — published, approved and the owner allows search engines
 *
 * These flags are deliberately independent. A default configuration must never
 * imply public access: the public site requires plan entitlement AND admin
 * enable AND explicit publish AND an approved, paying tenant.
 */
class WebsiteAccessService
{
    public static function defaultSettings(?array $row): array
    {
        $defaults = [
            'website_enabled' => 1,
            'website_published' => 0,
            'website_published_at' => null,
            'allow_indexing' => 1,
            'show_in_directory' => 1,
            'allow_public_enquiries' => 1,
            'allow_public_orders' => 1,
            'theme_preset' => 'modern',
            'layout_style' => 'classic',
            'background_style' => 'light',
            'button_style' => 'rounded',
            'text_style' => 'system',
            'primary_color' => '#2563eb',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f97316',
            'section_visibility' => null,
            'seo_title' => null,
            'seo_description' => null,
            '_settings_row' => false,
        ];
        if (!$row) {
            return $defaults;
        }
        $row['_settings_row'] = true;
        if (empty($row['primary_color']) && !empty($row['theme_color'])) {
            $row['primary_color'] = $row['theme_color'];
        }
        return array_merge($defaults, $row);
    }

    public static function settingsFor(int $businessId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM business_settings WHERE business_id = ? LIMIT 1');
        $stmt->execute([$businessId]);
        return self::defaultSettings($stmt->fetch(PDO::FETCH_ASSOC) ?: null);
    }

    public static function evaluate(array $business, ?array $subscription, ?array $settings = null, ?array $featureAccess = null): array
    {
        $businessId = (int) $business['id'];
        if ($settings === null) {
            $settings = self::settingsFor($businessId);
        } else {
            // Callers pass the RAW business_settings row (or null). Empty array
            // and rows without content are normalized through the same defaults.
            $settings = self::defaultSettings($settings === [] ? null : $settings);
        }
        if ($featureAccess === null) {
            $featureAccess = FeatureService::featuresForBusiness($businessId, $business, $subscription);
        }

        $configured = !empty($settings['_settings_row']);
        $portalUsable = SubscriptionService::canUsePortal($business, $subscription);
        $planEntitled = isset($featureAccess['public_website']);
        $enabledByAdmin = (int) ($settings['website_enabled'] ?? 1) === 1;
        $businessApproved = in_array((string) ($business['status'] ?? ''), ['approved', 'active'], true);
        $published = $configured && (int) ($settings['website_published'] ?? 0) === 1;

        $publicAccess = $configured && $planEntitled && $enabledByAdmin && $businessApproved && $portalUsable && $published;

        return [
            'configured' => $configured,
            'plan_entitled' => $planEntitled,
            'enabled_by_admin' => $enabledByAdmin,
            'business_approved' => $businessApproved,
            'portal_usable' => $portalUsable,
            'published' => $published,
            'public_access' => $publicAccess,
            'directory_visible' => $publicAccess && (int) ($settings['show_in_directory'] ?? 1) === 1,
            'indexing_eligible' => $publicAccess
                && (int) ($settings['allow_indexing'] ?? 1) === 1
                && (int) ($settings['show_in_directory'] ?? 1) === 1,
            'preview_available' => $planEntitled && $portalUsable,
            'settings' => $settings,
        ];
    }

    /**
     * SQL conditions selecting directory-eligible tenants (aliases: b = businesses,
     * w = business_settings). Used by the public directory and customer dashboard.
     */
    public static function directoryWhere(): string
    {
        return 'b.status IN ("active", "approved")
               AND COALESCE(w.website_enabled, 0) = 1
               AND COALESCE(w.website_published, 0) = 1
               AND COALESCE(w.show_in_directory, 0) = 1
               AND EXISTS (
                   SELECT 1 FROM business_subscriptions bs
                   INNER JOIN subscription_plan_features spf ON spf.plan_id = bs.plan_id AND spf.enabled = 1
                   INNER JOIN platform_features pf ON pf.id = spf.feature_id AND pf.identifier = "public_website" AND pf.is_active = 1 AND pf.available_for_plans = 1
                   WHERE bs.business_id = b.id
                     AND bs.status IN ("active", "trialing")
                     AND (bs.expires_at >= CURDATE() OR (bs.grace_ends_at IS NOT NULL AND bs.grace_ends_at >= CURDATE()))
               )';
    }

    /** Stricter variant for search engines: published + directory + indexing allowed. */
    public static function sitemapWhere(): string
    {
        return self::directoryWhere() . ' AND COALESCE(w.allow_indexing, 0) = 1';
    }
}
