<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';
$envValues = [];

if (is_file($envPath)) {
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES);
    if ($envLines === false) {
        throw new RuntimeException('Could not read the .env file.');
    }

    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $separator = strpos($line, '=');
        if ($separator === false) {
            continue;
        }

        $key = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));
        if (preg_match('/^[A-Z][A-Z0-9_]*$/', $key) !== 1) {
            continue;
        }

        if (
            strlen($value) >= 2
            && (($value[0] === '"' && str_ends_with($value, '"'))
                || ($value[0] === "'" && str_ends_with($value, "'")))
        ) {
            $value = substr($value, 1, -1);
        }

        $envValues[$key] = $value;
    }
}

$env = static function (string $key) use ($envValues): ?string {
    $processValue = getenv($key);
    if ($processValue !== false) {
        return $processValue;
    }

    return $envValues[$key] ?? null;
};

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