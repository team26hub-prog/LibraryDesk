<article class="member-book-card">
    <div class="member-book-cover" aria-hidden="true">▤</div>
    <div class="member-book-details">
        <div class="member-book-status">
            <?php
            $badgeStatus = (int) $bookCard['available_copies'] > 0 ? 'available' : 'unavailable';
            $badgeLabel = null;
            require __DIR__ . '/_status-badge.php';
            ?>
        </div>
        <h3><?= e($bookCard['title']) ?></h3>
        <p class="member-book-author"><?= e($bookCard['author']) ?></p>
        <p class="member-book-category"><?= e($bookCard['category'] ?? 'Uncategorised') ?></p>
        <p class="member-book-copies"><?= (int) $bookCard['available_copies'] ?> of <?= (int) $bookCard['total_copies'] ?> copies available</p>
    </div>
</article>
