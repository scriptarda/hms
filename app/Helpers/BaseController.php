<?php
namespace App\Helpers;

/**
 * Base Controller
 */
class BaseController
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        // Add common data
        if (Session::isLoggedIn()) {
            $data['authUser'] = Session::get('user');
            $data['authRole'] = Session::get('role');
            $data['authPermissions'] = Session::get('permissions', []);
        }
        $data['flashSuccess'] = Session::getFlash('success');
        $data['flashError'] = Session::getFlash('error');
        $data['flashWarning'] = Session::getFlash('warning');
        $data['appConfig'] = $GLOBALS['appConfig'];

        View::render($view, $data, $layout);
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $url): void
    {
        $base = $GLOBALS['appConfig']['url'] ?? '';
        header('Location: ' . $base . '/' . ltrim($url, '/'));
        exit;
    }

    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $referer);
        exit;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function allInput(): array
    {
        return array_merge($_GET, $_POST);
    }

    protected function hasPermission(string $permission): bool
    {
        $permissions = Session::get('permissions', []);
        return in_array($permission, $permissions);
    }

    protected function abort(int $code = 403, string $message = 'Forbidden'): void
    {
        http_response_code($code);
        if ($code === 404) {
            include APP_PATH . '/Views/errors/404.php';
        } else {
            echo "<h1>{$code} - {$message}</h1>";
        }
        exit;
    }

    protected function uploadFile(string $fieldName, string $subDir = ''): ?string
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$fieldName];
        $config = $GLOBALS['appConfig']['uploads'];

        // Validate size
        if ($file['size'] > $config['max_size']) {
            throw new \RuntimeException('File exceeds maximum size.');
        }

        // Validate extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $config['allowed_types'])) {
            throw new \RuntimeException('File type not allowed.');
        }

        // Generate unique filename
        $filename = uniqid('hems_', true) . '.' . $ext;
        $uploadDir = $config['path'] . ($subDir ? '/' . trim($subDir, '/') : '');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filepath = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        return ($subDir ? trim($subDir, '/') . '/' : '') . $filename;
    }
}
