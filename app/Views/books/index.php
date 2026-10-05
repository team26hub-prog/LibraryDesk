<section class="page-heading">
    <div><p class="eyebrow">COLLECTION</p><h1>Book management</h1><p class="page-description">Maintain titles, inventory, and availability.</p></div>
    <a class="button button-primary" href="<?= e(BASE_PATH) ?>/books/create">＋ <span>Add book</span></a>
</section>
<section class="table-panel">
    <div class="table-toolbar"><div><h2>Book catalogue</h2><p><?= count($books) ?> titles</p></div><label class="search-box"><span>⌕</span><input type="search" placeholder="Search books" data-table-search></label></div>
    <div class="table-scroll"><table data-search-table><thead><tr><th>Title</th><th>Author</th><th>ISBN</th><th>Category</th><th>Available</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($books as $book): ?>
        <tr><td class="primary-cell"><?= e($book['title']) ?></td><td><?= e($book['author']) ?></td><td><?= e($book['isbn'] ?? '—') ?></td><td><?= e($book['category'] ?? 'Uncategorised') ?></td><td><?= (int) $book['available_copies'] ?> / <?= (int) $book['total_copies'] ?></td><td class="row-actions"><a href="<?= e(BASE_PATH) ?>/books/edit?id=<?= (int) $book['id'] ?>">Edit</a><form method="post" action="<?= e(BASE_PATH) ?>/books/delete" data-confirm="Delete this book?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $book['id'] ?>"><button class="text-button danger-text" type="submit">Delete</button></form></td></tr>
    <?php endforeach; ?>
    <?php if ($books === []): ?><tr class="empty-row"><td colspan="6">No books yet. Add titles to build your catalogue.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>