<div class="auth-logo"><span class="brand-mark">SA</span> Initial Super Admin Setup</div>
<h1>Create Super Admin</h1>
<p class="sub">No default admin exists. Set the first platform owner here after importing the database — credentials are stored hashed, never hard-coded.</p>
<form method="post" action="<?= e(url('/setup')) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="form-row">
        <label for="name">Name</label>
        <input id="name" type="text" name="name" value="<?= e(old('name')) ?>" required autofocus>
    </div>
    <div class="form-row">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required>
    </div>
    <div class="form-row">
        <label for="password">Password (min 8 characters)</label>
        <input id="password" type="password" name="password" minlength="8" required>
    </div>
    <div class="form-row">
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required>
    </div>
    <button class="btn btn-primary" type="submit">Create Super Admin</button>
</form>
<p class="help-text">After this, new business owners are added from Super Admin or through public registration.</p>
<div class="auth-links"><span>Already set up? <a href="<?= e(url('/admin/login')) ?>">Admin sign-in</a></span></div>
