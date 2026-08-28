<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\HttpException;

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $value = trim($value, "\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function env_value(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url_path(): string
{
    if (in_array(PHP_SAPI, ['cli', 'php', 'wasm'], true)) {
        return '';
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim(dirname($scriptName), '/');

    if ($base === '/' || $base === '.' || $base === '\\') {
        return '';
    }

    return $base;
}

function url(string $path = ''): string
{
    if ($path !== '' && preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $base = base_url_path();
    $path = '/' . ltrim($path, '/');

    if ($path === '/') {
        return $base === '' ? '/' : $base . '/';
    }

    return $base . $path;
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function upload_url(?string $path): string
{
    if (!$path) {
        return '';
    }
    return url('/' . ltrim($path, '/'));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function old(string $key, $default = '')
{
    return $_SESSION['_old'][$key] ?? $default;
}

function clear_old_input(): void
{
    unset($_SESSION['_old']);
}

function redirect(string $path): void
{
    if (in_array(PHP_SAPI, ['cli', 'php', 'wasm'], true)) {
        // Console harnesses / tests cannot observe header(); make the
        // redirect explicit so CLI runs exercise the same code paths.
        fwrite(defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w'), "\n[redirect] " . $path . "\n");
        exit;
    }
    header('Location: ' . url($path));
    exit;
}

function abort_request(int $statusCode = 404, string $message = ''): void
{
    throw new HttpException($statusCode, $message);
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim($value, '-');
    return $value !== '' ? $value : 'item';
}

function text_excerpt(?string $text, int $limit = 120): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $limit, '...', 'UTF-8');
    }
    return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
}

function valid_hex_color(?string $value, string $fallback): string
{
    $value = trim((string) $value);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
}

function contrast_text_color(string $hex): string
{
    $hex = ltrim(valid_hex_color($hex, '#2563eb'), '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luminance = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return $luminance > 145 ? '#0f172a' : '#ffffff';
}

function website_section_enabled(array $settings, string $section, bool $default = true): bool
{
    $visibility = json_decode((string) ($settings['section_visibility'] ?? ''), true);
    if (!is_array($visibility)) {
        return $default;
    }
    return array_key_exists($section, $visibility) ? (bool) $visibility[$section] : $default;
}

function format_money($amount, string $currency = 'INR'): string
{
    $amount = (float) ($amount ?? 0);
    $symbol = $currency === 'INR' ? '₹' : ($currency . ' ');
    return $symbol . number_format($amount, 2);
}

function format_date($date, string $fallback = '—'): string
{
    if (!$date) {
        return $fallback;
    }

    try {
        return (new DateTimeImmutable((string) $date))->format('d M Y');
    } catch (Throwable $e) {
        return $fallback;
    }
}

function format_datetime($date, string $fallback = '—'): string
{
    if (!$date) {
        return $fallback;
    }

    try {
        return (new DateTimeImmutable((string) $date))->format('d M Y, h:i A');
    } catch (Throwable $e) {
        return $fallback;
    }
}

function time_ago($date): string
{
    if (!$date) {
        return '—';
    }

    try {
        $time = new DateTimeImmutable((string) $date);
        $now = new DateTimeImmutable('now');
        $diff = $now->getTimestamp() - $time->getTimestamp();
        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' min ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' hr ago';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . ' days ago';
        }
        return $time->format('d M Y');
    } catch (Throwable $e) {
        return '—';
    }
}

function status_badge(string $status): string
{
    $safeStatus = e(str_replace('_', ' ', ucfirst($status)));
    $class = 'badge';
    $map = [
        'active' => 'success',
        'approved' => 'success',
        'paid' => 'success',
        'converted' => 'success',
        'completed' => 'success',
        'expiring' => 'warning',
        'grace_period' => 'warning',
        'pending' => 'warning',
        'new' => 'info',
        'in_progress' => 'info',
        'contacted' => 'info',
        'expired' => 'danger',
        'suspended' => 'danger',
        'rejected' => 'danger',
        'failed' => 'danger',
        'cancelled' => 'muted',
        'inactive' => 'muted',
        'website_disabled' => 'muted',
        'no_subscription' => 'muted',
        'archived' => 'muted',
        'closed' => 'muted',
        'under_review' => 'info',
        'changes_requested' => 'warning',
        'published' => 'success',
        'unpublished' => 'muted',
    ];
    $class .= ' ' . ($map[$status] ?? 'muted');
    return '<span class="' . $class . '">' . $safeStatus . '</span>';
}

function app_log(string $message, array $context = []): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
    }
    $line .= PHP_EOL;
    $logFile = STORAGE_PATH . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0775, true);
    }
    file_put_contents($logFile, $line, FILE_APPEND);
}

