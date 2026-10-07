<article class="member-book-card">
    <div class="member-book-cover" aria-hidden="true"><?= icon('books') ?></div>
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
        <?php
        $requestStatus = $requestStatuses[(int) $bookCard['id']] ?? null;
        $alreadyRequested = in_array($requestStatus, ['pending', 'granted'], true);
        $requestLabel = match ($requestStatus) {
            'granted' => 'Granted',
            'pending' => 'Requested',
            default => 'Request',
        };
        ?>
        <form class="member-book-request" method="post" action="<?= e(BASE_PATH) ?>/book-requests<?= $alreadyRequested ? '/cancel' : '' ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="book_id" value="<?= (int) $bookCard['id'] ?>">
            <input type="hidden" name="return_to" value="<?= e($currentPath === BASE_PATH . '/browse' ? '/browse' . ($_GET === [] ? '' : '?' . http_build_query($_GET)) : '/') ?>">
            <button class="button button-secondary book-request-button" type="submit" aria-label="<?= e($requestLabel . ': ' . $bookCard['title']) ?>" <?= $alreadyRequested ? 'disabled' : '' ?>><?= e($requestLabel) ?></button>
            <?php if ($requestStatus === 'pending'): ?>
                <button class="button button-secondary book-request-button book-request-cancel" type="submit" aria-label="<?= e('Cancel request for: ' . $bookCard['title']) ?>">Cancel</button>
            <?php endif; ?>
        </form>
    </div>
</article>
