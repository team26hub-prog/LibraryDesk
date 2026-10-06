<section class="page-heading">
    <div><p class="eyebrow">DIRECTORY</p><h1>User management</h1><p class="page-description">Manage library members and staff accounts.</p></div>
    <a class="button button-primary" href="<?= e(BASE_PATH) ?>/users/create"><?= icon('plus') ?> <span>Add user</span></a>
</section>
<section class="table-panel">
    <div class="table-toolbar"><div><h2>All users</h2><p><?= count($users) ?> records</p></div><label class="search-box"><?= icon('search') ?><input type="search" placeholder="Search users" data-table-search></label></div>
    <div class="table-scroll"><table data-search-table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($users as $user): ?>
        <tr><td class="primary-cell"><?= e($user['name']) ?></td><td><?= e($user['email']) ?></td><td><?= e($user['phone'] ?? '—') ?></td><td><?= e(ucfirst($user['role'])) ?></td><td><span class="pill <?= $user['status'] === 'active' ? 'pill-green' : 'pill-muted' ?>"><?= e(ucfirst($user['status'])) ?></span></td><td class="row-actions"><a href="<?= e(BASE_PATH) ?>/users/edit?id=<?= (int) $user['id'] ?>">Edit</a><form method="post" action="<?= e(BASE_PATH) ?>/users/delete" data-confirm="Delete this user?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><button class="text-button danger-text" type="submit">Delete</button></form></td></tr>
    <?php endforeach; ?>
    <?php if ($users === []): ?><tr class="empty-row"><td colspan="6">No users yet. Add your first member to get started.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>