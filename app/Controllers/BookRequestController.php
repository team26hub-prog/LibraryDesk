<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\BookRequest;
use InvalidArgumentException;

final class BookRequestController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin');
        $this->view('requests/index', [
            'title' => 'Book requests',
            'requests' => (new BookRequest())->all(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole('member');
        $this->requireValidCsrfToken();
        $returnTo = $this->returnPath();
        try {
            $bookId = filter_var($_POST['book_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($bookId === false) {
                throw new InvalidArgumentException('Choose a valid book to request.');
            }
            $created = (new BookRequest())->create((int) $_SESSION['user_id'], $bookId);
            $this->flash($created ? 'success' : 'info', $created
                ? 'Book requested. Your request has been sent to the admin.'
                : 'You have already requested this book.');
        } catch (InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log((string) $exception);
            $this->flash('error', 'Could not send your book request. Please try again.');
        }
        $this->redirect($returnTo);
    }

    public function cancel(): void
    {
        $this->requireRole('member');
        $this->requireValidCsrfToken();
        $returnTo = $this->returnPath();
        try {
            $bookId = filter_var($_POST['book_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($bookId === false) {
                throw new InvalidArgumentException('Choose a valid book request to cancel.');
            }
            $cancelled = (new BookRequest())->cancel((int) $_SESSION['user_id'], $bookId);
            $this->flash($cancelled ? 'success' : 'info', $cancelled
                ? 'Book request cancelled. You can request another book.'
                : 'You do not have a pending request for this book.');
        } catch (InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log((string) $exception);
            $this->flash('error', 'Could not cancel your book request. Please try again.');
        }
        $this->redirect($returnTo);
    }

    public function decide(): void
    {
        $this->requireRole('admin');
        $this->requireValidCsrfToken();
        try {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $status = $_POST['status'] ?? null;
            if ($id === false || !is_string($status)) {
                throw new InvalidArgumentException('Choose a valid request and status.');
            }
            $updated = (new BookRequest())->decide($id, $status);
            $this->flash($updated ? 'success' : 'info', $updated
                ? ($status === 'granted' ? 'Book request granted.' : 'Book request cancelled.')
                : 'This request has already been handled or is no longer available.');
        } catch (InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log((string) $exception);
            $this->flash('error', 'Could not update the request. Please try again.');
        }
        $this->redirect('/book-requests');
    }

    private function returnPath(): string
    {
        $returnTo = $_POST['return_to'] ?? '/';
        return is_string($returnTo) && preg_match('~^/browse(?:\?[^\r\n]*)?$~D', $returnTo) === 1
            ? $returnTo
            : '/';
    }
}
