<?php

declare(strict_types=1);

use App\Controllers\BookController;
use App\Controllers\BorrowController;
use App\Controllers\DashboardController;
use App\Controllers\AuthController;
use App\Controllers\MemberController;
use App\Controllers\UserController;

$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/', [DashboardController::class, 'index']);

$router->get('/browse', [MemberController::class, 'browse']);
$router->get('/my-borrows', [MemberController::class, 'borrows']);
$router->get('/history', [MemberController::class, 'history']);
$router->get('/profile', [MemberController::class, 'profile']);
$router->post('/profile/name', [MemberController::class, 'updateName']);
$router->post('/profile/password', [MemberController::class, 'updatePassword']);

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/create', [UserController::class, 'create']);
$router->get('/users/edit', [UserController::class, 'edit']);
$router->post('/users/store', [UserController::class, 'store']);
$router->post('/users/update', [UserController::class, 'update']);
$router->post('/users/delete', [UserController::class, 'delete']);

$router->get('/books', [BookController::class, 'index']);
$router->get('/books/create', [BookController::class, 'create']);
$router->get('/books/edit', [BookController::class, 'edit']);
$router->post('/books/store', [BookController::class, 'store']);
$router->post('/books/update', [BookController::class, 'update']);
$router->post('/books/delete', [BookController::class, 'delete']);

$router->get('/borrow', [BorrowController::class, 'index']);
$router->get('/borrow/history', [BorrowController::class, 'history']);
$router->post('/borrow/checkout', [BorrowController::class, 'checkout']);
$router->post('/borrow/return', [BorrowController::class, 'returnBook']);
