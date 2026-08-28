<div class="auth-logo"><span class="brand-mark" style="background:#151c27;color:#fff;">AD</span> Platform Administration</div>
<span class="page-kicker">Restricted area</span>
<h1>Super Admin sign-in</h1>
<p class="sub">Platform administrators only. Business owners use the <a href="<?= e(url('/business/login')) ?>">business portal login</a>; customers use the <a href="<?= e(url('/customer/login')) ?>">customer sign-in</a>.</p>

<form method="post" action="<?= e(url('/admin/login')) ?>" autocomplete="on">
    <?= csrf_field() ?>
    <div class="form-row">
        <label for="email">Email address</label>
        <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="username" placeholder="admin@yourplatform.com">
    </div>
    <div class="form-row">
        <label for="password">Password</label>
        <div style="position:relative;">
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            <button type="button" class="btn btn-sm btn-outline" data-toggle-password="#password" style="position:absolute;right:6px;top:5px;padding:4px 8px;font-size:11px;">Show</button>
        </div>
    </div>
    <button class="btn btn-primary" type="submit"><?= icon('lock') ?> Enter Admin Console</button>
</form>

<div class="help-text" style="margin-top:10px;">Failed sign-in attempts are rate limited. This entry is not linked from public pages — treat its URL as confidential.</div>

<div class="auth-links">
    <span><a href="<?= e(url('/')) ?>">← Back to the platform</a></span>
</div>
