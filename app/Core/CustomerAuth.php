<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Authentication for platform-level customer accounts (buyers/visitors with
 * saved history). Customers are intentionally separate from business `users`:
 * they never approve anything, never access tenant data, and need no
 * Super Admin approval to register.
 */
class CustomerAuth
{
    private static $cachedCustomer = false;

    public static function customer(): ?array
    {
        if (self::$cachedCustomer !== false) {
            return self::$cachedCustomer;
        }

        if (empty($_SESSION['customer_account_id'])) {
            self::$cachedCustomer = null;
            return null;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT * FROM customer_accounts WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int) $_SESSION['customer_account_id']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$customer || $customer['status'] !== 'active') {
            self::logout(false);
            self::$cachedCustomer = null;
            return null;
        }

        self::$cachedCustomer = $customer;
        return $customer;
    }

    public static function id(): ?int
    {
        $customer = self::customer();
        return $customer ? (int) $customer['id'] : null;
    }

    public static function check(): bool
    {
        return self::customer() !== null;
    }

    public static function clearCache(): void
    {
        self::$cachedCustomer = false;
    }

    public static function attempt(int $accountId): void
    {
        if (PHP_SAPI !== 'cli') {
            session_regenerate_id(true);
        }
        $_SESSION['customer_account_id'] = $accountId;
        unset($_SESSION['customer_oauth_state']);
        self::$cachedCustomer = false;

        $update = Database::pdo()->prepare('UPDATE customer_accounts SET last_login_at = NOW() WHERE id = ?');
        $update->execute([$accountId]);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Flash::warning('Please log in to your customer account to continue.');
            redirect('/customer/login');
        }
    }

    public static function logout(bool $regenerate = true): void
    {
        self::$cachedCustomer = false;
        unset($_SESSION['customer_account_id']);
        if ($regenerate && PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
