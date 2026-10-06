<?php

declare(strict_types=1);

$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocal = in_array($remoteAddress, ['127.0.0.1', '::1'], true);
$env = require __DIR__ . '/config/environment.php';
$setupToken = $env('SETUP_TOKEN') ?? '';
$remoteSetupEnabled = strlen($setupToken) >= 32;

session_start();
if (!isset($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$projectRoot = __DIR__;
$lockPath = $projectRoot . DIRECTORY_SEPARATOR . '.setup.lock';
$complete = is_file($lockPath);
$message = '';
$messageType = 'error';
$oldName = '';
$oldEmail = '';
$authorized = $isLocal || ($remoteSetupEnabled
    && is_string($_SESSION['_setup_authorization'] ?? null)
    && hash_equals(hash('sha256', $setupToken), $_SESSION['_setup_authorization']));

if (!$isLocal && !$remoteSetupEnabled) {
    http_response_code(403);
    $message = 'Hosted setup is disabled. Configure a private SETUP_TOKEN of at least 32 characters in the server .env file to enable it.';
}

$validEmail = static function (string $email): bool {
    if (mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    $separator = strrpos($email, '@');
    if ($separator === false) {
        return false;
    }

    $localPart = substr($email, 0, $separator);
    $domain = substr($email, $separator + 1);
    if (strlen($localPart) > 64 || strlen($domain) > 253) {
        return false;
    }

    $labels = explode('.', $domain);
    if (count($labels) < 2) {
        return false;
    }

    foreach ($labels as $label) {
        if (
            $label === ''
            || strlen($label) > 63
            || preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/iD', $label) !== 1
        ) {
            return false;
        }
    }

    $topLevelDomain = end($labels);
    return strlen($topLevelDomain) >= 2 && preg_match('/[a-z]/i', $topLevelDomain) === 1;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !$complete) {
    $submittedToken = $_POST['_csrf'] ?? null;
    if (
        !is_string($submittedToken)
        || !hash_equals((string) $_SESSION['_csrf'], $submittedToken)
    ) {
        http_response_code(419);
        exit('The form expired. Refresh the page and try again.');
    }

    if (!$authorized && $remoteSetupEnabled && ($_POST['_setup_action'] ?? '') === 'unlock') {
        $submittedSetupToken = $_POST['setup_token'] ?? null;
        if (is_string($submittedSetupToken) && hash_equals($setupToken, $submittedSetupToken)) {
            session_regenerate_id(true);
            $_SESSION['_setup_authorization'] = hash('sha256', $setupToken);
            $authorized = true;
        } else {
            http_response_code(403);
            $message = 'The setup key is incorrect. Use the SETUP_TOKEN configured in the server .env file.';
        }
    }
}

// Unlocking and installation are separate submissions; never install on an unlock request.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !$complete && $authorized
    && ($_POST['_setup_action'] ?? '') === 'install') {

    $nameInput = $_POST['name'] ?? null;
    $emailInput = $_POST['email'] ?? null;
    $passwordInput = $_POST['password'] ?? null;
    $confirmationInput = $_POST['password_confirmation'] ?? null;
    $name = is_string($nameInput) ? trim($nameInput) : '';
    $email = is_string($emailInput) ? mb_strtolower(trim($emailInput)) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';
    $confirmation = is_string($confirmationInput) ? $confirmationInput : '';
    $oldName = $name;
    $oldEmail = $email;

    if (
        $name === ''
        || !mb_check_encoding($name, 'UTF-8')
        || mb_strlen($name) > 150
        || preg_match('/[\x00-\x1F\x7F]/u', $name) === 1
    ) {
        $message = 'Enter a name using 150 characters or fewer, without control characters.';
    } elseif (!$validEmail($email)) {
        $message = 'Enter a valid email address with a fully qualified domain.';
    } elseif (strlen($password) < 8 || strlen($password) > 72 || $password !== $confirmation) {
        $message = 'Passwords must match and contain between 8 and 72 bytes.';
    } else {
        try {
            $config = require $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
            $databaseName = (string) $config['database'];
            if (preg_match('/^[A-Za-z0-9_]+$/D', $databaseName) !== 1) {
                throw new RuntimeException('DB_DATABASE must contain only letters, numbers, and underscores.');
            }

            $server = new PDO(
                sprintf('mysql:host=%s;port=%s;charset=%s', $config['host'], $config['port'], $config['charset']),
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $lockName = 'librarydesk_setup_' . substr(hash('sha256', $databaseName), 0, 40);
            $lockStatement = $server->prepare('SELECT GET_LOCK(:lock_name, 10)');
            $lockStatement->execute(['lock_name' => $lockName]);
            if ((int) $lockStatement->fetchColumn() !== 1) {
                throw new RuntimeException('Another setup is currently running. Wait a moment and retry.');
            }

            try {
                if (is_file($lockPath)) {
                    throw new RuntimeException('Setup has already completed. Remove setup.php from the web root.');
                }

                $databaseDsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $databaseName,
                    $config['charset']
                );
                $databaseOptions = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                try {
                    $database = new PDO($databaseDsn, $config['username'], $config['password'], $databaseOptions);
                } catch (PDOException $exception) {
                    if ((int) ($exception->errorInfo[1] ?? 0) !== 1049) {
                        throw $exception;
                    }
                    // Local installs may create a database; cPanel databases normally already exist.
                    $server->exec(
                        'CREATE DATABASE IF NOT EXISTS `' . $databaseName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
                    );
                    $database = new PDO($databaseDsn, $config['username'], $config['password'], $databaseOptions);
                }

                $existingUsersTable = $database->prepare(
                    "SELECT COUNT(*) FROM information_schema.tables
                     WHERE table_schema = :database AND table_name = 'users'"
                );
                $existingUsersTable->execute(['database' => $databaseName]);
                if ((int) $existingUsersTable->fetchColumn() > 0) {
                    $existingUsers = (int) $database->query('SELECT COUNT(*) FROM users')->fetchColumn();
                    if ($existingUsers > 0) {
                        throw new RuntimeException('This database already has user accounts. Setup will not change them.');
                    }
                }

                $schemaPath = $projectRoot . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'schema.sql';
                $schema = file_get_contents($schemaPath);
                if ($schema === false) {
                    throw new RuntimeException('Could not read database/schema.sql.');
                }

                $tableStatements = 0;
                foreach (explode(';', $schema) as $statement) {
                    $statement = trim($statement);
                    if (preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\b/i', $statement) !== 1) {
                        continue;
                    }

                    $database->exec($statement);
                    $tableStatements++;
                }
                if ($tableStatements === 0) {
                    throw new RuntimeException('No table definitions were found in database/schema.sql.');
                }

                $createAdmin = $database->prepare(
                    "INSERT INTO users (name, email, password_hash, role, status)
                     VALUES (:name, :email, :password_hash, 'admin', 'active')"
                );
                $createAdmin->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $lockWritten = file_put_contents($lockPath, 'LibraryDesk setup completed.' . PHP_EOL, LOCK_EX);
                if ($lockWritten === false) {
                    error_log('LibraryDesk setup completed, but the .setup.lock file could not be written.');
                }

                $complete = true;
                unset($_SESSION['_setup_authorization']);
                $messageType = 'success';
                $message = 'Setup is complete. Sign in using the administrator account you just created.';
                $oldName = '';
                $oldEmail = '';
            } finally {
                $releaseLock = $server->prepare('SELECT RELEASE_LOCK(:lock_name)');
                $releaseLock->execute(['lock_name' => $lockName]);
            }
        } catch (PDOException $exception) {
            error_log((string) $exception);
            http_response_code(500);
            $message = 'Database setup failed. Check the .env database settings. On cPanel, create the database and user first, assign the user to the database with the required privileges, and use their full prefixed names.';
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
        }
    }
}

$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/setup.php'));
$basePath = $basePath === '/' ? '' : rtrim($basePath, '/');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LibraryDesk setup</title>
    <link rel="stylesheet" href="<?= $escape($basePath) ?>/public/assets/css/app.css">
    <style>
        .setup-page { max-width: 620px; margin: 48px auto; padding: 24px; }
        .setup-page h1 { margin-bottom: 10px; }
        .setup-description { margin-bottom: 24px; color: #333; font-size: 16px; }
        .setup-warning { margin: 0 0 20px; padding: 14px; border: 1px solid #edc9c3; background: #fff2ef; color: #763b32; font-size: 14px; }
        .setup-success { border-color: #c8e3ce; background: #edf7ef; color: #2d6841; }
        .setup-form { display: grid; gap: 16px; }
        .setup-form label { display: grid; gap: 7px; color: #111; font-size: 14px; font-weight: 700; }
        .setup-form input { width: 100%; height: 42px; padding: 0 11px; border: 1px solid #dce3dd; background: #fff; color: #111; font: inherit; }
        @media (max-width: 620px) { .setup-page { margin: 20px auto; padding: 16px; } }
    </style>
</head>
<body>
<main class="setup-page">
    <p class="eyebrow">LIBRARYDESK INSTALLER</p>
    <h1>Initial setup</h1>
    <p class="setup-description">Create the database tables and the first administrator account.</p>

    <?php if ($message !== ''): ?>
        <div class="setup-warning <?= $messageType === 'success' ? 'setup-success' : '' ?>" role="status">
            <?= $escape($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($complete): ?>
        <a class="button button-primary" href="<?= $escape($basePath) ?>/login">Go to sign in</a>
        <p class="setup-description">For security, remove <code>setup.php</code> from the web root now.</p>
    <?php elseif (!$authorized && $remoteSetupEnabled): ?>
        <form class="form-panel setup-form" method="post" action="<?= $escape($basePath) ?>/setup.php">
            <input type="hidden" name="_csrf" value="<?= $escape($_SESSION['_csrf']) ?>">
            <input type="hidden" name="_setup_action" value="unlock">
            <label>Private setup key
                <input type="password" name="setup_token" minlength="32" autocomplete="off" required autofocus>
            </label>
            <button class="button button-primary" type="submit">Unlock setup</button>
        </form>
    <?php elseif ($authorized): ?>
        <form class="form-panel setup-form" method="post" action="<?= $escape($basePath) ?>/setup.php">
            <input type="hidden" name="_csrf" value="<?= $escape($_SESSION['_csrf']) ?>">
            <input type="hidden" name="_setup_action" value="install">
            <label>Administrator name
                <input type="text" name="name" maxlength="150" value="<?= $escape($oldName) ?>" autocomplete="name" required autofocus>
            </label>
            <label>Administrator email
                <input type="email" name="email" maxlength="190" value="<?= $escape($oldEmail) ?>" autocomplete="email" required>
            </label>
            <label>Password
                <input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
            </label>
            <label>Confirm password
                <input type="password" name="password_confirmation" minlength="8" maxlength="72" autocomplete="new-password" required>
            </label>
            <button class="button button-primary" type="submit">Install LibraryDesk</button>
        </form>
        <p class="setup-description">Setup refuses to run if user accounts already exist. Hosted setup requires your private setup key.</p>
    <?php endif; ?>
</main>
</body>
</html>
