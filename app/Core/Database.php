<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Development/testing compatibility driver (see testing/sqlite/README.md).
        // Production and XAMPP deployments use the default MySQL driver.
        if (strtolower((string) Config::get('DB_DRIVER', 'mysql')) === 'sqlite') {
            $path = (string) Config::get('DB_SQLITE_PATH', APP_PATH . '/../storage/testing.sqlite');
            $compat = dirname(__DIR__, 2) . '/testing/sqlite/compat.php';
            if (!is_file($compat)) {
                throw new RuntimeException('DB_DRIVER=sqlite requires the testing/sqlite compatibility layer.');
            }
            require_once $compat;
            self::$pdo = \Testing\Sqlite\SqliteCompatPDO::open($path);
            return self::$pdo;
        }

        $host = Config::get('DB_HOST', '127.0.0.1');
        $port = Config::get('DB_PORT', '3306');
        $database = Config::get('DB_DATABASE', 'multi_business_platform');
        $username = Config::get('DB_USERNAME', 'root');
        $password = Config::get('DB_PASSWORD', '');
        $charset = Config::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        try {
            self::$pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $exception) {
            app_log('Database connection failed', ['message' => $exception->getMessage()]);
            throw new RuntimeException('Unable to connect to the database. Please check configuration.');
        }

        return self::$pdo;
    }

    /**
     * True when running on the SQLite compatibility driver (testing only).
     */
    public static function isSqliteCompat(): bool
    {
        return strtolower((string) Config::get('DB_DRIVER', 'mysql')) === 'sqlite';
    }
}
