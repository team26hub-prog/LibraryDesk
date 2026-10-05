<?php
$oldAuthInput = $_SESSION['_auth_old'] ?? [];
unset($_SESSION['_auth_old']);
?>
<section class="auth-wrap">
	<div class="auth-heading"><p class="eyebrow">LIBRARY DESK</p><h1>Create account</h1><p class="page-description">Register a library account.</p></div>
	<form class="form-panel stacked-form auth-form" method="post" action="<?= e(BASE_PATH) ?>/register" data-auth-register>
		<?= csrf_field() ?>
		<label>Full name<input type="text" name="name" maxlength="150" value="<?= e($oldAuthInput['name'] ?? '') ?>" autocomplete="name" required autofocus data-auth-name></label>
		<label>Email address<input type="email" name="email" maxlength="190" value="<?= e($oldAuthInput['email'] ?? '') ?>" autocomplete="email" required></label>
		<label>Password<input type="password" name="password" minlength="8" autocomplete="new-password" aria-describedby="password-hint" required data-auth-password></label>
		<p class="auth-field-hint" id="password-hint">Use 8–72 bytes. Passwords are case-sensitive.</p>
		<label>Confirm password<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required data-auth-confirm-password></label>
		<button class="button button-primary" type="submit">Create account</button>
		<p class="auth-switch">Already registered? <a href="<?= e(BASE_PATH) ?>/login">Sign in</a></p>
	</form>
</section>
