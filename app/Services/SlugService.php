<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class SlugService
{
    public static function unique(string $table, string $title, ?int $businessId = null, ?int $ignoreId = null): string
    {
        $base = slugify($title);
        $slug = $base;
        $counter = 2;

        while (self::exists($table, $slug, $businessId, $ignoreId)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private static function exists(string $table, string $slug, ?int $businessId, ?int $ignoreId): bool
    {
        $allowedTables = ['businesses', 'categories', 'listings'];
        if (!in_array($table, $allowedTables, true)) {
            throw new \InvalidArgumentException('Unsupported slug table.');
        }

        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$slug];

        if ($businessId !== null) {
            $sql .= ' AND business_id = ?';
            $params[] = $businessId;
        }

        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        $sql .= ' LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }
}
