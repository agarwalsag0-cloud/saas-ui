<div class="auth-logo"><span class="brand-mark">MB</span> Multi-Business Platform</div>
<h1>Business Portal Login</h1>
<p class="sub">Secure access for Super Admins, business owners and staff.</p>

<form method="post" action="<?= e(url('/login')) ?>" autocomplete="on">
    <?= csrf_field() ?>
    <div class="form-row">
        <label for="email">Email address</label>
        <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required autofocus placeholder="you@business.com">
    </div>
    <div class="form-row">
        <label for="password">Password</label>
        <div style="position:relative;">
            <input id="password" type="password" name="password" required placeholder="••••••••">
            <button type="button" class="btn btn-sm btn-outline" data-toggle-password="#password" style="position:absolute;right:6px;top:5px;padding:4px 8px;font-size:11px;">Show</button>
        </div>
    </div>
    <button class="btn btn-primary" type="submit">Login to Portal <?= icon('send') ?></button>
</form>

<div class="auth-links">
    <span>New to the platform? <a href="<?= e(url('/register-business')) ?>">Register Business</a></span>
    <span>Shopping for a business? <a href="<?= e(url('/customer/login')) ?>">Customer Login</a></span>
    <?php if (!\App\Controllers\SetupController::superAdminExistsStatic()): ?>
        <span><a href="<?= e(url('/setup')) ?>">First-time Super Admin setup →</a></span>
    <?php endif; ?>
</div>
