<section class="page-heading">
    <div><p class="eyebrow">THE COLLECTION</p><h1>Browse books</h1><p class="page-description">Find your next read in the library catalogue.</p></div>
</section>

<section class="form-panel member-filter-panel">
    <form class="member-filter-form" method="get" action="<?= e(BASE_PATH) ?>/browse">
        <label class="member-search-field">Search by title or author
            <input type="search" name="q" maxlength="100" value="<?= e($filters['search']) ?>" placeholder="Search the catalogue">
        </label>
        <label>Category
            <select name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="member-available-toggle">
            <input type="checkbox" name="available" value="1" <?= $filters['availableOnly'] ? 'checked' : '' ?>>
            <span>Available only</span>
        </label>
        <button class="button button-primary" type="submit">Apply filters</button>
    </form>
</section>

<section class="section-heading member-results-heading">
    <div><h2>Book catalogue</h2><p class="member-results-count"><?= (int) $pagination['total'] ?> books</p></div>
</section>
<?php if ($books === []): ?>
    <div class="member-empty-state"><p>No books match those filters.</p><a class="button button-secondary" href="<?= e(BASE_PATH) ?>/browse">Clear filters</a></div>
<?php else: ?>
    <section class="member-book-grid" aria-label="Book catalogue">
        <?php foreach ($books as $bookCard): ?>
            <?php require __DIR__ . '/partials/_book-card.php'; ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if ($pagination['pageCount'] > 1): ?>
    <nav class="pagination" aria-label="Catalogue pages">
        <?php for ($pageNumber = 1; $pageNumber <= $pagination['pageCount']; $pageNumber++): ?>
            <?php $pageQuery = $_GET; $pageQuery['page'] = $pageNumber; ?>
            <a class="pagination-link <?= $pageNumber === $pagination['page'] ? 'is-current' : '' ?>"
               href="<?= e(BASE_PATH . '/browse?' . http_build_query($pageQuery)) ?>"
               <?= $pageNumber === $pagination['page'] ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
