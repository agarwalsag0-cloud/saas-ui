<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use Throwable;

class ActivityLogger
{
    public static function log(string $action, ?string $subjectType = null, ?int $subjectId = null, ?int $businessId = null, array $properties = []): void
    {
        try {
            $user = Auth::user();
            $stmt = Database::pdo()->prepare(
                'INSERT INTO activity_logs
                 (actor_user_id, actor_role, business_id, action, subject_type, subject_id, properties, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $user['id'] ?? null,
                $user['role'] ?? 'system',
                $businessId,
                $action,
                $subjectType,
                $subjectId,
                !empty($properties) ? json_encode($properties, JSON_UNESCAPED_SLASHES) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (Throwable $exception) {
            app_log('Activity log write failed', ['message' => $exception->getMessage()]);
        }
    }
}
