<div class="auth-logo"><span class="brand-mark">MB</span> Multi-Business Platform</div>
<h1>Welcome back</h1>
<p class="sub">Sign in to track your enquiries, requests and favorite businesses. No admin approval needed — customer accounts are self-service.</p>

<div class="auth-tabs">
    <a class="active" href="<?= e(url('/customer/login')) ?>">Sign in</a>
    <a href="<?= e(url('/customer/register')) ?>">Create account</a>
</div>

<?php if ($googleEnabled): ?>
    <a class="btn-google" href="<?= e(url('/auth/google/redirect')) ?>"><?= icon('google') ?> Continue with Google</a>
    <div class="divider-or">or with email</div>
<?php else: ?>
    <div class="help-text">Google sign-in will appear here once the platform configures OAuth credentials in <code>.env</code>.</div>
<?php endif; ?>

<form method="post" action="<?= e(url('/customer/login')) ?>">
    <?= csrf_field() ?>
    <div class="form-row">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" required maxlength="190" placeholder="you@example.com" value="<?= e(old('email')) ?>">
    </div>
    <div class="form-row">
        <label for="password">Password</label>
        <div style="position:relative;">
            <input id="password" name="password" type="password" required placeholder="••••••••">
            <button type="button" class="btn btn-sm btn-outline" data-toggle-password="#password" style="position:absolute;right:6px;top:5px;padding:4px 8px;font-size:11px;">Show</button>
        </div>
    </div>
    <button class="btn btn-primary" type="submit">Sign in <?= icon('send') ?></button>
</form>

<div class="auth-links">
    <span>New here? <a href="<?= e(url('/customer/register')) ?>">Create a customer account</a></span>
    <span>Business owner or admin? <a href="<?= e(url('/login')) ?>">Portal login</a></span>
    <span><a href="<?= e(url('/')) ?>">← Back to the business directory</a></span>
</div>
