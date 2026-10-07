<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use InvalidArgumentException;
use PDO;
use PDOException;

final class BookRequest extends Model
{
    public function create(int $userId, int $bookId): bool
    {
        $reopen = $this->database->prepare(
            "UPDATE book_requests SET status = 'pending', created_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id AND book_id = :book_id AND status = 'cancelled'
               AND EXISTS (SELECT 1 FROM users WHERE users.id = book_requests.user_id
                           AND users.role = 'member' AND users.status = 'active')"
        );
        $reopen->execute(['user_id' => $userId, 'book_id' => $bookId]);
        if ($reopen->rowCount() === 1) {
            return true;
        }
        $statement = $this->database->prepare(
            "INSERT INTO book_requests (user_id, book_id)
             SELECT users.id, books.id FROM users CROSS JOIN books
             WHERE users.id = :user_id AND users.role = 'member' AND users.status = 'active'
               AND books.id = :book_id"
        );
        try {
            $statement->execute(['user_id' => $userId, 'book_id' => $bookId]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000' && in_array($bookId, $this->requestedBookIds($userId), true)) {
                return false;
            }
            throw $exception;
        }
        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException('The book or your active member account could not be found.');
        }
        return true;
    }

    public function cancel(int $userId, int $bookId): bool
    {
        $statement = $this->database->prepare(
            "DELETE FROM book_requests WHERE user_id = :user_id AND book_id = :book_id AND status = 'pending'"
        );
        $statement->execute(['user_id' => $userId, 'book_id' => $bookId]);
        return $statement->rowCount() === 1;
    }

    public function requestedBookIds(int $userId): array
    {
        $statement = $this->database->prepare('SELECT book_id FROM book_requests WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function memberStatuses(int $userId): array
    {
        $statement = $this->database->prepare('SELECT book_id, status FROM book_requests WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function decide(int $id, string $status): bool
    {
        if (!in_array($status, ['granted', 'cancelled'], true)) {
            throw new InvalidArgumentException('Choose Granted or Cancel for the request.');
        }
        $statement = $this->database->prepare(
            "UPDATE book_requests SET status = :status WHERE id = :id AND status = 'pending'"
        );
        $statement->execute(['status' => $status, 'id' => $id]);
        return $statement->rowCount() === 1;
    }

    public function all(): array
    {
        return $this->database->query(
            'SELECT book_requests.id, book_requests.created_at, book_requests.status, users.name AS user_name,
                    users.email AS user_email, books.title AS book_title, books.author
             FROM book_requests
             INNER JOIN users ON users.id = book_requests.user_id
             INNER JOIN books ON books.id = book_requests.book_id
             ORDER BY book_requests.created_at DESC, book_requests.id DESC'
        )->fetchAll();
    }
}
