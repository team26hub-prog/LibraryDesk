<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

final class UserController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin');
        $this->view('users/index', [
            'title' => 'User management',
            'users' => (new User())->all(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole('admin');
        $this->view('users/create', ['title' => 'Add user', 'user' => $this->emptyUser()]);
    }

    public function edit(): void
    {
        $this->requireRole('admin');
        $user = (new User())->find((int) ($_GET['id'] ?? 0));
        if ($user === null) {
            $this->flash('error', 'That user could not be found.');
            $this->redirect('/users');
        }
        if ($user['role'] === 'admin') {
            $this->flash('error', 'Admin accounts are protected and cannot be edited or deleted.');
            $this->redirect('/users');
        }

        $this->view('users/edit', ['title' => 'Edit user', 'user' => $user]);
    }

    public function store(): void
    {
        $this->requireRole('admin');
        $this->requireValidCsrfToken();
        try {
            (new User())->create($this->validatedUser());
            $this->flash('success', 'User added.');
        } catch (\InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $this->flash('error', 'Could not add the user. Check that the email address is not already in use.');
        }
        $this->redirect('/users');
    }

    public function update(): void
    {
        $this->requireRole('admin');
        $this->requireValidCsrfToken();
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            $this->flash('error', 'Invalid user record.');
            $this->redirect('/users');
        }

        try {
            (new User())->update((int) $id, $this->validatedUser());
            $this->flash('success', 'User updated.');
        } catch (\InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $this->flash('error', 'Could not update the user. Check that the email address is not already in use.');
        }
        $this->redirect('/users');
    }

    public function delete(): void
    {
        $this->requireRole('admin');
        $this->requireValidCsrfToken();
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        try {
            if (!$id) {
                throw new \InvalidArgumentException('Invalid user record.');
            }
            (new User())->delete((int) $id);
            $this->flash('success', 'User deleted.');
        } catch (\InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $this->flash('error', 'Could not delete this user. A user with borrow history or book requests cannot be deleted.');
        }
        $this->redirect('/users');
    }

    private function validatedUser(): array
    {
        $submittedName = $_POST['name'] ?? null;
        $name = is_string($submittedName) ? trim($submittedName) : '';
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = (string) ($_POST['role'] ?? 'member');
        $status = (string) ($_POST['status'] ?? 'active');

        if (!valid_full_name($name)) {
            throw new \InvalidArgumentException('Enter a full name using letters, spaces, apostrophes, or hyphens, up to 150 characters.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            throw new \InvalidArgumentException('Enter a valid email address.');
        }
        if (!in_array($role, ['member', 'librarian', 'admin'], true) || !in_array($status, ['active', 'inactive'], true)) {
            throw new \InvalidArgumentException('Choose a valid role and account status.');
        }

        $phone = trim((string) ($_POST['phone'] ?? ''));
        return [
            'name' => $name,
            'email' => $email,
            'phone' => $phone === '' ? null : $phone,
            'role' => $role,
            'status' => $status,
        ];
    }

    private function emptyUser(): array
    {
        return ['id' => null, 'name' => '', 'email' => '', 'phone' => '', 'role' => 'member', 'status' => 'active'];
    }
}
