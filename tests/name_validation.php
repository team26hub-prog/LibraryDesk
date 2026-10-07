<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/helpers.php';
require dirname(__DIR__) . '/app/Core/Controller.php';
require dirname(__DIR__) . '/app/Controllers/UserController.php';

$valid = ['Ayesha', 'Ayesha Khan', "O'Connor", 'Anne-Marie', 'D’Arcy', 'José García', 'عائشہ خان', "Jose\u{0301}", str_repeat('A', 150)];
$invalid = ['', '@@@', 'Ayesha@@ Khan', 'User123', '123', 'Name!', '<script>', '-Name', "Name'", "Name\nOther", "Name\tOther", str_repeat('A', 151), "\u{0301}"];

if (($argv[1] ?? '') === '--fixtures') {
    echo json_encode(['pattern' => full_name_pattern(), 'valid' => $valid, 'invalid' => $invalid], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

$controller = new App\Controllers\UserController();
$validate = new ReflectionMethod($controller, 'validatedUser');
foreach ($valid as $name) {
    if (!valid_full_name($name)) throw new RuntimeException('Valid name rejected: ' . $name);
    $_POST = ['name' => $name, 'email' => 'test@example.test'];
    if ($validate->invoke($controller)['name'] !== $name) throw new RuntimeException('Valid name did not pass controller.');
}
foreach ($invalid as $name) {
    if (valid_full_name($name)) throw new RuntimeException('Invalid name accepted: ' . $name);
    $_POST = ['name' => $name, 'email' => 'test@example.test'];
    try {
        $validate->invoke($controller);
        throw new RuntimeException('Invalid name passed controller.');
    } catch (InvalidArgumentException $exception) {
    }
}
$_POST = ['name' => ['invalid'], 'email' => 'test@example.test'];
try {
    $validate->invoke($controller);
    throw new RuntimeException('Array name passed controller.');
} catch (InvalidArgumentException $exception) {
}
$_POST = ['name' => '  Ayesha Khan  ', 'email' => 'test@example.test'];
if ($validate->invoke($controller)['name'] !== 'Ayesha Khan') throw new RuntimeException('Surrounding spaces were not trimmed.');

define('BASE_PATH', '/librarymanagement');
$_SESSION = [];
$user = ['id' => null, 'name' => '', 'email' => '', 'phone' => '', 'role' => 'member', 'status' => 'active'];
ob_start();
require dirname(__DIR__) . '/app/Views/users/create.php';
$html = ob_get_clean();
$document = new DOMDocument();
@$document->loadHTML('<?xml encoding="UTF-8">' . $html);
$xpath = new DOMXPath($document);
if ($xpath->query('//a[contains(@class, "back-button") and @href="/librarymanagement/users"]')->length !== 1) throw new RuntimeException('Back button missing or incorrect.');
$input = $xpath->query('//input[@name="name"]')->item(0);
if ($input->getAttribute('pattern') !== full_name_pattern()) throw new RuntimeException('Browser and server patterns differ.');

echo "Passed: valid/invalid Unicode names, controller checks, malformed input, trimming, and Back button.\n";
