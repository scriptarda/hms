<?php
/**
 * Front Controller
 * HEMS - Healthcare Enterprise Management System
 * 
 * All requests are routed through this file.
 */

// Error reporting based on environment
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Define base paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', __DIR__);
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// Load configuration
$appConfig = require CONFIG_PATH . '/app.php';
$dbConfig  = require CONFIG_PATH . '/database.php';
require CONFIG_PATH . '/constants.php';

// Set timezone
date_default_timezone_set($appConfig['timezone']);

// Debug mode
if ($appConfig['debug']) {
    ini_set('display_errors', 1);
}

// Autoloader
spl_autoload_register(function ($class) {
    // Map namespace prefixes to directories
    $prefixes = [
        'App\\Controllers\\'   => APP_PATH . '/Controllers/',
        'App\\Models\\'        => APP_PATH . '/Models/',
        'App\\Services\\'      => APP_PATH . '/Services/',
        'App\\Repositories\\'  => APP_PATH . '/Repositories/',
        'App\\Middleware\\'     => APP_PATH . '/Middleware/',
        'App\\Helpers\\'       => APP_PATH . '/Helpers/',
    ];

    foreach ($prefixes as $prefix => $dir) {
        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $dir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});

// Load Composer autoloader if it exists
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
}

// Initialize core helpers
use App\Helpers\Session;
use App\Helpers\Database;
use App\Helpers\Router;
use App\Helpers\CSRF;

// Check if installation is needed
if (!file_exists(CONFIG_PATH . '/installed.lock') && !isset($_GET['url'])) {
    $_GET['url'] = 'install';
} elseif (!file_exists(CONFIG_PATH . '/installed.lock')) {
    $requestedUrl = $_GET['url'] ?? '';
    if (strpos($requestedUrl, 'install') !== 0) {
        $_GET['url'] = 'install';
    }
}

// Start session
Session::start($appConfig['session']);

// Generate CSRF token
CSRF::generate();

// Initialize database connection (only if installed)
if (file_exists(CONFIG_PATH . '/installed.lock')) {
    try {
        Database::init($dbConfig);
    } catch (\PDOException $e) {
        if ($appConfig['debug']) {
            die('Database connection failed: ' . $e->getMessage());
        }
        die('System temporarily unavailable. Please contact the administrator.');
    }
}

// Store config globally
$GLOBALS['appConfig'] = $appConfig;
$GLOBALS['dbConfig'] = $dbConfig;

// Load routes
$router = new Router();
require ROOT_PATH . '/routes/web.php';

// Get the URL
$url = $_GET['url'] ?? '';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);

// Dispatch the request
try {
    $router->dispatch($url, $_SERVER['REQUEST_METHOD']);
} catch (\Exception $e) {
    if ($appConfig['debug']) {
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>Internal Server Error</h1>';
    }
}
