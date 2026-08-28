<?php
$active = $active ?? '';
$customer = $customer ?? [];
$customerUnreadNotifications = $customerUnreadNotifications ?? 0;
if (!function_exists('customer_nav_active')) { function customer_nav_active(string $key, string $active): string { return $key === $active ? 'active' : ''; } }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Customer Portal') ?> · Multi-Business Platform</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="<?= e(url('/customer')) ?>">
            <span class="brand-mark"><?= e(strtoupper(substr($customer['name'] ?? 'C', 0, 1))) ?></span>
            <span>
                <span class="brand-title">Customer Portal</span>
                <span class="brand-subtitle">Manage Experience</span>
            </span>
        </a>
        <nav>
            <a class="nav-link <?= customer_nav_active('dashboard', $active) ?>" href="<?= e(url('/customer')) ?>"><?= icon('dashboard') ?> Dashboard</a>
            <a class="nav-link" href="<?= e(url('/')) ?>"><?= icon('storefront') ?> Public Businesses</a>
            <a class="nav-link <?= customer_nav_active('enquiries', $active) ?>" href="<?= e(url('/customer/enquiries')) ?>"><?= icon('chat') ?> My Enquiries</a>
            <a class="nav-link <?= customer_nav_active('orders', $active) ?>" href="<?= e(url('/customer/orders')) ?>"><?= icon('receipt') ?> My Orders</a>
            <a class="nav-link <?= customer_nav_active('profile', $active) ?>" href="<?= e(url('/customer/profile')) ?>"><?= icon('person') ?> Profile</a>
            <div class="nav-section-title">Account</div>
            <a class="nav-link <?= customer_nav_active('notifications', $active) ?>" href="<?= e(url('/customer/notifications')) ?>"><?= icon('notifications') ?> Notifications <?php if ($customerUnreadNotifications): ?><span class="pill"><?= (int) $customerUnreadNotifications ?></span><?php endif; ?></a>
            <form method="post" action="<?= e(url('/customer/logout')) ?>" style="margin:0;">
                <?= csrf_field() ?>
                <button class="nav-link" type="submit" style="width:100%;background:none;border:none;border-left:4px solid transparent;font:inherit;color:inherit;cursor:pointer;"><?= icon('logout') ?> Logout</button>
            </form>
        </nav>
        <div style="margin-top:auto;padding:18px 16px 0;">
            <a class="btn btn-primary btn-block" href="<?= e(url('/')) ?>" style="margin-bottom:14px;"><?= icon('search') ?> Browse Businesses</a>
        </div>
    </aside>
    <div class="main-wrap">
        <header class="topbar">
            <div class="actions">
                <button class="btn btn-outline sidebar-toggle" data-sidebar-toggle type="button" aria-label="Toggle navigation"><?= icon('menu') ?></button>
                <h1><?= e($pageTitle ?? 'Customer Portal') ?></h1>
            </div>
            <div class="topbar-actions">
                <div class="user-chip">
                    <?php if (!empty($customer['avatar_path'])): ?>
                        <span class="avatar"><img src="<?= e(upload_url($customer['avatar_path'])) ?>" alt=""></span>
                    <?php else: ?>
                        <span class="avatar"><?= e(strtoupper(substr($customer['name'] ?? 'C', 0, 1))) ?></span>
                    <?php endif; ?>
                    <small><?= e($customer['name'] ?? 'Customer') ?></small>
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
