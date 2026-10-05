<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use DateTimeImmutable;
use InvalidArgumentException;

final class Borrow extends Model
{
    public function memberStats(int $userId): array
    {
        $statement = $this->database->prepare(
            "SELECT
                COUNT(*) AS total_borrowed,
                COALESCE(SUM(CASE WHEN returned_at IS NULL THEN 1 ELSE 0 END), 0) AS currently_borrowed,
                COALESCE(SUM(CASE WHEN returned_at IS NULL
                                       AND due_at BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY)
                                  THEN 1 ELSE 0 END), 0) AS due_soon,
                COALESCE(SUM(CASE WHEN returned_at IS NULL AND due_at < CURRENT_DATE
                                  THEN 1 ELSE 0 END), 0) AS overdue
             FROM loans
             WHERE user_id = :user_id"
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetch() ?: [
            'total_borrowed' => 0,
            'currently_borrowed' => 0,
            'due_soon' => 0,
            'overdue' => 0,
        ];
    }

    public function memberActive(int $userId): array
    {
        $statement = $this->database->prepare(
            "SELECT books.title AS book_title, books.author, loans.borrowed_at, loans.due_at,
                    CASE WHEN loans.due_at < CURRENT_DATE THEN 'overdue' ELSE 'borrowed' END AS display_status,
                    DATEDIFF(loans.due_at, CURRENT_DATE) AS days_left,
                    DATEDIFF(CURRENT_DATE, loans.due_at) AS days_overdue
             FROM loans
             INNER JOIN books ON books.id = loans.book_id
             WHERE loans.user_id = :user_id AND loans.returned_at IS NULL
             ORDER BY loans.due_at ASC, loans.id DESC"
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function memberHistory(int $userId, int $limit, int $offset): array
    {
        $statement = $this->database->prepare(
            "SELECT books.title AS book_title, books.author, loans.borrowed_at, loans.due_at,
                    loans.returned_at,
                    CASE WHEN loans.returned_at IS NOT NULL THEN 'returned'
                         WHEN loans.due_at < CURRENT_DATE THEN 'overdue'
                         ELSE 'borrowed' END AS display_status
             FROM loans
             INNER JOIN books ON books.id = loans.book_id
             WHERE loans.user_id = :user_id
             ORDER BY loans.borrowed_at DESC, loans.id DESC
             LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function memberHistoryCount(int $userId): int
    {
        $statement = $this->database->prepare('SELECT COUNT(*) FROM loans WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public function all(): array
    {
        $sql = "SELECT loans.id, users.name AS user_name, books.title AS book_title,
                       loans.borrowed_at, loans.due_at, loans.returned_at,
                       CASE WHEN loans.status IN ('active', 'overdue') AND loans.due_at < CURRENT_DATE
                            THEN 'overdue' ELSE loans.status END AS status
                FROM loans
                INNER JOIN users ON users.id = loans.user_id
                INNER JOIN books ON books.id = loans.book_id
                ORDER BY loans.borrowed_at DESC, loans.id DESC";

        return $this->database->query($sql)->fetchAll();
    }

    public function history(): array
    {
        $statement = $this->database->query(
            "SELECT loans.id, users.name AS user_name, books.title AS book_title,
                    loans.borrowed_at, loans.due_at, loans.returned_at, loans.status
             FROM loans
             INNER JOIN users ON users.id = loans.user_id
             INNER JOIN books ON books.id = loans.book_id
             WHERE loans.status = 'returned'
             ORDER BY loans.returned_at DESC, loans.id DESC"
        );

        return $statement->fetchAll();
    }

    public function members(): array
    {
        return $this->database->query(
            "SELECT id, name, email FROM users WHERE status = 'active' AND role = 'member' ORDER BY name"
        )->fetchAll();
    }

    public function availableBooks(): array
    {
        return $this->database->query(
            'SELECT id, title, author, available_copies FROM books WHERE available_copies > 0 ORDER BY title'
        )->fetchAll();
    }

    public function checkout(int $userId, int $bookId, string $dueAt): void
    {
        $dueDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dueAt);
        if ($userId < 1 || $bookId < 1 || !$dueDate || $dueDate->format('Y-m-d') !== $dueAt || $dueAt < date('Y-m-d')) {
            throw new InvalidArgumentException('Choose a member, an available book, and a valid due date.');
        }

        $this->database->beginTransaction();
        try {
            $member = $this->database->prepare(
                "SELECT id FROM users WHERE id = :id AND status = 'active' AND role = 'member' FOR UPDATE"
            );
            $member->execute(['id' => $userId]);
            if (!$member->fetch()) {
                throw new InvalidArgumentException('Choose an active library member.');
            }

            $book = $this->database->prepare(
                'UPDATE books SET available_copies = available_copies - 1
                 WHERE id = :id AND available_copies > 0'
            );
            $book->execute(['id' => $bookId]);
            if ($book->rowCount() !== 1) {
                throw new InvalidArgumentException('That book has no available copies.');
            }

            $loan = $this->database->prepare(
                "INSERT INTO loans (user_id, book_id, borrowed_at, due_at, status)
                 VALUES (:user_id, :book_id, CURRENT_DATE, :due_at, 'active')"
            );
            $loan->execute(['user_id' => $userId, 'book_id' => $bookId, 'due_at' => $dueAt]);
            $this->database->commit();
        } catch (\Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    public function returnBook(int $loanId): void
    {
        if ($loanId < 1) {
            throw new InvalidArgumentException('Choose a valid loan.');
        }

        $this->database->beginTransaction();
        try {
            $loan = $this->database->prepare(
                "UPDATE loans SET returned_at = CURRENT_DATE, status = 'returned'
                 WHERE id = :id AND status IN ('active', 'overdue')"
            );
            $loan->execute(['id' => $loanId]);
            if ($loan->rowCount() !== 1) {
                throw new InvalidArgumentException('This loan has already been returned.');
            }

            $bookIdStatement = $this->database->prepare('SELECT book_id FROM loans WHERE id = :id');
            $bookIdStatement->execute(['id' => $loanId]);
            $bookId = (int) $bookIdStatement->fetchColumn();

            $stock = $this->database->prepare(
                'UPDATE books SET available_copies = LEAST(total_copies, available_copies + 1) WHERE id = :id'
            );
            $stock->execute(['id' => $bookId]);
            $this->database->commit();
        } catch (\Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

}