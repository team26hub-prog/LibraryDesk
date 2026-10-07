<section class="page-heading">
    <div><p class="eyebrow">MY LIBRARY</p><h1>Profile</h1><p class="page-description">Manage your account details and password.</p></div>
</section>
<section class="member-profile-grid">
    <article class="form-panel">
        <div class="form-panel-heading"><p class="eyebrow">ACCOUNT DETAILS</p><h2>Your profile</h2></div>
        <form class="stacked-form" method="post" action="<?= e(BASE_PATH) ?>/profile/name">
            <?= csrf_field() ?>
            <label>Full name<input type="text" name="name" maxlength="150" value="<?= e($user['name']) ?>" autocomplete="name" pattern="<?= e(full_name_pattern()) ?>" title="Use letters, spaces, apostrophes, or hyphens." required data-full-name></label>
            <label>Email address<input type="email" value="<?= e($user['email']) ?>" autocomplete="email" readonly></label>
            <label>Membership status<input type="text" value="<?= e(ucfirst($user['status'])) ?>" readonly></label>
            <div class="form-actions"><button class="button button-primary" type="submit">Save name</button></div>
        </form>
    </article>
    <article class="form-panel">
        <div class="form-panel-heading"><p class="eyebrow">SECURITY</p><h2>Change password</h2></div>
        <form class="stacked-form" method="post" action="<?= e(BASE_PATH) ?>/profile/password">
            <?= csrf_field() ?>
            <label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
            <label>New password<input type="password" name="new_password" minlength="8" autocomplete="new-password" required></label>
            <label>Confirm new password<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
            <div class="form-actions"><button class="button button-primary" type="submit">Change password</button></div>
        </form>
    </article>
</section>
