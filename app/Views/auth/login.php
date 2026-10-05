<?php
$oldAuthInput = $_SESSION['_auth_old'] ?? [];
unset($_SESSION['_auth_old']);
?>
<section class="auth-wrap">
	<div class="auth-heading"><p class="eyebrow">LIBRARY DESK</p><h1>Sign in</h1><p class="page-description">Use your library account to continue.</p></div>
	<form class="form-panel stacked-form auth-form" method="post" action="<?= e(BASE_PATH) ?>/login" data-auth-login>
		<?= csrf_field() ?>
		<label>Email address<input type="email" name="email" maxlength="190" value="<?= e($oldAuthInput['email'] ?? '') ?>" autocomplete="username" required autofocus></label>
		<label>Password<input type="password" name="password" maxlength="4096" autocomplete="current-password" required></label>
		<button class="button button-primary" type="submit">Sign in</button>
		<div class="auth-switch">
			<p>New to the system?</p>
			<a class="button button-secondary auth-signup-button" href="<?= e(BASE_PATH) ?>/register">Create an account</a>
		</div>
	</form>
</section>
