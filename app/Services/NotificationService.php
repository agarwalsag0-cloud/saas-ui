<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class NotificationService
{
    public static function create(
        string $audience,
        string $type,
        string $title,
        string $body,
        ?int $businessId = null,
        ?int $userId = null,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?int $customerAccountId = null
    ): void {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO notifications
             (business_id, user_id, customer_account_id, audience, type, title, body, related_type, related_id, is_read, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())'
        );
        $stmt->execute([$businessId, $userId, $customerAccountId, $audience, $type, $title, $body, $relatedType, $relatedId]);
    }

    public static function latestForCustomer(int $customerAccountId, int $limit = 20): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM notifications
             WHERE audience = "customer" AND customer_account_id = ?
             ORDER BY created_at DESC LIMIT ' . (int) $limit
        );
        $stmt->execute([$customerAccountId]);
        return $stmt->fetchAll();
    }

    public static function unreadCountForCustomer(int $customerAccountId): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE audience = "customer" AND customer_account_id = ? AND is_read = 0'
        );
        $stmt->execute([$customerAccountId]);
        return (int) $stmt->fetchColumn();
    }

    public static function markCustomerRead(int $customerAccountId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE notifications SET is_read = 1, read_at = NOW()
             WHERE audience = "customer" AND customer_account_id = ? AND is_read = 0'
        );
        $stmt->execute([$customerAccountId]);
    }

    public static function unreadCount(string $audience, ?int $businessId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM notifications WHERE audience = ? AND is_read = 0';
        $params = [$audience];

        if ($businessId !== null) {
            $sql .= ' AND business_id = ?';
            $params[] = $businessId;
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function latest(string $audience, ?int $businessId = null, int $limit = 8): array
    {
        $sql = 'SELECT * FROM notifications WHERE audience = ?';
        $params = [$audience];

        if ($businessId !== null) {
            $sql .= ' AND business_id = ?';
            $params[] = $businessId;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ' . (int) $limit;
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function markAllRead(string $audience, ?int $businessId = null): void
    {
        $sql = 'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE audience = ? AND is_read = 0';
        $params = [$audience];

        if ($businessId !== null) {
            $sql .= ' AND business_id = ?';
            $params[] = $businessId;
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
    }
}
