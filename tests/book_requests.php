<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});
require dirname(__DIR__) . '/app/Core/helpers.php';
define('BASE_PATH', '/librarymanagement');

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$database = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$database->exec('PRAGMA foreign_keys = ON');
$database->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, role TEXT, status TEXT)');
$database->exec('CREATE TABLE books (id INTEGER PRIMARY KEY, title TEXT, author TEXT, category TEXT, total_copies INTEGER, available_copies INTEGER)');
$database->exec("CREATE TABLE book_requests (id INTEGER PRIMARY KEY, user_id INTEGER REFERENCES users(id), book_id INTEGER REFERENCES books(id), status TEXT NOT NULL DEFAULT 'pending', created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, book_id))");
$database->exec("INSERT INTO users VALUES
    (1, 'Member <one>', 'one@example.test', 'member', 'active'),
    (2, 'Member two', 'two@example.test', 'member', 'active'),
    (3, 'Admin', 'admin@example.test', 'admin', 'active'),
    (4, 'Inactive', 'inactive@example.test', 'member', 'inactive'),
    (5, 'Staff', 'staff@example.test', 'librarian', 'active')");
$database->exec("INSERT INTO books VALUES (1, 'Book <one>', 'Author', 'Fiction', 3, 3), (2, 'Unavailable', 'Author', NULL, 1, 0)");
$model = new App\Models\BookRequest($database);

// Route checks run separately because redirects intentionally exit.
$scenario = $argv[1] ?? '';
if ($scenario !== '') {
    (new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $database);
    $_SESSION = ['user_id' => 1, 'role' => 'member', 'user_name' => 'Member', '_csrf' => 'valid-token'];
    $_SERVER['REQUEST_URI'] = BASE_PATH . '/book-requests';
    $_POST = ['book_id' => 1, 'user_id' => 2, '_csrf' => 'valid-token', 'return_to' => '/browse?q=Book'];
    if ($scenario === 'resubmit') {
        $model->create(1, 1);
        $model->decide(1, 'cancelled');
    }
    $isCancellation = str_starts_with($scenario, 'cancel');
    $isDecision = str_starts_with($scenario, 'decision');
    if ($isDecision) {
        $_SERVER['REQUEST_URI'] .= '/status';
        $_SESSION['role'] = 'admin';
        $model->create(1, 1);
        $_POST['id'] = 1;
        $_POST['status'] = $scenario === 'decision-cancel' ? 'cancelled' : 'granted';
        if ($scenario === 'decision-member') $_SESSION['role'] = 'member';
        if ($scenario === 'decision-csrf') $_POST['_csrf'] = 'wrong-token';
        if ($scenario === 'decision-invalid') $_POST['status'] = 'invalid';
        if ($scenario === 'decision-array') $_POST['status'] = ['granted'];
        if ($scenario === 'decision-missing') $_POST['id'] = 999;
        if ($scenario === 'decision-repeat') $model->decide(1, 'cancelled');
    }
    if ($isCancellation) {
        $_SERVER['REQUEST_URI'] .= '/cancel';
        $model->create(1, 1);
        $model->create(2, 1);
        $model->create(2, 2);
        if ($scenario === 'cancel-other') $_POST['book_id'] = 2;
        if ($scenario === 'cancel-missing') $_POST['book_id'] = 999;
        if ($scenario === 'cancel-invalid') $_POST['book_id'] = ['invalid'];
        if ($scenario === 'cancel-admin') $_SESSION['role'] = 'admin';
        if ($scenario === 'cancel-csrf') $_POST['_csrf'] = 'wrong-token';
        if ($scenario === 'cancel-anonymous') $_SESSION = [];
    }
    if ($scenario === 'admin-submit') $_SESSION['role'] = 'admin';
    if ($scenario === 'csrf') $_POST['_csrf'] = 'wrong-token';
    if ($scenario === 'invalid-book') $_POST['book_id'] = ['invalid'];
    if ($scenario === 'anonymous') $_SESSION = [];
    ob_start();
    register_shutdown_function(static function () use ($scenario, $database, $isCancellation, $isDecision): void {
        ob_end_clean();
        $rows = $database->query('SELECT * FROM book_requests')->fetchAll();
        if ($isDecision) {
            $expectedStatus = match ($scenario) {
                'decision-grant' => 'granted',
                'decision-cancel', 'decision-repeat' => 'cancelled',
                default => 'pending',
            };
            check(count($rows) === 1 && $rows[0]['status'] === $expectedStatus, 'Only a valid admin decision should change pending status.');
            if ($scenario === 'decision-grant' || $scenario === 'decision-cancel') check($_SESSION['flash']['type'] === 'success', 'Admin decision should confirm success.');
            if ($scenario === 'decision-member' || $scenario === 'decision-invalid' || $scenario === 'decision-array') check($_SESSION['flash']['type'] === 'error', 'Unauthorized or invalid decision must be rejected.');
            if ($scenario === 'decision-csrf') check(http_response_code() === 419, 'Admin decision needs valid CSRF.');
            if ($scenario === 'decision-missing' || $scenario === 'decision-repeat') check($_SESSION['flash']['type'] === 'info', 'Already handled or missing requests should be safe.');
        } elseif ($isCancellation) {
            check(count($rows) === ($scenario === 'cancel' ? 2 : 3), 'Cancellation must only remove the current member request.');
            check(count(array_filter($rows, static fn ($row) => (int) $row['user_id'] === 2)) === 2, 'Other member requests must remain, even with forged user_id.');
            if ($scenario === 'cancel') check($_SESSION['flash']['type'] === 'success', 'Cancellation should confirm success.');
            if ($scenario === 'cancel-csrf') check(http_response_code() === 419, 'Cancellation requires valid CSRF.');
            if ($scenario === 'cancel-admin' || $scenario === 'cancel-invalid') check($_SESSION['flash']['type'] === 'error', 'Invalid cancellation should be rejected.');
            if ($scenario === 'cancel-other' || $scenario === 'cancel-missing') check($_SESSION['flash']['type'] === 'info', 'Missing own request should be handled without an error.');
        } elseif ($scenario === 'submit' || $scenario === 'resubmit') {
            check(count($rows) === 1 && (int) $rows[0]['user_id'] === 1, 'Requester must come from the session, ignoring submitted user_id.');
            check($_SESSION['flash']['type'] === 'success', 'Successful request should display confirmation.');
            check($rows[0]['status'] === 'pending', 'New and resubmitted requests should be pending.');
        } else {
            check($rows === [], 'Invalid or unauthorized requests must not write records.');
            if ($scenario === 'csrf') check(http_response_code() === 419, 'Bad CSRF should be rejected.');
            if ($scenario === 'member-list' || $scenario === 'admin-submit') check($_SESSION['flash']['type'] === 'error', 'Wrong role should be rejected.');
        }
        echo "Passed route: $scenario\n";
    });
    $router = new App\Core\Router();
    require dirname(__DIR__) . '/routes/web.php';
    $router->dispatch($scenario === 'member-list' ? 'GET' : 'POST', $_SERVER['REQUEST_URI']);
    exit;
}

check($model->create(1, 1), 'Member should be able to request a book.');
check(!$model->create(1, 1), 'Duplicate requests should not create another record.');
check($model->create(2, 1), 'Another member can request the same book.');
check($model->create(1, 2), 'Members can request an unavailable book.');
check($model->requestedBookIds(1) === [1, 2], 'Requests should belong to the correct member.');
check($model->requestedBookIds(2) === [1], 'Other members should see only their own requested books.');
foreach ([[3, 1], [4, 1], [5, 1], [999, 1], [1, 999]] as [$userId, $bookId]) {
    $rejected = false;
    try { $model->create($userId, $bookId); } catch (InvalidArgumentException $exception) { $rejected = true; }
    check($rejected, 'Invalid accounts or books must be rejected.');
}
check((int) $database->query('SELECT available_copies FROM books WHERE id = 1')->fetchColumn() === 3, 'Requests must not reserve stock.');
$requests = $model->all();
check(count($requests) === 3 && $requests[0]['book_title'] === 'Unavailable', 'Admin list should show all requests, newest first.');
check($requests[2]['user_name'] === 'Member <one>', 'Admin list should identify the requesting member.');

$_SESSION = ['_csrf' => 'test-token'];
$requestStatuses = $model->memberStatuses(1);
$currentPath = BASE_PATH . '/browse';
$_GET = ['q' => 'Book', 'page' => '2'];
$bookCard = $database->query('SELECT * FROM books WHERE id = 1')->fetch();
ob_start();
require dirname(__DIR__) . '/app/Views/member/partials/_book-card.php';
$html = ob_get_clean();
check(str_contains($html, 'disabled') && str_contains($html, '>Requested</button>'), 'Requested cards should disable duplicate submissions.');
check(str_contains($html, '>Cancel</button>') && str_contains($html, '/book-requests/cancel'), 'Requested cards should provide cancellation.');
check(str_contains($html, '/browse?q=Book&amp;page=2'), 'Request should retain catalogue filters and page.');
check(str_contains($html, 'Book &lt;one&gt;') && !str_contains($html, 'Book <one>'), 'Book title must be escaped.');
$requestStatuses = [];
ob_start();
require dirname(__DIR__) . '/app/Views/member/partials/_book-card.php';
$html = ob_get_clean();
check(str_contains($html, '>Request</button>') && !str_contains($html, 'disabled'), 'Unrequested cards should offer Request.');
check(!str_contains($html, '>Cancel</button>'), 'Unrequested cards should not offer cancellation.');
ob_start();
require dirname(__DIR__) . '/app/Views/requests/index.php';
$html = ob_get_clean();
check(str_contains($html, 'Member &lt;one&gt;') && str_contains($html, 'one@example.test'), 'Admin list should safely display requester details.');
check(substr_count($html, 'class="request-status-actions"') === 3, 'Pending requests should each show decision buttons.');
$requests = [];
ob_start();
require dirname(__DIR__) . '/app/Views/requests/index.php';
check(str_contains(ob_get_clean(), 'No book requests yet'), 'Empty request list needs an empty state.');

check(!$model->cancel(2, 2), 'A member cannot cancel another member request.');
check($model->cancel(1, 1), 'A member should be able to cancel their own request.');
check(!$model->cancel(1, 1), 'Repeating cancellation should be safe.');
check($model->requestedBookIds(1) === [2], 'Cancelled book should no longer be requested by the member.');
check($model->requestedBookIds(2) === [1], 'Other member request for the same book must remain.');
check(count($model->all()) === 2, 'Cancelled request should disappear from the admin list.');
check($model->create(1, 1), 'Member should be able to request the cancelled book again.');
check((int) $database->query('SELECT available_copies FROM books WHERE id = 1')->fetchColumn() === 3, 'Cancelling and requesting again must not alter stock.');

$requests = $model->all();
check($model->decide((int) $requests[0]['id'], 'granted'), 'Pending request should be grantable.');
check(!$model->decide((int) $requests[0]['id'], 'cancelled'), 'A grant must not be overwritten by a later decision.');
check($model->decide((int) $requests[1]['id'], 'cancelled'), 'Pending request should be cancellable by admin.');
check(!$model->cancel(1, 1), 'Members must not remove handled requests.');
try {
    $model->decide((int) $requests[2]['id'], 'invalid');
    throw new RuntimeException('Invalid decision must be rejected.');
} catch (InvalidArgumentException $exception) {
}
$requests = $model->all();
ob_start();
require dirname(__DIR__) . '/app/Views/requests/index.php';
$html = ob_get_clean();
$document = new DOMDocument();
@$document->loadHTML($html);
$xpath = new DOMXPath($document);
$rows = $xpath->query('//tbody/tr');
foreach ([0 => 'Granted', 1 => 'Cancelled'] as $rowIndex => $label) {
    check($xpath->query('.//button', $rows->item($rowIndex))->length === 0, 'Decided request should have no decision buttons.');
    check(str_contains($rows->item($rowIndex)->textContent, $label), 'Decided request should show its saved status.');
}
check($xpath->query('.//button', $rows->item(2))->length === 2, 'Pending request should keep both buttons.');
$requestStatuses = $model->memberStatuses(1);
ob_start();
require dirname(__DIR__) . '/app/Views/member/partials/_book-card.php';
$html = ob_get_clean();
check(str_contains($html, '>Granted</button>') && !str_contains($html, '>Cancel</button>'), 'Granted member card should show the decision without Cancel.');
check((int) $database->query('SELECT available_copies FROM books WHERE id = 1')->fetchColumn() === 3, 'Admin decision must not change stock.');

$bookCard = $database->query('SELECT * FROM books WHERE id = 2')->fetch();
ob_start();
require dirname(__DIR__) . '/app/Views/member/partials/_book-card.php';
$html = ob_get_clean();
check(str_contains($html, '>Request</button>') && !str_contains($html, 'disabled'), 'Admin-cancelled card should show an active Request button.');
check(!str_contains($html, '>Cancelled</button>') && !str_contains($html, '>Cancel</button>'), 'Cancelled card should show only Request.');
check(!str_contains($html, '/book-requests/cancel'), 'Cancelled card should submit a new request instead of cancellation.');
$cancelledId = (int) $requests[1]['id'];
$database->exec("UPDATE book_requests SET created_at = '2000-01-01 00:00:00' WHERE id = $cancelledId");
check($model->create(1, 2), 'Member should be able to resubmit an admin-cancelled request.');
check($model->memberStatuses(1)[2] === 'pending', 'Resubmitted request should return to pending.');
check(count($model->all()) === 3, 'Reopening should reuse the existing row without duplicates.');
$reopened = array_values(array_filter($model->all(), static fn ($row) => (int) $row['id'] === $cancelledId))[0];
check($reopened['created_at'] !== '2000-01-01 00:00:00', 'Reopened request needs a fresh timestamp.');
check(!$model->create(1, 2), 'Resubmission must still prevent duplicate pending requests.');
check(!$model->create(1, 1), 'Granted requests must not be reopened.');
check($model->memberStatuses(2)[1] === 'pending', 'Other member requests should remain unchanged.');

echo "Passed: request lifecycle, admin-cancelled resubmission, card actions, duplicates, timestamps, and isolation.\n";
