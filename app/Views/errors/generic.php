<section class="public-section">
    <div class="card" style="text-align:center;max-width:720px;margin:60px auto;">
        <span class="badge danger"><?= e($statusCode ?? 'Error') ?></span>
        <h1 class="page-title">Request failed</h1>
        <p class="page-description" style="margin:0 auto 18px;"><?= e($message ?? 'The request could not be completed.') ?></p>
        <a class="btn btn-primary" href="<?= e(url('/')) ?>">Go home</a>
    </div>
</section>
