<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $requestPath = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    $assetRoot = realpath(__DIR__ . '/public/assets');
    $requestedFile = realpath(__DIR__ . $requestPath);
    if (
        $assetRoot !== false
        && $requestedFile !== false
        && str_starts_with($requestedFile, $assetRoot . DIRECTORY_SEPARATOR)
        && is_file($requestedFile)
    ) {
        return false;
    }
}

session_start();

if (!isset($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

$scriptDirectory = rawurldecode(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')));
define('BASE_PATH', $scriptDirectory === '/' ? '' : rtrim($scriptDirectory, '/'));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = __DIR__ . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/app/Core/helpers.php';

$router = new App\Core\Router();
require __DIR__ . '/routes/web.php';

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    echo 'An unexpected error occurred. Check the PHP error log for details.';
}