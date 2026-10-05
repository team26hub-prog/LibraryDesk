<section class="page-heading">
    <div><p class="eyebrow">MY LIBRARY</p><h1>My borrows</h1><p class="page-description">Books you currently have and their due dates.</p></div>
</section>
<section class="table-panel">
    <div class="table-toolbar"><div><h2>Active borrows</h2><p><?= count($borrows) ?> books</p></div></div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Book</th><th>Author</th><th>Borrowed date</th><th>Due date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($borrows as $borrow): ?>
                <?php
                $overdue = (int) $borrow['days_overdue'] > 0;
                $badgeStatus = $overdue ? 'overdue' : 'borrowed';
                $badgeLabel = $overdue ? 'Overdue' : 'Borrowed';
                ?>
                <tr class="<?= $overdue ? 'overdue-row' : '' ?>">
                    <td class="primary-cell"><?= e($borrow['book_title']) ?></td>
                    <td><?= e($borrow['author']) ?></td>
                    <td><?= e($borrow['borrowed_at']) ?></td>
                    <td><?= e($borrow['due_at']) ?></td>
                    <td><?php require __DIR__ . '/partials/_status-badge.php'; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($borrows === []): ?><tr class="empty-row"><td colspan="5">You have no active borrows.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
