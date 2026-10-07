<section class="page-heading">
    <div><p class="eyebrow">MEMBER REQUESTS</p><h1>Book requests</h1><p class="page-description">See which books library members have requested.</p></div>
</section>
<section class="table-panel">
    <div class="table-toolbar">
        <div><h2>All requests</h2><p><?= count($requests) ?> requests</p></div>
        <label class="search-box"><?= icon('search') ?><input type="search" placeholder="Search requests" aria-label="Search book requests" data-table-search></label>
    </div>
    <div class="table-scroll">
        <table data-search-table>
            <thead><tr><th>Member</th><th>Email</th><th>Book</th><th>Author</th><th>Requested on</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <tr><td class="primary-cell"><?= e($request['user_name']) ?></td><td><?= e($request['user_email']) ?></td><td class="primary-cell"><?= e($request['book_title']) ?></td><td><?= e($request['author']) ?></td><td><?= e($request['created_at']) ?></td>
                    <td>
                        <?php if ($request['status'] === 'pending'): ?>
                            <form class="request-status-actions" method="post" action="<?= e(BASE_PATH) ?>/book-requests/status">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                <button class="button button-primary book-request-button" type="submit" name="status" value="granted">Granted</button>
                                <button class="button button-secondary book-request-button book-request-cancel" type="submit" name="status" value="cancelled">Cancel</button>
                            </form>
                        <?php else: ?>
                            <span class="pill <?= $request['status'] === 'granted' ? 'pill-green' : 'pill-red' ?>"><?= $request['status'] === 'granted' ? 'Granted' : 'Cancelled' ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($requests === []): ?><tr class="empty-row"><td colspan="6">No book requests yet. Requests from members will appear here.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
