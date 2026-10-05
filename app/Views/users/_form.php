<section class="page-heading">
    <div><p class="eyebrow">USER MANAGEMENT</p><h1><?= e($formHeading) ?></h1><p class="page-description">User details are used for library records and circulation.</p></div>
</section>
<section class="form-panel form-narrow">
    <form class="stacked-form" method="post" action="<?= e(BASE_PATH . $formAction) ?>">
        <?= csrf_field() ?>
        <?php if (!empty($user['id'])): ?><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><?php endif; ?>
        <label>Full name<input type="text" name="name" maxlength="150" value="<?= e($user['name']) ?>" autocomplete="name" required></label>
        <label>Email address<input type="email" name="email" maxlength="190" value="<?= e($user['email']) ?>" autocomplete="email" required></label>
        <label>Phone <span class="optional-label">Optional</span><input type="tel" name="phone" maxlength="30" value="<?= e($user['phone'] ?? '') ?>" autocomplete="tel"></label>
        <div class="form-row">
            <label>Role<select name="role" required><?php foreach (['member' => 'Member', 'librarian' => 'Librarian', 'admin' => 'Administrator'] as $value => $label): ?><option value="<?= e($value) ?>" <?= $user['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label>Status<select name="status" required><?php foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label): ?><option value="<?= e($value) ?>" <?= $user['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        </div>
        <div class="form-actions"><a class="button button-secondary" href="<?= e(BASE_PATH) ?>/users">Cancel</a><button class="button button-primary" type="submit">Save user</button></div>
    </form>
</section>