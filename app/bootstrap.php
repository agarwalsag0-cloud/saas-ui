<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');
define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');

require_once APP_PATH . DIRECTORY_SEPARATOR . 'helpers.php';

load_env(ROOT_PATH . DIRECTORY_SEPARATOR . '.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $prefixLength = strlen($prefix);

    if (strncmp($prefix, $class, $prefixLength) !== 0) {
        return;
    }

    $relativeClass = substr($class, $prefixLength);
    $file = APP_PATH . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

use App\Core\Config;

$timezone = Config::get('APP_TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set($timezone);

$appDebug = filter_var(Config::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $appDebug ? '1' : '0');
ini_set('display_startup_errors', $appDebug ? '1' : '0');
error_reporting(E_ALL);

if ((!in_array(PHP_SAPI, ['cli', 'php', 'wasm'], true) || Config::bool('MBSP_CLI_SESSIONS', false)) && session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name(Config::get('SESSION_NAME', 'MBSPSESSID'));
    session_start();

    // Session validity: idle expiry for authenticated portals. Activity is
    // stamped on every request; when the window is exceeded all identity keys
    // are dropped so guards treat the visitor as logged out.
    if (session_status() === PHP_SESSION_ACTIVE) {
        $idleSeconds = max(300, ((int) Config::get('SESSION_IDLE_MINUTES', '120')) * 60);
        $last = isset($_SESSION['_last_activity']) ? (int) $_SESSION['_last_activity'] : null;
        if ($last !== null && (time() - $last) > $idleSeconds && (isset($_SESSION['user_id']) || isset($_SESSION['customer_account_id']))) {
            unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['business_id'], $_SESSION['customer_account_id'], $_SESSION['_intended']);
            \App\Core\Flash::warning('Your session expired after inactivity. Please sign in again.');
        }
        $_SESSION['_last_activity'] = time();
    }
}
