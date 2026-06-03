<?php
namespace App\Helpers;

/**
 * CSRF Protection
 */
class CSRF
{
    private static string $tokenKey = '_csrf_token';

    public static function generate(): string
    {
        if (!Session::has(self::$tokenKey)) {
            Session::set(self::$tokenKey, bin2hex(random_bytes(32)));
        }
        return Session::get(self::$tokenKey);
    }

    public static function token(): string
    {
        return Session::get(self::$tokenKey, '');
    }

    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    public static function meta(): string
    {
        $token = self::token();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }

    public static function validate(?string $token = null): bool
    {
        $token = $token ?? ($_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        $sessionToken = self::token();
        if (empty($token) || empty($sessionToken)) return false;
        return hash_equals($sessionToken, $token);
    }

    public static function regenerate(): string
    {
        Session::set(self::$tokenKey, bin2hex(random_bytes(32)));
        return self::token();
    }
}
