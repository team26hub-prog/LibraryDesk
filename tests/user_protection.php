<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Model.php';
require dirname(__DIR__) . '/app/Models/User.php';
require dirname(__DIR__) . '/app/Core/helpers.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$database = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$database->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, phone TEXT, role TEXT, status TEXT)');
$database->exec("INSERT INTO users VALUES
    (1, 'Admin', 'admin@example.test', NULL, 'admin', 'active'),
    (2, 'Member', 'member@example.test', NULL, 'member', 'active'),
    (3, 'Librarian', 'librarian@example.test', NULL, 'librarian', 'active')");
$model = new App\Models\User($database);
$originalAdmin = $model->find(1);
$changes = ['name' => 'Changed', 'email' => 'changed@example.test', 'phone' => null, 'role' => 'member', 'status' => 'inactive'];

foreach (['update', 'delete'] as $operation) {
    foreach ([1, 999] as $id) {
        $rejected = false;
        try {
            if ($operation === 'update') {
                $model->update($id, $changes);
            } else {
                $model->delete($id);
            }
        } catch (InvalidArgumentException $exception) {
            $rejected = true;
            check(str_contains($exception->getMessage(), $id === 1 ? 'protected' : 'could not be found'), 'Unexpected rejection message.');
        }
        check($rejected, "$operation should reject account $id.");
    }
}
check($model->find(1) === $originalAdmin, 'The admin account must remain unchanged.');

define('BASE_PATH', '/librarymanagement');
$_SESSION = ['_csrf' => 'test-token'];
$users = $database->query('SELECT * FROM users ORDER BY id')->fetchAll();
ob_start();
require dirname(__DIR__) . '/app/Views/users/index.php';
$html = ob_get_clean();
$document = new DOMDocument();
@$document->loadHTML($html);
$xpath = new DOMXPath($document);
$rows = $xpath->query('//tbody/tr');
check($rows->length === 3, 'All accounts should appear in the table.');
check($xpath->query('.//a | .//form | .//button', $rows->item(0))->length === 0, 'Admin row must have no edit or delete controls.');
check(str_contains($rows->item(0)->textContent, 'Protected'), 'Admin row should display Protected.');
foreach ([1, 2] as $rowIndex) {
    check($xpath->query('.//a', $rows->item($rowIndex))->length === 1, 'Non-admin row needs its edit link.');
    check($xpath->query('.//form', $rows->item($rowIndex))->length === 1, 'Non-admin row needs its delete form.');
}

foreach ([2, 3] as $id) {
    $data = $changes;
    $data['email'] = "changed$id@example.test";
    $model->update($id, $data);
    check($model->find($id)['name'] === 'Changed', 'Non-admin updates should still work.');
    $model->delete($id);
    check($model->find($id) === null, 'Non-admin deletes should still work.');
}

$model->create(['name' => 'Promoted', 'email' => 'promoted@example.test', 'phone' => null, 'role' => 'member', 'status' => 'active']);
$id = (int) $database->lastInsertId();
$data = $changes;
$data['role'] = 'admin';
$model->update($id, $data);
try {
    $model->delete($id);
    throw new RuntimeException('A newly promoted admin must be protected.');
} catch (InvalidArgumentException $exception) {
    check($model->find($id)['role'] === 'admin', 'Promoted admin must remain in the database.');
}

echo "Passed: admin protection, table controls, member/librarian management, and promoted admin protection.\n";
