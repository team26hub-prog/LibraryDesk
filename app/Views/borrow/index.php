<section class="page-heading">
    <div><p class="eyebrow">CIRCULATION</p><h1>Borrow &amp; return</h1><p class="page-description">Track active borrows, due dates, and returned books.</p></div>
    <a class="button button-secondary" href="<?= e(BASE_PATH) ?>/borrow/history">View history</a>
</section>
<section class="form-panel checkout-panel">
    <div class="form-panel-heading"><div><p class="eyebrow">NEW BORROW</p><h2>Record a borrow</h2></div></div>
    <?php if ($members === [] || $books === []): ?><p class="inline-note">Add an active member and an available book before recording a borrow.</p><?php else: ?>
    <form class="inline-form" method="post" action="<?= e(BASE_PATH) ?>/borrow/checkout">
        <?= csrf_field() ?>
        <label>Member<select name="user_id" required><option value="">Select member</option><?php foreach ($members as $member): ?><option value="<?= (int) $member['id'] ?>"><?= e($member['name']) ?> (<?= e($member['email']) ?>)</option><?php endforeach; ?></select></label>
        <label>Book<select name="book_id" required><option value="">Select book</option><?php foreach ($books as $book): ?><option value="<?= (int) $book['id'] ?>"><?= e($book['title']) ?> · <?= (int) $book['available_copies'] ?> available</option><?php endforeach; ?></select></label>
        <label>Due date<input type="date" name="due_at" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required></label>
        <button class="button button-primary" type="submit">Check out</button>
    </form>
    <?php endif; ?>
</section>
<section class="table-panel">
    <div class="table-toolbar"><div><h2>Borrow activity</h2><p><?= count($loans) ?> records</p></div><label class="search-box"><?= icon('search') ?><input type="search" placeholder="Search borrows" data-table-search></label></div>
    <div class="table-scroll"><table data-search-table><thead><tr><th>Member</th><th>Book</th><th>Borrowed</th><th>Due date</th><th>Returned</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($loans as $loan): ?>
        <tr><td class="primary-cell"><?= e($loan['user_name']) ?></td><td><?= e($loan['book_title']) ?></td><td><?= e($loan['borrowed_at']) ?></td><td><?= e($loan['due_at']) ?></td><td><?= e($loan['returned_at'] ?? '—') ?></td><td><span class="pill <?= $loan['status'] === 'active' ? 'pill-yellow' : ($loan['status'] === 'overdue' ? 'pill-red' : 'pill-green') ?>"><?= e($loan['status'] === 'active' ? 'Borrowed' : ucfirst($loan['status'])) ?></span></td><td><?php if ($loan['status'] !== 'returned'): ?><form method="post" action="<?= e(BASE_PATH) ?>/borrow/return" data-confirm="Mark this book as returned?"><?= csrf_field() ?><input type="hidden" name="loan_id" value="<?= (int) $loan['id'] ?>"><button class="text-button" type="submit">Return</button></form><?php else: ?><span class="muted-text">Complete</span><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if ($loans === []): ?><tr class="empty-row"><td colspan="7">No borrows recorded yet.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>