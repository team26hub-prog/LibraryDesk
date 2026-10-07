<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Book;
use App\Models\BookRequest;
use App\Models\Borrow;
use App\Models\User;
use InvalidArgumentException;

final class MemberController extends Controller
{
    private const PAGE_SIZE = 12;

    public function browse(): void
    {
        $this->requireRole('member');
        $search = $this->queryString('q', 100);
        $category = $this->queryString('category', 120);
        $availableFilter = $_GET['available'] ?? '';
        if (!is_string($availableFilter) || !in_array($availableFilter, ['', '1'], true)) {
            http_response_code(400);
            exit('Invalid availability filter.');
        }
        $availableOnly = $availableFilter === '1';
        $page = $this->pageNumber();
        $books = new Book();
        $total = $books->memberBrowseCount($search, $category, $availableOnly);
        $pageCount = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min($page, $pageCount);
        $this->view('member/browse', [
            'title' => 'Browse books',
            'requestStatuses' => (new BookRequest())->memberStatuses((int) $_SESSION['user_id']),
            'books' => $books->browseForMembers(
                $search,
                $category,
                $availableOnly,
                self::PAGE_SIZE,
                ($page - 1) * self::PAGE_SIZE
            ),
            'categories' => $books->memberCategories(),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'availableOnly' => $availableOnly,
            ],
            'pagination' => ['page' => $page, 'pageCount' => $pageCount, 'total' => $total],
        ]);
    }

    public function borrows(): void
    {
        $this->requireRole('member');
        $this->view('member/borrows', [
            'title' => 'My borrows',
            'borrows' => (new Borrow())->memberActive((int) $_SESSION['user_id']),
        ]);
    }

    public function history(): void
    {
        $this->requireRole('member');
        $userId = (int) $_SESSION['user_id'];
        $borrows = new Borrow();
        $total = $borrows->memberHistoryCount($userId);
        $pageCount = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min($this->pageNumber(), $pageCount);
        $this->view('member/history', [
            'title' => 'Borrow history',
            'borrows' => $borrows->memberHistory(
                $userId,
                self::PAGE_SIZE,
                ($page - 1) * self::PAGE_SIZE
            ),
            'pagination' => ['page' => $page, 'pageCount' => $pageCount, 'total' => $total],
        ]);
    }

    public function profile(): void
    {
        $this->requireRole('member');
        $user = (new User())->find((int) $_SESSION['user_id']);
        if ($user === null) {
            http_response_code(404);
            exit('Member profile not found.');
        }

        $this->view('member/profile', ['title' => 'Profile', 'user' => $user]);
    }

    public function updateName(): void
    {
        $this->requireRole('member');
        $this->requireValidCsrfToken();
        $submittedName = $_POST['name'] ?? null;
        $name = is_string($submittedName) ? trim($submittedName) : '';
        if (!valid_full_name($name)) {
            $this->flash('error', 'Enter a full name using letters, spaces, apostrophes, or hyphens, up to 150 characters.');
            $this->redirect('/profile');
        }

        (new User())->updateMemberName((int) $_SESSION['user_id'], $name);
        $_SESSION['user_name'] = $name;
        $this->flash('success', 'Your name has been updated.');
        $this->redirect('/profile');
    }

    public function updatePassword(): void
    {
        $this->requireRole('member');
        $this->requireValidCsrfToken();
        $currentPassword = $_POST['current_password'] ?? null;
        $newPassword = $_POST['new_password'] ?? null;
        $confirmation = $_POST['password_confirmation'] ?? null;

        if (
            !is_string($currentPassword)
            || !is_string($newPassword)
            || !is_string($confirmation)
            || strlen($newPassword) < 8
            || $newPassword !== $confirmation
        ) {
            $this->flash('error', 'The new password must be at least 8 characters and match its confirmation.');
            $this->redirect('/profile');
        }

        try {
            (new User())->changeMemberPassword((int) $_SESSION['user_id'], $currentPassword, $newPassword);
        } catch (InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect('/profile');
        }

        $this->flash('success', 'Your password has been changed.');
        $this->redirect('/profile');
    }

    private function queryString(string $key, int $maximumLength): string
    {
        $value = $_GET[$key] ?? '';
        if (!is_string($value)) {
            http_response_code(400);
            exit('Invalid browse filter.');
        }

        return mb_substr(trim($value), 0, $maximumLength);
    }

    private function pageNumber(): int
    {
        if (!array_key_exists('page', $_GET)) {
            return 1;
        }

        $submittedPage = $_GET['page'];
        if (!is_string($submittedPage) && !is_int($submittedPage)) {
            http_response_code(400);
            exit('Invalid page number.');
        }
        $page = filter_var($submittedPage, FILTER_VALIDATE_INT);
        if (!is_int($page) || $page < 1) {
            http_response_code(400);
            exit('Invalid page number.');
        }

        return $page;
    }
}
