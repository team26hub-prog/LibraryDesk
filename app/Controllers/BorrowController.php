<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Borrow;

final class BorrowController extends Controller
{
    public function index(): void
    {
        $this->requireRole('librarian', 'admin');
        $borrow = new Borrow();
        $this->view('borrow/index', [
            'title' => 'Borrow & return',
            'loans' => $borrow->all(),
            'members' => $borrow->members(),
            'books' => $borrow->availableBooks(),
        ]);
    }

    public function history(): void
    {
        $this->requireRole('librarian', 'admin');
        $this->view('borrow/history', [
            'title' => 'Borrowing history',
            'loans' => (new Borrow())->history(),
        ]);
    }

    public function checkout(): void
    {
        $this->requireRole('librarian', 'admin');
        $this->requireValidCsrfToken();

        try {
            (new Borrow())->checkout(
                filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ?: 0,
                filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT) ?: 0,
                (string) ($_POST['due_at'] ?? '')
            );
            $this->flash('success', 'The book has been checked out.');
        } catch (\InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $this->flash('error', 'The checkout could not be completed. Check the selected member and book.');
        }

        $this->redirect('/borrow');
    }

    public function returnBook(): void
    {
        $this->requireRole('librarian', 'admin');
        $this->requireValidCsrfToken();
        $loanId = filter_input(INPUT_POST, 'loan_id', FILTER_VALIDATE_INT) ?: 0;

        try {
            (new Borrow())->returnBook($loanId);
            $this->flash('success', 'The book has been marked as returned.');
        } catch (\Throwable $exception) {
            $this->flash('error', 'That active borrow could not be returned.');
        }

        $this->redirect('/borrow');
    }
}