function flash_messages(): array
{
    return Flash::all();
}

/**
 * Inline SVG icon set that mirrors the Material Symbols style used by the
 * Stitch design without requiring any external CDN (works offline in XAMPP).
 */
function icon(string $name, string $class = ''): string
{
    static $paths = [
        'dashboard' => '<path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>',
        'storefront' => '<path d="M20 4H4v2h16V4zm1 4H3v2.5c0 .3.2.5.5.5h.5l1 8.6c.1.5.5.9 1.1.9h8.8c.6 0 1-.4 1.1-.9l1-8.6h.5c.3 0 .5-.2.5-.5V8zm-4 8.8H7L6.2 10h11.6l-.8 6.8z"/>',
        'inventory' => '<path d="M20 2H4C2.9 2 2 2.9 2 4v3.01c0 .72.43 1.34 1 1.65V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.66c.57-.31 1-.93 1-1.65V4c0-1.1-.9-2-2-2zM6 19H4v-2h2v2zm0-9H4V9.5l2-.01V10zm8 9h-6v-2h6v2zm0-4h-6v-2h6v2zm0-4h-6V9h6v2zm4 4h-2V9.5l2-.01V13zm0-9h-2v2h2V6z"/>',
        'chat' => '<path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>',
        'settings' => '<path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.49.49 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.48.48 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.73 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.49.49 0 0 0-.12-.61l-2.01-1.58zM12 15.6a3.59 3.59 0 1 1 0-7.18 3.59 3.59 0 0 1 0 7.18z"/>',
        'notifications' => '<path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/>',
        'logout' => '<path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8v-2H4V5z"/>',
        'person' => '<path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/>',
        'groups' => '<path d="M16 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3zM8 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
        'credit-card' => '<path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>',
        'build' => '<path d="M22.61 16.41 15.58 9.38a5.99 5.99 0 0 0-6.76-8.79l3.65 3.65-2.12 2.12L5.03 4.15a5.99 5.99 0 0 0 8.76 6.76l7.03 7.03a1 1 0 0 1 0 1.41l-1.41 1.42z"/>',
        'public' => '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm7.93 9h-3.39a15.9 15.9 0 0 0-1.46-6.07A8.02 8.02 0 0 1 19.93 11zM12 20c-.64 0-2.3-2.76-2.53-7h5.06c-.23 4.24-1.89 7-2.53 7zM9.47 11C9.7 6.76 11.36 4 12 4s2.3 2.76 2.53 7H9.47zM6.92 4.93A15.9 15.9 0 0 0 5.46 11H2.07a8.02 8.02 0 0 1 4.85-6.07zM2.07 13h3.39a15.9 15.9 0 0 0 1.46 6.07A8.02 8.02 0 0 1 2.07 13zm9.93 0H9.47c.23 4.24 1.89 7 2.53 7s2.3-2.76 2.53-7H12zm5.58 6.07A15.9 15.9 0 0 0 20.54 13h3.39a8.02 8.02 0 0 1-4.85 6.07z"/>',
        'palette' => '<path d="M12 2a10 10 0 0 0 0 20c1.1 0 2-.9 2-2 0-.51-.2-1-.51-1.35-.3-.34-.49-.8-.49-1.15 0-1.1.9-2 2-2h1.55A5.4 5.4 0 0 0 22 10C22 5.58 17.52 2 12 2zm-5.5 9a1.5 1.5 0 1 1 1.5-1.5 1.5 1.5 0 0 1-1.5 1.5zm3-4a1.5 1.5 0 1 1 1.5-1.5 1.5 1.5 0 0 1-1.5 1.5zm5 0a1.5 1.5 0 1 1 1.5-1.5 1.5 1.5 0 0 1-1.5 1.5zm3 4a1.5 1.5 0 1 1 1.5-1.5 1.5 1.5 0 0 1-1.5 1.5z"/>',
        'visibility' => '<path d="M12 6a7.7 7.7 0 0 0-7.9 6 7.7 7.7 0 0 0 7.9 6 7.7 7.7 0 0 0 7.9-6 7.7 7.7 0 0 0-7.9-6zm0 10a4 4 0 1 1 4-4 4 4 0 0 1-4 4zm0-6.2A2.2 2.2 0 1 0 14.2 12 2.2 2.2 0 0 0 12 9.8z"/>',
        'lock' => '<path d="M18 8h-1V6A5 5 0 0 0 7 6v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2zM9 6a3 3 0 0 1 6 0v2H9V6zm9 14H6V10h12v10zm-6-3a2 2 0 1 0-2-2 2 2 0 0 0 2 2z"/>',
        'search' => '<path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.49 4.49 0 0 1 9.5 14z"/>',
        'receipt' => '<path d="M18 17H6v-2h12v2zm0-4H6v-2h12v2zm0-4H6V7h12v2zM3 22l1.5-1.5L6 22l1.5-1.5L9 22l1.5-1.5L12 22l1.5-1.5L15 22l1.5-1.5L18 22l1.5-1.5L21 22V2l-1.5 1.5L18 2l-1.5 1.5L15 2l-1.5 1.5L12 2l-1.5 1.5L9 2 7.5 3.5 6 2 4.5 3.5 3 2v20z"/>',
        'history' => '<path d="M13 3a9 9 0 0 0-9 9H1l3.9 3.9.1.1L9 13H6a7 7 0 1 1 7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.95 8.95 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>',
        'bolt' => '<path d="M11 21h-1l1-7H7.5a.5.5 0 0 1-.47-.69L9.45 3h3.55l-1 8h3.5a.5.5 0 0 1 .47.67L12.55 21z"/>',
        'flag' => '<path d="M14.4 6 14 4H5v17h2v-7h5.6l.4 2h7V6z"/>',
        'check' => '<path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>',
        'close' => '<path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>',
        'menu' => '<path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>',
        'send' => '<path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/>',
        'google' => '<path d="M12 11v2.8h4.6a4 4 0 0 1-1.7 2.6l2.7 2.1c1.6-1.5 2.5-3.7 2.5-6.3 0-.6-.06-1.2-.18-1.7H12z"/><path d="M12 21c2.4 0 4.5-.8 6-2.2l-2.7-2.1c-.8.5-1.7.9-3.3.9a5.3 5.3 0 0 1-5-3.6L4.2 13v2.2A7.5 7.5 0 0 0 12 21z"/><path d="M7 14a7.6 7.6 0 0 1-.44-2.5C6.56 10.9 6.7 10 7 9.2V7H4.2A7.5 7.5 0 0 0 2.5 11.5 7.5 7.5 0 0 0 4.2 16L7 14z"/><path d="M12 6.5c1.4 0 2.6.5 3.5 1.4l2.6-2.6A7.4 7.4 0 0 0 12 4a7.5 7.5 0 0 0-7.8 3L7 9.2A5.3 5.3 0 0 1 12 6.5z"/>',
    ];
    $body = $paths[$name] ?? $paths['check'];
    return '<svg class="icon' . ($class !== '' ? ' ' . e($class) : '') . '" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">' . $body . '</svg>';
}

/**
 * Google OAuth configuration check (server side, never trust the client).
 */
function google_oauth_configured(): bool
{
    return \App\Core\Config::get('GOOGLE_CLIENT_ID') !== null && \App\Core\Config::get('GOOGLE_CLIENT_SECRET') !== null;
}
