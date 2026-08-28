<div class="auth-logo"><span class="brand-mark">MB</span> Multi-Business Platform</div>
<h1>Who is signing in?</h1>
<p class="sub">Choose the right door — customer and business accounts are completely separate systems.</p>

<div class="grid" style="gap:14px;margin:22px 0;">
    <a class="card auth-choice" href="<?= e(url('/customer/login')) ?>">
        <div class="auth-choice-icon"><?= icon('person') ?></div>
        <div>
            <strong>Customer</strong>
            <p class="table-muted">Browse businesses, track your enquiries and requests. Sign in with Google or email — no approval needed.</p>
        </div>
        <span class="badge info">Self-service</span>
    </a>
    <a class="card auth-choice" href="<?= e(url('/business/login')) ?>">
        <div class="auth-choice-icon"><?= icon('storefront') ?></div>
        <div>
            <strong>Business</strong>
            <p class="table-muted">Owners and staff managing a business tenant: catalog, enquiries, subscription and website publishing.</p>
        </div>
        <span class="badge muted">Tenant access</span>
    </a>
</div>

<div class="auth-links">
    <span>New to the platform as a business? <a href="<?= e(url('/register-business')) ?>">Register Business</a></span>
    <span>Just looking around? <a href="<?= e(url('/')) ?>">Browse the public directory</a></span>
    <?php if (!\App\Controllers\SetupController::superAdminExistsStatic()): ?>
        <span class="notice" style="width:100%;"><?= icon('build') ?> &nbsp;First run: no platform administrator exists yet. <a href="<?= e(url('/setup')) ?>">Create the initial Super Admin →</a></span>
    <?php endif; ?>
</div>
