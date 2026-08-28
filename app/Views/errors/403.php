<section class="public-section">
    <div class="card" style="text-align:center;max-width:720px;margin:60px auto;">
        <span class="badge danger">403</span>
        <h1 class="page-title">Access denied</h1>
        <p class="page-description" style="margin:0 auto 18px;"><?= e($message ?? 'You are not allowed to access this resource.') ?></p>
        <a class="btn btn-primary" href="<?= e(url('/login')) ?>">Login</a>
    </div>
</section>
