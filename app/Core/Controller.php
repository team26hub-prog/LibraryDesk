<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = dirname(__DIR__) . '/Views/' . $template . '.php';

        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }

        require dirname(__DIR__) . '/Views/layouts/header.php';
        require $viewPath;
        require dirname(__DIR__) . '/Views/layouts/footer.php';
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . BASE_PATH . $path, true, 303);
        exit;
    }

    protected function flash(string $type, string $message, ?string $title = null): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message, 'title' => $title];
    }

    protected function requireValidCsrfToken(): void
    {
        $submittedToken = $_POST['_csrf'] ?? '';
        $sessionToken = $_SESSION['_csrf'] ?? '';

        if (!is_string($submittedToken) || !is_string($sessionToken) || !hash_equals($sessionToken, $submittedToken)) {
            http_response_code(419);
            $this->flash('error', 'Refresh the form and try again. Your changes were not submitted.', 'Form expired');
            $this->view('errors/expired', ['title' => 'Form expired']);
            exit;
        }
    }

    protected function requireRole(string ...$roles): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
            $this->flash('error', 'You do not have permission to access that page.');
            $this->redirect('/');
        }
    }
}
