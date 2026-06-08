<?php
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

$appConfig = require CONFIG_PATH . '/app.php';
$dbConfig = require CONFIG_PATH . '/database.php';
require CONFIG_PATH . '/constants.php';

date_default_timezone_set($appConfig['timezone']);

spl_autoload_register(function ($class) {
    $prefixes = [
        'App\\Controllers\\' => APP_PATH . '/Controllers/',
        'App\\Jobs\\' => APP_PATH . '/Jobs/',
        'App\\Models\\' => APP_PATH . '/Models/',
        'App\\Services\\' => APP_PATH . '/Services/',
        'App\\Repositories\\' => APP_PATH . '/Repositories/',
        'App\\Middleware\\' => APP_PATH . '/Middleware/',
        'App\\Helpers\\' => APP_PATH . '/Helpers/',
    ];

    foreach ($prefixes as $prefix => $dir) {
        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
}

if (!file_exists(CONFIG_PATH . '/installed.lock')) {
    fwrite(STDERR, "HEMS is not installed. Run the installer before starting the SLA monitor.\n");
    exit(1);
}

use App\Helpers\Database;
use App\Jobs\SlaMonitorJob;

Database::init($dbConfig);

$limit = max(1, (int)($argv[1] ?? 500));
$result = (new SlaMonitorJob())->handle($limit);

echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
