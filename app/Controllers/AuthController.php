<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use PDOException;

final class AuthController extends Controller
{
	public function loginForm(): void
	{
		if (isset($_SESSION['user_id'])) {
			$this->redirect('/');
		}
		$this->view('auth/login', ['title' => 'Sign in']);
	}

	public function login(): void
	{
		$this->requireValidCsrfToken();
		$emailInput = $_POST['email'] ?? null;
		$passwordInput = $_POST['password'] ?? null;
		$email = is_string($emailInput) ? mb_strtolower(trim($emailInput)) : '';
		$password = is_string($passwordInput) ? $passwordInput : '';

		if (
			$email === ''
			|| !$this->isValidEmail($email)
			|| $password === ''
			|| strlen($password) > 4096
		) {
			$this->redirectWithAuthError('/login', 'Enter a valid email address and password.', ['email' => $email]);
		}

		$user = (new User())->findByEmail($email);

		if ($user === null || $user['status'] !== 'active' || !is_string($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
			$this->redirectWithAuthError('/login', 'Email or password is incorrect.', ['email' => $email]);
		}

		session_regenerate_id(true);
		$_SESSION['user_id'] = (int) $user['id'];
		$_SESSION['user_name'] = $user['name'];
		$_SESSION['role'] = $user['role'];
		$this->redirect('/');
	}

	public function registerForm(): void
	{
		if (isset($_SESSION['user_id'])) {
			$this->redirect('/');
		}
		$this->view('auth/register', ['title' => 'Create account']);
	}

	public function register(): void
	{
		$this->requireValidCsrfToken();
		$nameInput = $_POST['name'] ?? null;
		$emailInput = $_POST['email'] ?? null;
		$passwordInput = $_POST['password'] ?? null;
		$confirmationInput = $_POST['password_confirmation'] ?? null;
		$name = is_string($nameInput) ? trim($nameInput) : '';
		$email = is_string($emailInput) ? mb_strtolower(trim($emailInput)) : '';
		$password = is_string($passwordInput) ? $passwordInput : '';
		$confirmation = is_string($confirmationInput) ? $confirmationInput : '';
		$oldInput = ['name' => $name, 'email' => $email];

		if (
			$name === ''
			|| !mb_check_encoding($name, 'UTF-8')
			|| mb_strlen($name) > 150
			|| preg_match('/[\x00-\x1F\x7F]/u', $name) === 1
		) {
			$this->redirectWithAuthError('/register', 'Enter a name using 150 characters or fewer, without control characters.', $oldInput);
		}
		if ($email === '' || !$this->isValidEmail($email)) {
			$this->redirectWithAuthError('/register', 'Enter a valid email address with a fully qualified domain, such as name@example.com.', $oldInput);
		}
		if (strlen($password) < 8 || strlen($password) > 72 || $password !== $confirmation) {
			$this->redirectWithAuthError('/register', 'Passwords must match and contain between 8 and 72 bytes.', $oldInput);
		}

		$users = new User();
		$role = $users->count() === 0 ? 'admin' : 'member';

		try {
			$userId = $users->register($name, $email, $password, $role);
		} catch (PDOException $exception) {
			if ($exception->getCode() !== '23000') {
				throw $exception;
			}
			$this->redirectWithAuthError('/register', 'An account with that email address already exists.', $oldInput);
		}

		session_regenerate_id(true);
		$_SESSION['user_id'] = $userId;
		$_SESSION['user_name'] = $name;
		$_SESSION['role'] = $role;
		$this->redirect('/');
	}

	public function logout(): void
	{
		$this->requireValidCsrfToken();
		$_SESSION = [];
		session_regenerate_id(true);
		$this->redirect('/login');
	}

	private function redirectWithAuthError(string $path, string $message, array $oldInput): never
	{
		$_SESSION['_auth_old'] = $oldInput;
		$this->flash('error', $message);
		$this->redirect($path);
	}

	private function isValidEmail(string $email): bool
	{
		if (
			mb_strlen($email) > 190
			|| filter_var($email, FILTER_VALIDATE_EMAIL) === false
		) {
			return false;
		}

		$separator = strrpos($email, '@');
		if ($separator === false) {
			return false;
		}

		$localPart = substr($email, 0, $separator);
		$domain = substr($email, $separator + 1);
		if (strlen($localPart) > 64 || strlen($domain) > 253) {
			return false;
		}

		$labels = explode('.', $domain);
		if (count($labels) < 2) {
			return false;
		}

		foreach ($labels as $label) {
			if (
				$label === ''
				|| strlen($label) > 63
				|| preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/iD', $label) !== 1
			) {
				return false;
			}
		}

		$topLevelDomain = end($labels);
		return strlen($topLevelDomain) >= 2 && preg_match('/[a-z]/i', $topLevelDomain) === 1;
	}
}
