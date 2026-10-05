<section class="page-heading">
    <div><p class="eyebrow">MY LIBRARY</p><h1>Borrow history</h1><p class="page-description">A record of the books you have borrowed.</p></div>
</section>
<section class="table-panel">
    <div class="table-toolbar"><div><h2>All borrows</h2><p><?= (int) $pagination['total'] ?> records</p></div></div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Book</th><th>Author</th><th>Borrowed date</th><th>Due date</th><th>Returned date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($borrows as $borrow): ?>
                <tr class="<?= $borrow['display_status'] === 'overdue' ? 'overdue-row' : '' ?>">
                    <td class="primary-cell"><?= e($borrow['book_title']) ?></td>
                    <td><?= e($borrow['author']) ?></td>
                    <td><?= e($borrow['borrowed_at']) ?></td>
                    <td><?= e($borrow['due_at']) ?></td>
                    <td><?= e($borrow['returned_at'] ?? '—') ?></td>
                    <td><?php $badgeStatus = $borrow['display_status']; $badgeLabel = ucfirst($badgeStatus); require __DIR__ . '/partials/_status-badge.php'; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($borrows === []): ?><tr class="empty-row"><td colspan="6">You don't have any borrow history yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php if ($pagination['pageCount'] > 1): ?>
    <nav class="pagination" aria-label="Borrow history pages">
        <?php for ($pageNumber = 1; $pageNumber <= $pagination['pageCount']; $pageNumber++): ?>
            <a class="pagination-link <?= $pageNumber === $pagination['page'] ? 'is-current' : '' ?>"
               href="<?= e(BASE_PATH . '/history?' . http_build_query(['page' => $pageNumber])) ?>"
               <?= $pageNumber === $pagination['page'] ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
