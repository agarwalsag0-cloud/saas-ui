<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateInterval;
use DateTimeImmutable;
use PDO;

class SubscriptionService
{
    public static function current(int $businessId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT bs.*, sp.name AS plan_name, sp.billing_cycle, sp.features, sp.price AS plan_price, sp.currency
             FROM business_subscriptions bs
             LEFT JOIN subscription_plans sp ON sp.id = bs.plan_id
             WHERE bs.business_id = ?
             ORDER BY bs.expires_at DESC, bs.id DESC
             LIMIT 1'
        );
        $stmt->execute([$businessId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($subscription) {
            $subscription['effective_status'] = self::effectiveStatus(null, $subscription);
        }

        return $subscription;
    }

    public static function effectiveStatus(?array $business, ?array $subscription): string
    {
        if ($business && in_array($business['status'], ['suspended', 'inactive', 'pending', 'rejected', 'archived'], true)) {
            return $business['status'];
        }

        if (!$subscription) {
            return 'no_subscription';
        }

        if (in_array($subscription['status'], ['suspended', 'cancelled', 'expired'], true)) {
            return $subscription['status'];
        }

        if (($subscription['status'] ?? '') === 'pending') {
            return 'pending';
        }

        $now = new DateTimeImmutable('now');
        $expires = !empty($subscription['expires_at']) ? new DateTimeImmutable((string) $subscription['expires_at']) : null;
        $grace = !empty($subscription['grace_ends_at']) ? new DateTimeImmutable((string) $subscription['grace_ends_at']) : null;

        if (!$expires) {
            return $subscription['status'] ?? 'pending';
        }

        if ($now <= $expires) {
            $warningAt = $now->add(new DateInterval('P7D'));
            return $expires <= $warningAt ? 'expiring' : 'active';
        }

        if ($grace && $now <= $grace) {
            return 'grace_period';
        }

        return 'expired';
    }

    public static function canUsePortal(array $business, ?array $subscription): bool
    {
        if (!in_array($business['status'], ['active', 'approved'], true)) {
            return false;
        }

        $status = self::effectiveStatus($business, $subscription);
        return in_array($status, ['active', 'expiring', 'grace_period', 'trialing'], true);
    }

    /**
     * Content-management gate (Business Portal writes + plan features).
     *
     * Deliberately separate from canUsePortal / approval / publication:
     * a registered business may prepare its profile, listings and website
     * while it is still pending or under review — registration is not
     * approval, but approval is required before anything goes public.
     * Suspended / deactivated / rejected / archived tenants cannot write.
     */
    public static function canManageContent(array $business, ?array $subscription): bool
    {
        if (in_array((string) ($business['status'] ?? ''), ['suspended', 'inactive', 'rejected', 'archived'], true)) {
            return false;
        }

        // Subscription health only — not short-circuited by business status.
        $status = self::effectiveStatus(null, $subscription);
        return in_array($status, ['active', 'expiring', 'grace_period', 'trialing'], true);
    }

    public static function expiringSoon(int $days = 7): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT bs.*, b.name AS business_name, b.email AS business_email
             FROM business_subscriptions bs
             INNER JOIN businesses b ON b.id = bs.business_id
             WHERE bs.status = "active"
               AND bs.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
             ORDER BY bs.expires_at ASC'
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}
