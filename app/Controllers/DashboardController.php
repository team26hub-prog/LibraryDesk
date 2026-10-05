<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Book;
use App\Models\Borrow;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireRole('member', 'librarian', 'admin');

        if (($_SESSION['role'] ?? '') === 'member') {
            $userId = (int) $_SESSION['user_id'];
            $borrows = new Borrow();
            $this->view('member/overview', [
                'title' => 'Overview',
                'firstName' => explode(' ', trim((string) ($_SESSION['user_name'] ?? 'Member')))[0],
                'stats' => $borrows->memberStats($userId),
                'activeBorrows' => $borrows->memberActive($userId),
                'availableBooks' => (new Book())->availableForMembers(6),
            ]);
            return;
        }

        $database = Database::connection();
        $stats = $database->query(
            "SELECT
                (SELECT COUNT(*) FROM users) AS user_count,
                (SELECT COUNT(*) FROM books) AS book_count,
                (SELECT COUNT(*) FROM loans WHERE status IN ('active', 'overdue')) AS active_count,
                (SELECT COUNT(*) FROM loans WHERE status IN ('active', 'overdue') AND due_at < CURRENT_DATE) AS overdue_count"
        )->fetch();

        $this->view('dashboard/index', ['title' => 'Overview', 'stats' => $stats]);
    }
}