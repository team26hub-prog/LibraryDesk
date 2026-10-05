<section class="page-heading">
    <div><p class="eyebrow">BOOK MANAGEMENT</p><h1><?= e($formHeading) ?></h1><p class="page-description">Keep the catalogue and copy counts up to date.</p></div>
</section>
<section class="form-panel form-narrow">
    <form class="stacked-form" method="post" action="<?= e(BASE_PATH . $formAction) ?>">
        <?= csrf_field() ?>
        <?php if (!empty($book['id'])): ?><input type="hidden" name="id" value="<?= (int) $book['id'] ?>"><?php endif; ?>
        <label>Book title<input type="text" name="title" maxlength="255" value="<?= e($book['title']) ?>" required></label>
        <label>Author<input type="text" name="author" maxlength="190" value="<?= e($book['author']) ?>" required></label>
        <div class="form-row">
            <label>ISBN <span class="optional-label">Optional</span><input type="text" name="isbn" maxlength="20" value="<?= e($book['isbn'] ?? '') ?>"></label>
            <label>Category <span class="optional-label">Optional</span><input type="text" name="category" maxlength="120" value="<?= e($book['category'] ?? '') ?>"></label>
        </div>
        <div class="form-row">
            <label>Publication year <span class="optional-label">Optional</span><input type="number" name="published_year" min="1" max="65535" value="<?= e($book['published_year'] ?? '') ?>"></label>
            <label>Total copies<input type="number" name="total_copies" min="1" value="<?= (int) ($book['total_copies'] ?? 1) ?>" required></label>
        </div>
        <?php if (!empty($book['id'])): ?><p class="inline-note">Available copies are calculated from active borrows when you save changes.</p><?php endif; ?>
        <div class="form-actions"><a class="button button-secondary" href="<?= e(BASE_PATH) ?>/books">Cancel</a><button class="button button-primary" type="submit">Save book</button></div>
    </form>
</section>