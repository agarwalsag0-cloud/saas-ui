<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use RuntimeException;

/**
 * Google Identity (OAuth 2.0 authorization code flow) integration.
 *
 * This is an architecture-level integration: it only works once real
 * credentials are configured in .env (GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET)
 * and an HTTPS redirect URI is registered in Google Cloud Console.
 * Nothing here stores Google passwords — only the verified account identity
 * (sub, email, name, picture) is used to create/link a customer account.
 */
class GoogleOAuthService
{
    private const AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    public static function isConfigured(): bool
    {
        return self::clientId() !== null && self::clientSecret() !== null;
    }

    public static function clientId(): ?string
    {
        $id = Config::get('GOOGLE_CLIENT_ID');
        return $id !== null && trim($id) !== '' ? trim($id) : null;
    }

    public static function clientSecret(): ?string
    {
        $secret = Config::get('GOOGLE_CLIENT_SECRET');
        return $secret !== null && trim($secret) !== '' ? trim($secret) : null;
    }

    public static function redirectUri(): string
    {
        $configured = Config::get('GOOGLE_REDIRECT_URI');
        if ($configured !== null && trim($configured) !== '') {
            return trim($configured);
        }
        // Fallback: derive from the current request host so local installs work.
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . url('/auth/google/callback');
    }

    public static function authorizationUrl(string $state): string
    {
        $params = [
            'client_id' => (string) self::clientId(),
            'redirect_uri' => self::redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ];

        return self::AUTH_ENDPOINT . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Exchanges the authorization code and returns verified profile claims.
     *
     * @return array{sub:string,email:?string,name:string,picture:?string}
     */
    public static function exchange(string $code): array
    {
        $response = self::httpPostForm(self::TOKEN_ENDPOINT, [
            'code' => $code,
            'client_id' => (string) self::clientId(),
            'client_secret' => (string) self::clientSecret(),
            'redirect_uri' => self::redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['access_token']) || empty($data['id_token'])) {
            throw new RuntimeException('Google did not return a valid token response.');
        }

        $claims = self::decodeIdToken((string) $data['id_token']);

        // When an ID token audience/issuer check is not possible offline, still
        // verify the token against Google's tokeninfo endpoint (server-to-server).
        if ($claims === null) {
            $verify = self::httpGet('https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode((string) $data['id_token']));
            $claims = json_decode($verify, true);
        }

        if (!is_array($claims) || empty($claims['sub'])) {
            throw new RuntimeException('Could not verify the Google account identity.');
        }

        $issuer = (string) ($claims['iss'] ?? '');
        if ($issuer !== '' && !in_array($issuer, ['https://accounts.google.com', 'accounts.google.com'], true)) {
            throw new RuntimeException('Unexpected Google token issuer.');
        }
        if (($claims['aud'] ?? null) !== null && $claims['aud'] !== self::clientId()) {
            throw new RuntimeException('Unexpected Google token audience.');
        }

        return [
            'sub' => (string) $claims['sub'],
            'email' => isset($claims['email']) ? strtolower(trim((string) $claims['email'])) : null,
            'name' => trim((string) ($claims['given_name'] ?? $claims['name'] ?? 'Google User')),
            'picture' => isset($claims['picture']) ? (string) $claims['picture'] : null,
        ];
    }

    /**
     * Decodes the JWT payload without trusting it blindly (aud/iss verified by
     * caller against configured client id + tokeninfo fallback).
     */
    private static function decodeIdToken(string $idToken): ?array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            return null;
        }
        $payload = $parts[1];
        $payload = strtr($payload, '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            return null;
        }
        $claims = json_decode($decoded, true);
        return is_array($claims) ? $claims : null;
    }

    private static function httpPostForm(string $url, array $fields): string
    {
        $body = http_build_query($fields, '', '&');
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
                CURLOPT_TIMEOUT => 20,
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($response === false || $status < 200 || $status >= 300) {
                throw new RuntimeException('Google token exchange failed.');
            }
            return (string) $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
                'content' => $body,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new RuntimeException('Google token exchange failed (outbound HTTPS unavailable).');
        }
        return $response;
    }

    private static function httpGet(string $url): string
    {
        $context = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
        $response = @file_get_contents($url, false, $context);
        return $response === false ? '' : $response;
    }
}
