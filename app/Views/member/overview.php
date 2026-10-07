<section class="page-heading member-page-heading">
    <div>
        <p class="eyebrow">MY LIBRARY</p>
        <h1>Overview</h1>
        <p class="page-description">Your borrowed books and due dates.</p>
        <p class="member-greeting">Welcome back, <?= e($firstName) ?></p>
    </div>
    <span class="date-stamp"><?= date('l, F j, Y') ?></span>
</section>

<section class="metric-grid" aria-label="Your borrowing summary">
    <?php
    $statIcon = 'borrow';
    $statLabel = 'CURRENTLY BORROWED';
    $statValue = $stats['currently_borrowed'];
    $statTone = 'green';
    require __DIR__ . '/partials/_stat-card.php';
    $statIcon = 'clock';
    $statLabel = 'DUE SOON';
    $statValue = $stats['due_soon'];
    $statTone = 'yellow';
    require __DIR__ . '/partials/_stat-card.php';
    $statIcon = 'alert';
    $statLabel = 'OVERDUE';
    $statValue = $stats['overdue'];
    $statTone = (int) $stats['overdue'] > 0 ? 'coral' : 'blue';
    require __DIR__ . '/partials/_stat-card.php';
    $statIcon = 'books';
    $statLabel = 'TOTAL BORROWED';
    $statValue = $stats['total_borrowed'];
    $statTone = 'blue';
    require __DIR__ . '/partials/_stat-card.php';
    ?>
</section>

<section class="section-heading">
    <div>
        <p class="eyebrow">YOUR BOOKS</p>
        <h2>Currently borrowed</h2>
    </div>
    <a class="member-inline-link" href="<?= e(BASE_PATH) ?>/my-borrows">View all</a>
</section>
<?php if ($activeBorrows === []): ?>
    <div class="member-empty-state">
        <p>You haven't borrowed anything yet.</p>
        <a class="button button-primary" href="<?= e(BASE_PATH) ?>/browse">Browse books</a>
    </div>
<?php else: ?>
    <section class="table-panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Book</th><th>Author</th><th>Borrowed</th><th>Due date</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($activeBorrows, 0, 5) as $borrow): ?>
                    <?php
                    if ((int) $borrow['days_overdue'] > 0) {
                        $badgeStatus = 'overdue';
                        $badgeLabel = 'Overdue by ' . (int) $borrow['days_overdue'] . ' days';
                    } elseif ((int) $borrow['days_left'] <= 3) {
                        $badgeStatus = 'due-soon';
                        $badgeLabel = (int) $borrow['days_left'] . ' days left';
                    } else {
                        $badgeStatus = 'borrowed';
                        $badgeLabel = (int) $borrow['days_left'] . ' days left';
                    }
                    ?>
                    <tr class="<?= $badgeStatus === 'overdue' ? 'overdue-row' : '' ?>">
                        <td class="primary-cell"><?= e($borrow['book_title']) ?></td>
                        <td><?= e($borrow['author']) ?></td>
                        <td><?= e($borrow['borrowed_at']) ?></td>
                        <td><?= e($borrow['due_at']) ?></td>
                        <td><?php require __DIR__ . '/partials/_status-badge.php'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<section class="section-heading">
    <div>
        <p class="eyebrow">FIND YOUR NEXT READ</p>
        <h2>Available to borrow</h2>
    </div>
    <a class="member-inline-link" href="<?= e(BASE_PATH) ?>/browse">View all</a>
</section>
<?php if ($availableBooks === []): ?>
    <div class="member-empty-state"><p>There are no available books right now. Please check back soon.</p></div>
<?php else: ?>
    <section class="member-book-grid" aria-label="Available books">
        <?php foreach ($availableBooks as $bookCard): ?>
            <?php require __DIR__ . '/partials/_book-card.php'; ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
