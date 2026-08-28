<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class Auth
{
    private static $cachedUser = false;

    public static function user(): ?array
    {
        if (self::$cachedUser !== false) {
            return self::$cachedUser;
        }

        if (empty($_SESSION['user_id'])) {
            self::$cachedUser = null;
            return null;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT u.*, b.name AS business_name, b.slug AS business_slug, b.status AS business_status
             FROM users u
             LEFT JOIN businesses b ON b.id = u.business_id
             WHERE u.id = ?
             LIMIT 1'
        );
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$user || $user['status'] !== 'active') {
            self::logout(false);
            self::$cachedUser = null;
            return null;
        }

        self::$cachedUser = $user;
        return $user;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function businessId(): ?int
    {
        $user = self::user();
        return $user && $user['business_id'] ? (int) $user['business_id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === 'super_admin';
    }

    public static function isBusinessUser(): bool
    {
        return in_array(self::role(), ['business_owner', 'business_staff'], true);
    }

    public static function attempt(string $email, string $password, ?string &$error = null): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.*, b.status AS business_status
             FROM users u
             LEFT JOIN businesses b ON b.id = u.business_id
             WHERE u.email = ?
             LIMIT 1'
        );
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
            return false;
        }

        if ($user['status'] !== 'active') {
            $error = 'This user account is not active.';
            return false;
        }

        if (in_array($user['role'], ['business_owner', 'business_staff'], true)) {
            if (!$user['business_id'] || in_array($user['business_status'], ['rejected', 'archived'], true)) {
                $error = 'This business account is not available.';
                return false;
            }
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['business_id'] = $user['business_id'] ? (int) $user['business_id'] : null;
        self::$cachedUser = false;

        $update = Database::pdo()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $update->execute([(int) $user['id']]);

        return true;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Flash::warning('Please log in to continue.');
            redirect('/login');
        }
    }

    public static function requireSuperAdmin(): void
    {
        self::requireLogin();
        if (!self::isSuperAdmin()) {
            throw new HttpException(403);
        }
    }

    public static function requireBusinessUser(): void
    {
        self::requireLogin();
        if (!self::isBusinessUser()) {
            throw new HttpException(403);
        }
    }

    public static function redirectPath(): string
    {
        if (self::isSuperAdmin()) {
            return '/admin';
        }
        if (self::isBusinessUser()) {
            return '/business';
        }
        return '/login';
    }

    public static function logout(bool $regenerate = true): void
    {
        self::$cachedUser = false;
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['business_id']);
        if ($regenerate && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
