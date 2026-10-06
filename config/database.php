<?php

declare(strict_types=1);

$env = require __DIR__ . '/environment.php';

$required = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
$missing = array_values(array_filter($required, static fn (string $key): bool => $env($key) === null));
if ($missing !== []) {
    throw new RuntimeException(
        'Missing database configuration: ' . implode(', ', $missing) . '. Copy .env.example to .env and configure it.'
    );
}

return [
    'host' => $env('DB_HOST'),
    'port' => $env('DB_PORT'),
    'database' => $env('DB_DATABASE'),
    'username' => $env('DB_USERNAME'),
    'password' => $env('DB_PASSWORD'),
    'charset' => $env('DB_CHARSET') ?? 'utf8mb4',
];