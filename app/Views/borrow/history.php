<section class="page-heading">
    <div><p class="eyebrow">CIRCULATION</p><h1>Borrow history</h1><p class="page-description">Previously completed borrows.</p></div>
    <a class="button button-secondary" href="<?= e(BASE_PATH) ?>/borrow">Back to circulation</a>
</section>
<section class="table-panel">
    <div class="table-toolbar"><div><h2>Returned books</h2><p><?= count($loans) ?> records</p></div><label class="search-box"><?= icon('search') ?><input type="search" placeholder="Search history" data-table-search></label></div>
    <div class="table-scroll"><table data-search-table><thead><tr><th>Member</th><th>Book</th><th>Borrowed</th><th>Due date</th><th>Returned</th></tr></thead><tbody>
    <?php foreach ($loans as $loan): ?><tr><td class="primary-cell"><?= e($loan['user_name']) ?></td><td><?= e($loan['book_title']) ?></td><td><?= e($loan['borrowed_at']) ?></td><td><?= e($loan['due_at']) ?></td><td><?= e($loan['returned_at']) ?></td></tr><?php endforeach; ?>
    <?php if ($loans === []): ?><tr class="empty-row"><td colspan="5">No returned books yet.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>