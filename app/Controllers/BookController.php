<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Book;
use InvalidArgumentException;

final class BookController extends Controller
{
    public function index(): void
    {
        $this->requireRole('librarian', 'admin');
        $this->view('books/index', [
            'title' => 'Book management',
            'books' => (new Book())->all(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole('librarian', 'admin');
        $this->view('books/create', ['title' => 'Add book', 'book' => $this->emptyBook()]);
    }

    public function edit(): void
    {
        $this->requireRole('librarian', 'admin');
        $book = (new Book())->find((int) ($_GET['id'] ?? 0));
        if ($book === null) {
            $this->flash('error', 'That book could not be found.');
            $this->redirect('/books');
        }

        $this->view('books/edit', ['title' => 'Edit book', 'book' => $book]);
    }

    public function store(): void
    {
        $this->requireRole('librarian', 'admin');
        $this->requireValidCsrfToken();
        try {
            (new Book())->create($this->validatedBook());
            $this->flash('success', 'Book added to the catalogue.');
        } catch (InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $this->flash('error', 'Could not add the book. Check that the ISBN is not already in use.');
        }
        $this->redirect('/books');
    }

    public function update(): void
    {
        $this->requireRole('librarian', 'admin');
        $this->requireValidCsrfToken();
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            $this->flash('error', 'Invalid book record.');
            $this->redirect('/books');
        }

        try {
            (new Book())->update((int) $id, $this->validatedBook());
            $this->flash('success', 'Book updated.');
        } catch (InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $this->flash('error', 'Could not update the book. Check the ISBN and copy count.');
        }
        $this->redirect('/books');
    }

    public function delete(): void
    {
        $this->requireRole('librarian', 'admin');
        $this->requireValidCsrfToken();
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        try {
            if (!$id) {
                throw new InvalidArgumentException('Invalid book record.');
            }
            (new Book())->delete((int) $id);
            $this->flash('success', 'Book deleted.');
        } catch (\Throwable $exception) {
            $this->flash('error', 'Could not delete this book while it has borrow history.');
        }
        $this->redirect('/books');
    }

    private function validatedBook(): array
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $author = trim((string) ($_POST['author'] ?? ''));
        $copies = filter_var($_POST['total_copies'] ?? null, FILTER_VALIDATE_INT);
        $yearInput = trim((string) ($_POST['published_year'] ?? ''));
        $year = $yearInput === '' ? null : filter_var($yearInput, FILTER_VALIDATE_INT);

        if ($title === '' || mb_strlen($title) > 255 || $author === '' || mb_strlen($author) > 190) {
            throw new InvalidArgumentException('Enter a title and author within the allowed length.');
        }
        if (!$copies || $copies < 1) {
            throw new InvalidArgumentException('Total copies must be at least one.');
        }
        if ($yearInput !== '' && (!$year || $year < 1 || $year > 65535)) {
            throw new InvalidArgumentException('Enter a valid publication year.');
        }

        $isbn = trim((string) ($_POST['isbn'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? ''));
        return [
            'isbn' => $isbn === '' ? null : $isbn,
            'title' => $title,
            'author' => $author,
            'category' => $category === '' ? null : $category,
            'published_year' => $year,
            'total_copies' => $copies,
        ];
    }

    private function emptyBook(): array
    {
        return [
            'id' => null, 'isbn' => '', 'title' => '', 'author' => '', 'category' => '',
            'published_year' => '', 'total_copies' => 1,
        ];
    }
}