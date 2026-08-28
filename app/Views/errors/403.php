<?php
$isBusiness = \App\Core\Auth::isBusinessUser();
$isAdmin = \App\Core\Auth::isSuperAdmin();
?>
<section class="public-section">
    <div class="card" style="text-align:center;max-width:720px;margin:60px auto;">
        <span class="badge danger">403</span>
        <h1 class="page-title">Access denied</h1>
        <p class="page-description" style="margin:0 auto 8px;"><?= e($message ?? 'You are not allowed to access this resource.') ?></p>
        <p class="table-muted">Authorization is enforced server-side: this response does not depend on anything hidden in the UI.</p>
        <div class="actions" style="justify-content:center;margin-top:16px;">
            <?php if ($isBusiness): ?>
                <a class="btn btn-primary" href="<?= e(url('/business')) ?>">Back to my business portal</a>
            <?php elseif ($isAdmin): ?>
                <a class="btn btn-primary" href="<?= e(url('/admin')) ?>">Back to the admin console</a>
            <?php else: ?>
                <a class="btn btn-primary" href="<?= e(url('/login')) ?>">Sign in</a>
            <?php endif; ?>
            <a class="btn btn-outline" href="<?= e(url('/')) ?>">Public directory</a>
        </div>
    </div>
</section>
