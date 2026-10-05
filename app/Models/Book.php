<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use InvalidArgumentException;
use PDO;

final class Book extends Model
{
    public function browseForMembers(
        string $search,
        string $category,
        bool $availableOnly,
        int $limit,
        int $offset
    ): array {
        [$where, $parameters] = $this->memberBrowseConditions($search, $category, $availableOnly);
        $statement = $this->database->prepare(
            'SELECT id, title, author, category, total_copies, available_copies
             FROM books' . $where . '
             ORDER BY title, id
             LIMIT :limit OFFSET :offset'
        );
        foreach ($parameters as $name => $value) {
            $statement->bindValue(':' . $name, $value, \PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function memberBrowseCount(string $search, string $category, bool $availableOnly): int
    {
        [$where, $parameters] = $this->memberBrowseConditions($search, $category, $availableOnly);
        $statement = $this->database->prepare('SELECT COUNT(*) FROM books' . $where);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn();
    }

    public function memberCategories(): array
    {
        return $this->database->query(
            "SELECT DISTINCT category FROM books
             WHERE category IS NOT NULL AND category <> ''
             ORDER BY category"
        )->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function availableForMembers(int $limit): array
    {
        $statement = $this->database->prepare(
            'SELECT id, title, author, category, total_copies, available_copies
             FROM books
             WHERE available_copies > 0
             ORDER BY title, id
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function memberBrowseConditions(string $search, string $category, bool $availableOnly): array
    {
        $filters = [];
        $parameters = [];

        if ($search !== '') {
            $filters[] = '(title LIKE :search_title OR author LIKE :search_author)';
            $parameters['search_title'] = '%' . $search . '%';
            $parameters['search_author'] = '%' . $search . '%';
        }
        if ($category !== '') {
            $filters[] = 'category = :category';
            $parameters['category'] = $category;
        }
        if ($availableOnly) {
            $filters[] = 'available_copies > 0';
        }

        return [$filters === [] ? '' : ' WHERE ' . implode(' AND ', $filters), $parameters];
    }

    public function all(): array
    {
        return $this->database->query(
            'SELECT id, isbn, title, author, category, published_year, total_copies, available_copies
             FROM books ORDER BY title'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->database->prepare('SELECT * FROM books WHERE id = :id');
        $statement->execute(['id' => $id]);
        $book = $statement->fetch();

        return $book ?: null;
    }

    public function create(array $data): void
    {
        $data['available_copies'] = $data['total_copies'];
        $statement = $this->database->prepare(
            'INSERT INTO books (isbn, title, author, category, published_year, total_copies, available_copies)
             VALUES (:isbn, :title, :author, :category, :published_year, :total_copies, :available_copies)'
        );
        $statement->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $this->database->beginTransaction();

        try {
            $activeStatement = $this->database->prepare(
                "SELECT id FROM loans WHERE book_id = :id AND status IN ('active', 'overdue') FOR UPDATE"
            );
            $activeStatement->execute(['id' => $id]);
            $borrowedCopies = count($activeStatement->fetchAll());

            if ($data['total_copies'] < $borrowedCopies) {
                throw new InvalidArgumentException('Total copies cannot be less than the number currently borrowed.');
            }

            $data['available_copies'] = $data['total_copies'] - $borrowedCopies;
            $data['id'] = $id;
            $statement = $this->database->prepare(
                'UPDATE books SET isbn = :isbn, title = :title, author = :author,
                    category = :category, published_year = :published_year,
                    total_copies = :total_copies, available_copies = :available_copies
                 WHERE id = :id'
            );
            $statement->execute($data);
            $this->database->commit();
        } catch (\Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $statement = $this->database->prepare('DELETE FROM books WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}