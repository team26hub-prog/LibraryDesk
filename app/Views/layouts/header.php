<?php
$currentPath = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$flash = $_SESSION['flash'] ?? null;
$role = $_SESSION['role'] ?? null;
$userName = $_SESSION['user_name'] ?? '';
unset($_SESSION['flash']);
$quickLinks = [];

if ($role === 'member') {
    $quickLinks = [
        ['path' => '/browse', 'icon' => '▤', 'label' => 'Browse books'],
        ['path' => '/my-borrows', 'icon' => '↔', 'label' => 'My borrows'],
        ['path' => '/history', 'icon' => '◷', 'label' => 'History'],
        ['path' => '/profile', 'icon' => '♙', 'label' => 'Profile'],
    ];
} elseif ($role === 'admin') {
    $quickLinks = [
        ['path' => '/users', 'icon' => '♙', 'label' => 'Users'],
        ['path' => '/books', 'icon' => '▤', 'label' => 'Books'],
        ['path' => '/borrow', 'icon' => '↔', 'label' => 'Borrow & return'],
    ];
} elseif ($role === 'librarian') {
    $quickLinks = [
        ['path' => '/books', 'icon' => '▤', 'label' => 'Books'],
        ['path' => '/borrow', 'icon' => '↔', 'label' => 'Borrow & return'],
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape($title ?? 'Library Desk') ?> | Library Desk</title>
    <link rel="stylesheet" href="<?= $escape(BASE_PATH) ?>/public/assets/css/app.css">
</head>
<body>
<div class="app-shell <?= isset($_SESSION['user_id']) && $quickLinks !== [] ? 'has-mobile-quick-nav' : '' ?>">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a class="brand" href="<?= $escape(BASE_PATH) ?>/" aria-label="Library Desk home">
                <span class="brand-mark">L</span>
                <span>Library<span class="brand-light">Desk</span></span>
            </a>
            <button class="mobile-nav-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="primary-navigation" data-nav-toggle>
                <span></span><span></span><span></span>
            </button>
        </div>
        <p class="nav-label">WORKSPACE</p>
        <nav class="primary-nav" id="primary-navigation" aria-label="Main navigation">
            <a class="nav-link <?= $currentPath === BASE_PATH . '/' || $currentPath === BASE_PATH ? 'is-active' : '' ?>" href="<?= $escape(BASE_PATH) ?>/">
                <span class="nav-icon">⌂</span> Overview
            </a>
            <?php if ($role === 'member'): ?>
                <a class="nav-link <?= $currentPath === BASE_PATH . '/browse' ? 'is-active' : '' ?>" href="<?= $escape(BASE_PATH) ?>/browse">
                    <span class="nav-icon">▤</span> Browse books
                </a>
                <a class="nav-link <?= $currentPath === BASE_PATH . '/my-borrows' ? 'is-active' : '' ?>" href="<?= $escape(BASE_PATH) ?>/my-borrows">
                    <span class="nav-icon">↔</span> My borrows
                </a>
                <a class="nav-link <?= $currentPath === BASE_PATH . '/history' ? 'is-active' : '' ?>" href="<?= $escape(BASE_PATH) ?>/history">
                    <span class="nav-icon">◷</span> History
                </a>
                <a class="nav-link <?= $currentPath === BASE_PATH . '/profile' ? 'is-active' : '' ?>" href="<?= $escape(BASE_PATH) ?>/profile">
                    <span class="nav-icon">♙</span> Profile
                </a>
            <?php else: ?>
            <?php if ($role === 'admin'): ?>
                <a class="nav-link <?= str_contains((string) $currentPath, '/users') ? 'is-active' : '' ?>" href="<?= $escape(BASE_PATH) ?>/users">
                    <span class="nav-icon">♙</span> Users
                </a>
            <?php endif; ?>
            <?php if (in_array($role, ['admin', 'librarian'], true)): ?>
                <a class="nav-link <?= str_contains((string) $currentPath, '/books') ? 'is-active' : '' ?>" href="<?= $escape(BASE_PATH) ?>/books">
                    <span class="nav-icon">▤</span> Books
                </a>
                <a class="nav-link <?= str_contains((string) $currentPath, '/borrow') ? 'is-active' : '' ?>" href="<?= $escape(BASE_PATH) ?>/borrow">
                    <span class="nav-icon">↔</span> Borrow &amp; return
                </a>
            <?php endif; ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form class="mobile-nav-signout" method="post" action="<?= $escape(BASE_PATH) ?>/logout">
                    <?= csrf_field() ?>
                    <button class="mobile-nav-signout-button" type="submit">Sign out</button>
                </form>
            <?php endif; ?>
        </nav>
        <div class="sidebar-bottom">
            <span class="status-dot"></span>
            <span>Library workspace</span>
        </div>
    </aside>
    <?php if (isset($_SESSION['user_id']) && $quickLinks !== []): ?>
        <nav class="mobile-quick-nav" aria-label="Quick actions">
            <?php foreach ($quickLinks as $link): ?>
                <?php
                $linkPath = BASE_PATH . $link['path'];
                $isActive = $link['path'] === '/books' || $link['path'] === '/borrow'
                    ? str_contains($currentPath, $link['path'])
                    : $currentPath === $linkPath;
                ?>
                <a class="mobile-quick-link <?= $isActive ? 'is-active' : '' ?>"
                   href="<?= $escape($linkPath) ?>"
                   <?= $isActive ? 'aria-current="page"' : '' ?>>
                    <span class="mobile-quick-icon" aria-hidden="true"><?= $escape($link['icon']) ?></span>
                    <span><?= $escape($link['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="breadcrumb">LIBRARY <span>/</span> <?= $escape(strtoupper($title ?? 'Overview')) ?></div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="topbar-user"><span class="avatar"><?= $escape(strtoupper(substr($userName, 0, 1))) ?></span><span><?= $escape($userName) ?></span>
                    <form class="topbar-signout" method="post" action="<?= $escape(BASE_PATH) ?>/logout"><?= csrf_field() ?><button class="text-button" type="submit">Sign out</button></form>
                </div>
            <?php else: ?>
                <a class="topbar-user" href="<?= $escape(BASE_PATH) ?>/login">Sign in</a>
            <?php endif; ?>
        </header>
        <div class="page-content">
            <?php if (is_array($flash)): ?>
                <div class="notice notice-<?= $escape($flash['type'] ?? 'success') ?>" role="status"><?= $escape($flash['message'] ?? '') ?></div>
            <?php endif; ?>