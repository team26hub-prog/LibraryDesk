<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use InvalidArgumentException;

final class User extends Model
{
    public function all(): array
    {
        return $this->database->query(
            'SELECT id, name, email, phone, role, status, created_at FROM users ORDER BY name'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, name, email, phone, role, status FROM users WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, name, email, password_hash, role, status FROM users WHERE email = :email'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function count(): int
    {
        return (int) $this->database->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function register(string $name, string $email, string $password, string $role): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO users (name, email, password_hash, role, status)
             VALUES (:name, :email, :password_hash, :role, \'active\')'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function create(array $data): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO users (name, email, phone, role, status)
             VALUES (:name, :email, :phone, :role, :status)'
        );
        $statement->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $this->requireUnprotectedUser($id);
        $data['id'] = $id;
        $statement = $this->database->prepare(
            'UPDATE users SET name = :name, email = :email, phone = :phone,
                    role = :role, status = :status WHERE id = :id AND role <> \'admin\''
        );
        $statement->execute($data);
    }

    public function updateMemberName(int $id, string $name): void
    {
        $statement = $this->database->prepare('UPDATE users SET name = :name WHERE id = :id');
        $statement->execute(['name' => $name, 'id' => $id]);
    }

    public function changeMemberPassword(int $id, string $currentPassword, string $newPassword): void
    {
        $statement = $this->database->prepare(
            'SELECT password_hash FROM users WHERE id = :id AND role = \'member\''
        );
        $statement->execute(['id' => $id]);
        $passwordHash = $statement->fetchColumn();

        if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
            throw new InvalidArgumentException('Your current password is incorrect.');
        }

        $update = $this->database->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id AND role = \'member\''
        );
        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->requireUnprotectedUser($id);
        $statement = $this->database->prepare('DELETE FROM users WHERE id = :id AND role <> \'admin\'');
        $statement->execute(['id' => $id]);
    }

    private function requireUnprotectedUser(int $id): void
    {
        $user = $this->find($id);
        if ($user === null) {
            throw new InvalidArgumentException('That user could not be found.');
        }
        if ($user['role'] === 'admin') {
            throw new InvalidArgumentException('Admin accounts are protected and cannot be edited or deleted.');
        }
    }
}
