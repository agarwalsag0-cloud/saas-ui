<?php

declare(strict_types=1);

namespace Testing\Sqlite;

use PDO;

/**
 * SQLite compatibility PDO for the test harness ONLY.
 *
 * Rewrites the handful of MySQL-specific SQL constructs the app uses
 * (NOW(), CURDATE(), DATE_ADD/DATE_SUB INTERVAL, YEAR()/MONTH(),
 * GROUP_CONCAT SEPARATOR, a targeted ON DUPLICATE KEY UPDATE mapping)
 * into SQLite equivalents so real application code paths can execute
 * without a MySQL server. Production never loads this class: the driver
 * is only selected via DB_DRIVER=sqlite (see testing/README.md).
 */
class SqliteCompatPDO extends PDO
{
    /** Tables whose INSERT .. ON DUPLICATE KEY UPDATE can be rewritten to upsert. */
    private const UPSERT_TARGETS = [
        'business_settings' => 'business_id',
        'user_preferences' => 'user_id',
    ];

    public static function open(string $path): self
    {
        $pdo = new self('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = OFF');
        return $pdo;
    }

    public function prepare($statement, $options = []): \PDOStatement|false
    {
        return parent::prepare($this->rewrite((string) $statement), is_array($options) && $options ? $options : []);
    }

    public function query(...$args): \PDOStatement|false
    {
        if (isset($args[0]) && is_string($args[0])) {
            $args[0] = $this->rewrite($args[0]);
        }
        return parent::query(...$args);
    }

    public function exec($statement): int|false
    {
        return parent::exec($this->rewrite((string) $statement));
    }

    public function rewrite(string $sql): string
    {
        // GROUP_CONCAT(expr SEPARATOR ',') -> GROUP_CONCAT(expr, ',')
        $sql = preg_replace_callback('/GROUP_CONCAT\(\s*(.+?)\s+SEPARATOR\s+(\'[^\']*\')\s*\)/i', static fn ($m) => 'GROUP_CONCAT(' . $m[1] . ', ' . $m[2] . ')', $sql) ?? $sql;

        // DATE_ADD / DATE_SUB with simple or column arguments
        $sql = preg_replace_callback(
            '/\bDATE_(SUB|ADD)\(\s*((?:CURDATE\(\)|NOW\(\)|[A-Za-z_][\w.]*(?:\(\))?)?)\s*,\s*INTERVAL\s+([\w?\'-]+)\s+(SECOND|MINUTE|HOUR|DAY|WEEK|MONTH|YEAR)S?\s*\)/i',
            static function ($m) {
                $unit = strtolower($m[4]) . 's';
                $arg = trim($m[2]) !== '' ? self::timeExpr($m[2]) : "CURRENT_TIMESTAMP";
                $n = $m[3];
                $sign = strtoupper($m[1]) === 'SUB' ? '-' : '+';
                $offset = "'" . $sign . "' || CAST((" . (is_numeric($n) ? (int) $n : $n) . ") AS TEXT) || ' " . $unit . "'";
                return 'datetime(' . $arg . ', ' . $offset . ')';
            },
            $sql
        ) ?? $sql;

        // DATE_FORMAT(expr, "%b %Y") -> strftime with normalized specifiers
        $sql = preg_replace_callback('/\bDATE_FORMAT\(\s*([^,]+?)\s*,\s*([\'\"`][^\'\"`]*[\'\"`])\s*\)/i', static function ($m) {
            $fmt = substr($m[2], 1, -1);
            $map = ['%b' => '%m', '%M' => '%m', '%e' => '%d', '%c' => '%m', '%p' => '', '%h' => '%m'];
            foreach ($map as $my => $lite) {
                $fmt = str_replace($my, $lite, $fmt);
            }
            return 'strftime(' . $m[2][0] . $fmt . $m[2][0] . ', ' . $m[1] . ')';
        }, $sql) ?? $sql;

        $sql = str_ireplace(['NOW(6)', 'NOW()'], 'CURRENT_TIMESTAMP', $sql);
        $sql = preg_replace('/\bCURDATE\(\)/i', "date('now')", $sql) ?? $sql;
        $sql = preg_replace_callback('/\bYEAR\(\s*([^()]+(?:\([^()]*\))?[^()]*)\s*\)/i', static fn ($m) => "CAST(strftime('%Y', " . $m[1] . ") AS INTEGER)", $sql) ?? $sql;
        $sql = preg_replace_callback('/\bMONTH\(\s*([^()]+(?:\([^()]*\))?[^()]*)\s*\)/i', static fn ($m) => "CAST(strftime('%m', " . $m[1] . ") AS INTEGER)", $sql) ?? $sql;
        $sql = preg_replace_callback('/\bDAYOFWEEK\(\s*([^()]+?)\s*\)/i', static fn ($m) => "CAST(strftime('%w', " . $m[1] . ") AS INTEGER) + 1", $sql) ?? $sql;

        $sql = $this->rewriteUpsert($sql);

        // Misc small talk
        $sql = str_ireplace('SQL_CALC_FOUND_ROWS', '', $sql);
        $sql = preg_replace('/\bGREATEST\(/i', 'MAX(', $sql) ?? $sql;
        $sql = preg_replace('/\bLEAST\(/i', 'MIN(', $sql) ?? $sql;

        return $sql;
    }

    private static function timeExpr(string $expr): string
    {
        $expr = trim($expr);
        if (stripos($expr, 'CURDATE') === 0) {
            return "date('now')";
        }
        if (stripos($expr, 'NOW') === 0) {
            return 'CURRENT_TIMESTAMP';
        }
        return $expr;
    }

    private function rewriteUpsert(string $sql): string
    {
        if (!preg_match('/\bINSERT\s+(?:OR\s+\w+\s+)?INTO\s+`?(\w+)`?/i', $sql, $m)) {
            return $sql;
        }
        $table = strtolower($m[1]);
        if (!isset(self::UPSERT_TARGETS[$table]) || stripos($sql, 'ON DUPLICATE KEY UPDATE') === false) {
            return $sql;
        }
        $conflict = self::UPSERT_TARGETS[$table];
        $sql = preg_replace('/\bON\s+DUPLICATE\s+KEY\s+UPDATE\b/i', 'ON CONFLICT(' . $conflict . ') DO UPDATE SET', $sql) ?? $sql;
        $sql = preg_replace('/\bVALUES\(\s*([A-Za-z_]\w*)\s*\)/i', 'excluded.$1', $sql) ?? $sql;
        return $sql;
    }
}
