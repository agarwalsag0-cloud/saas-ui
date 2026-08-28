<?php
$active = $active ?? '';
$adminUser = $adminUser ?? [];
$adminUnreadNotifications = $adminUnreadNotifications ?? 0;
$adminReviewQueueCount = $adminReviewQueueCount ?? 0;
function admin_nav_active(string $key, string $active): string { return $key === $active ? 'active' : ''; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Admin') ?> · Platform Control</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="<?= e(url('/admin')) ?>">
            <span class="brand-mark">SA</span>
            <span>
                <span class="brand-title">Platform Control</span>
                <span class="brand-subtitle">Super Admin Portal</span>
            </span>
        </a>
        <nav>
            <div class="nav-section-title">Overview</div>
            <a class="nav-link <?= admin_nav_active('dashboard', $active) ?>" href="<?= e(url('/admin')) ?>"><?= icon('dashboard') ?> Dashboard</a>
            <a class="nav-link <?= admin_nav_active('businesses', $active) ?>" href="<?= e(url('/admin/businesses')) ?>"><?= icon('storefront') ?> Businesses <?php if ($adminReviewQueueCount): ?><span class="pill"><?= (int) $adminReviewQueueCount ?></span><?php endif; ?></a>
            <a class="nav-link <?= admin_nav_active('plans', $active) ?>" href="<?= e(url('/admin/plans')) ?>"><?= icon('credit-card') ?> Subscription Plans</a>
            <a class="nav-link <?= admin_nav_active('features', $active) ?>" href="<?= e(url('/admin/features')) ?>"><?= icon('build') ?> Feature Registry</a>
            <div class="nav-section-title">Platform</div>
            <a class="nav-link <?= admin_nav_active('activity', $active) ?>" href="<?= e(url('/admin/activity')) ?>"><?= icon('history') ?> Activity &amp; Audit</a>
            <a class="nav-link <?= admin_nav_active('notifications', $active) ?>" href="<?= e(url('/admin/notifications')) ?>"><?= icon('notifications') ?> Notifications <?php if ($adminUnreadNotifications): ?><span class="pill"><?= (int) $adminUnreadNotifications ?></span><?php endif; ?></a>
            <div class="nav-section-title">Public</div>
            <a class="nav-link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('public') ?> Portal Directory</a>
            <div class="nav-section-title">Session</div>
            <form method="post" action="<?= e(url('/logout')) ?>" style="margin:0;">
                <?= csrf_field() ?>
                <button class="nav-link" type="submit" style="width:100%;background:none;border:none;border-left:4px solid transparent;font:inherit;color:inherit;cursor:pointer;"><?= icon('logout') ?> Logout</button>
            </form>
        </nav>
    </aside>
    <div class="main-wrap">
        <header class="topbar">
            <div class="actions">
                <button class="btn btn-outline sidebar-toggle" data-sidebar-toggle type="button" aria-label="Toggle navigation"><?= icon('menu') ?></button>
                <h1><?= e($pageTitle ?? 'Admin') ?></h1>
            </div>
            <div class="topbar-actions">
                <?php if ($adminReviewQueueCount): ?><a class="badge warning" href="<?= e(url('/admin/businesses?status=under_review')) ?>">Review queue: <?= (int) $adminReviewQueueCount ?></a><?php endif; ?>
                <div class="user-chip">
                    <span class="avatar"><?= e(strtoupper(substr($adminUser['name'] ?? 'A', 0, 1))) ?></span>
                    <small><?= e($adminUser['name'] ?? 'Admin') ?></small>
                </div>
            </div>
        </header>
        <main class="content">
            <?php $messages = flash_messages(); if ($messages): ?>
                <div class="alerts">
                    <?php foreach ($messages as $message): ?>
                        <div class="alert <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
