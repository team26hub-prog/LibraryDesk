<section class="page-heading">
    <div>
        <p class="eyebrow">LIBRARY OPERATIONS</p>
        <h1>Overview</h1>
        <p class="page-description">A clear view of your collection and circulation.</p>
    </div>
    <span class="date-stamp"><?= date('l, F j, Y') ?></span>
</section>

<section class="metric-grid" aria-label="Library summary">
    <article class="metric-panel metric-blue">
        <span class="metric-icon"><?= icon('users') ?></span>
        <div class="metric-copy">
            <p class="metric-value"><?= (int) ($stats['user_count'] ?? 0) ?></p>
            <p class="metric-label">Registered users</p>
        </div>
    </article>
    <article class="metric-panel metric-green">
        <span class="metric-icon"><?= icon('books') ?></span>
        <div class="metric-copy">
            <p class="metric-value"><?= (int) ($stats['book_count'] ?? 0) ?></p>
            <p class="metric-label">Book titles</p>
        </div>
    </article>
    <article class="metric-panel metric-yellow">
        <span class="metric-icon"><?= icon('checkout') ?></span>
        <div class="metric-copy">
            <p class="metric-value"><?= (int) ($stats['active_count'] ?? 0) ?></p>
            <p class="metric-label">Active borrows</p>
        </div>
    </article>
    <article class="metric-panel metric-coral">
        <span class="metric-icon"><?= icon('clock') ?></span>
        <div class="metric-copy">
            <p class="metric-value"><?= (int) ($stats['overdue_count'] ?? 0) ?></p>
            <p class="metric-label">Overdue returns</p>
        </div>
    </article>
</section>

<?php if (in_array($_SESSION['role'] ?? '', ['admin', 'librarian'], true)): ?>
<section class="section-heading">
    <div>
        <p class="eyebrow">GET STARTED</p>
        <h2>Manage your library</h2>
    </div>
</section>
<section class="module-grid">
    <a class="module-link" href="<?= htmlspecialchars(BASE_PATH, ENT_QUOTES, 'UTF-8') ?>/users">
        <span class="module-number">01</span><span class="module-symbol coral-symbol"><?= icon('users') ?></span>
        <span class="module-copy"><strong>User management</strong><small>Member records and account status</small></span><span class="module-arrow"><?= icon('arrow-right') ?></span>
    </a>
    <a class="module-link" href="<?= htmlspecialchars(BASE_PATH, ENT_QUOTES, 'UTF-8') ?>/books">
        <span class="module-number">02</span><span class="module-symbol green-symbol"><?= icon('books') ?></span>
        <span class="module-copy"><strong>Book management</strong><small>Catalogue, copies, and availability</small></span><span class="module-arrow"><?= icon('arrow-right') ?></span>
    </a>
    <a class="module-link" href="<?= htmlspecialchars(BASE_PATH, ENT_QUOTES, 'UTF-8') ?>/borrow">
        <span class="module-number">03</span><span class="module-symbol yellow-symbol"><?= icon('borrow') ?></span>
        <span class="module-copy"><strong>Borrow &amp; return</strong><small>Circulation and due dates</small></span><span class="module-arrow"><?= icon('arrow-right') ?></span>
    </a>
</section>
<?php endif; ?>
