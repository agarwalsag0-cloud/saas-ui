<div class="auth-logo"><span class="brand-mark">MB</span> Business Portal</div>
<span class="page-kicker">Tenant access</span>
<h1>Business Portal Login</h1>
<p class="sub">Sign in with your business owner or staff account. Customer accounts and platform administration use their own separate sign-in.</p>

<form method="post" action="<?= e(url('/business/login')) ?>" autocomplete="on">
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
    <button class="btn btn-primary" type="submit">Login to my business portal <?= icon('send') ?></button>
</form>

<div class="help-text" style="margin-top:10px;">Your sign-in gives access to the business tenant your account belongs to — never to any other business, regardless of URLs or IDs.</div>

<div class="auth-links">
    <span>New business? <a href="<?= e(url('/register-business')) ?>">Register Business</a></span>
    <span>Shopping instead of selling? <a href="<?= e(url('/customer/login')) ?>">Customer sign in</a></span>
    <span><a href="<?= e(url('/login')) ?>">← Back to sign-in options</a></span>
</div>
