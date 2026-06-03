<?php
namespace App\Middleware;

use App\Helpers\CSRF;

class CsrfMiddleware
{
    public function handle(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::validate()) {
                http_response_code(403);
                echo '<h1>403 - Invalid CSRF Token</h1>';
                exit;
                return false;
            }
        }
        return true;
    }
}
