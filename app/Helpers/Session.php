<?php
namespace App\Helpers;

/**
 * Session Management
 */
class Session
{
    /**
     * Start the session with secure settings
     */
    public static function start(array $config = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $defaults = [
            'name'     => 'HEMS_SESSION',
            'lifetime' => 7200,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly'  => true,
            'samesite'  => 'Strict',
        ];

        $config = array_merge($defaults, $config);

        session_name($config['name']);

        session_set_cookie_params([
            'lifetime' => $config['lifetime'],
            'path'     => $config['path'],
            'domain'   => $config['domain'],
            'secure'   => $config['secure'],
            'httponly'  => $config['httponly'],
            'samesite'  => $config['samesite'],
        ]);

        session_start();

        // Regenerate session ID periodically
        if (!isset($_SESSION['_last_regenerate'])) {
            self::regenerate();
        } elseif (time() - $_SESSION['_last_regenerate'] > 300) {
            self::regenerate();
        }
    }

    /**
     * Regenerate session ID
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_last_regenerate'] = time();
    }

    /**
     * Get a session value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Check if a session key exists
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Set a flash message (available for next request only)
     */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get and remove a flash message
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Check if flash message exists
     */
    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    /**
     * Destroy the session
     */
    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Get the current authenticated user ID
     */
    public static function userId(): ?int
    {
        return self::get('user_id');
    }

    /**
     * Check if user is authenticated
     */
    public static function isLoggedIn(): bool
    {
        return self::has('user_id');
    }

    /**
     * Get all session data
     */
    public static function all(): array
    {
        return $_SESSION;
    }
}
