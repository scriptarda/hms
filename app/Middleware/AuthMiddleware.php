<?php
namespace App\Middleware;

use App\Helpers\Session;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Please log in to continue.');
            $base = $GLOBALS['appConfig']['url'] ?? '';
            header('Location: ' . $base . '/login');
            exit;
            return false;
        }
        return true;
    }
}
