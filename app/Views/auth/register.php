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
		<div class="auth-password-field">
			<label for="register-password">Password</label>
			<span class="password-input">
				<input id="register-password" type="password" name="password" minlength="8" autocomplete="new-password" aria-describedby="password-hint" required data-auth-password>
				<button class="password-toggle" type="button" aria-label="Show password" aria-controls="register-password" aria-pressed="false" data-password-toggle hidden><span class="password-eye-show"><?= icon('eye') ?></span><span class="password-eye-hide"><?= icon('eye-off') ?></span></button>
			</span>
		</div>
		<p class="auth-field-hint" id="password-hint">Use 8–72 bytes. Passwords are case-sensitive.</p>
		<div class="auth-password-field">
			<label for="register-password-confirmation">Confirm password</label>
			<span class="password-input">
				<input id="register-password-confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required data-auth-confirm-password>
				<button class="password-toggle" type="button" aria-label="Show confirm password" aria-controls="register-password-confirmation" aria-pressed="false" data-password-toggle hidden><span class="password-eye-show"><?= icon('eye') ?></span><span class="password-eye-hide"><?= icon('eye-off') ?></span></button>
			</span>
		</div>
		<button class="button button-primary" type="submit">Create account</button>
		<p class="auth-switch">Already registered? <a href="<?= e(BASE_PATH) ?>/login">Sign in</a></p>
	</form>
</section>
