<div class="auth-logo"><span class="brand-mark">MB</span> Multi-Business Platform</div>
<h1>Create your customer account</h1>
<p class="sub">Track enquiries and requests across every business website on the platform. Approval is not required — your account works immediately.</p>

<div class="auth-tabs">
    <a href="<?= e(url('/customer/login')) ?>">Sign in</a>
    <a class="active" href="<?= e(url('/customer/register')) ?>">Create account</a>
</div>

<?php if ($googleEnabled): ?>
    <a class="btn-google" href="<?= e(url('/auth/google/redirect')) ?>"><?= icon('google') ?> Continue with Google</a>
    <div class="divider-or">or with email</div>
<?php endif; ?>

<form method="post" action="<?= e(url('/customer/register')) ?>">
    <?= csrf_field() ?>
    <div class="form-row">
        <label for="name">Full name</label>
        <input id="name" name="name" type="text" required maxlength="190" value="<?= e(old('name')) ?>">
    </div>
    <div class="form-grid" style="gap:12px;">
        <div class="form-row">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" required maxlength="190" value="<?= e(old('email')) ?>">
        </div>
        <div class="form-row">
            <label for="phone">Phone (optional)</label>
            <input id="phone" name="phone" type="tel" maxlength="40" value="<?= e(old('phone')) ?>">
        </div>
        <div class="form-row">
            <label for="password">Password (min 8 characters)</label>
            <input id="password" name="password" type="password" required minlength="8">
        </div>
        <div class="form-row">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8">
        </div>
    </div>
    <button class="btn btn-primary" type="submit">Create account <?= icon('send') ?></button>
</form>

<div class="auth-links">
    <span>Already registered? <a href="<?= e(url('/customer/login')) ?>">Sign in</a></span>
    <span>Looking for the business portal? <a href="<?= e(url('/business/login')) ?>">Business portal login</a></span>
</div>
