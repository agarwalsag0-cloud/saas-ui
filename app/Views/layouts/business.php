<?php
$active = $active ?? '';
$businessUnreadNotifications = $businessUnreadNotifications ?? 0;
$featureAccess = $featureAccess ?? [];
$websiteAccess = $websiteAccess ?? null;
$canFeature = fn(string $identifier): bool => isset($featureAccess[$identifier]);
$canAnyFeature = fn(array $identifiers): bool => (bool) array_intersect($identifiers, array_keys($featureAccess));
if (!function_exists('business_nav_active')) { function business_nav_active(string $key, string $active): string { return $key === $active ? 'active' : ''; } }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Business') ?> · <?= e($business['name'] ?? 'Business Portal') ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar business-sidebar">
        <a class="brand" href="<?= e(url('/business')) ?>">
            <span class="brand-mark"><?= e(strtoupper(substr($business['name'] ?? 'B', 0, 1))) ?></span>
            <span>
                <span class="brand-title"><?= e($business['name'] ?? 'Business') ?></span>
                <span class="brand-subtitle">Business Portal</span>
            </span>
        </a>
        <nav>
            <div class="nav-section-title">Manage</div>
            <a class="nav-link <?= business_nav_active('dashboard', $active) ?>" href="<?= e(url('/business')) ?>"><?= icon('dashboard') ?> Dashboard</a>
            <?php if ($canFeature('business_profile')): ?><a class="nav-link <?= business_nav_active('profile', $active) ?>" href="<?= e(url('/business/profile')) ?>"><?= icon('storefront') ?> Business Profile</a><?php endif; ?>
            <?php if ($canFeature('public_website')): ?>
                <a class="nav-link <?= business_nav_active('website', $active) ?>" href="<?= e(url('/business/website')) ?>"><?= icon('palette') ?> Website</a>
            <?php else: ?>
                <a class="nav-link <?= business_nav_active('website', $active) ?>" href="<?= e(url('/business/website')) ?>"><?= icon('lock') ?> Website <span class="badge muted" style="margin-left:auto;">Locked</span></a>
            <?php endif; ?>
            <?php if ($canFeature('categories')): ?><a class="nav-link <?= business_nav_active('categories', $active) ?>" href="<?= e(url('/business/categories')) ?>"><?= icon('build') ?> Categories</a><?php endif; ?>
            <?php if ($canAnyFeature(['product_management','service_management'])): ?><a class="nav-link <?= business_nav_active('listings', $active) ?>" href="<?= e(url('/business/listings')) ?>"><?= icon('inventory') ?> Products &amp; Services</a><?php endif; ?>
            <?php if ($canFeature('offers')): ?><a class="nav-link <?= business_nav_active('offers', $active) ?>" href="<?= e(url('/business/offers')) ?>"><?= icon('bolt') ?> Offers</a><?php endif; ?>
            <div class="nav-section-title">Customers</div>
            <?php if ($canFeature('enquiries')): ?><a class="nav-link <?= business_nav_active('enquiries', $active) ?>" href="<?= e(url('/business/enquiries')) ?>"><?= icon('chat') ?> Enquiries</a><?php endif; ?>
            <?php if ($canFeature('orders')): ?><a class="nav-link <?= business_nav_active('orders', $active) ?>" href="<?= e(url('/business/orders')) ?>"><?= icon('receipt') ?> Orders/Requests</a><?php endif; ?>
            <div class="nav-section-title">Account</div>
            <a class="nav-link <?= business_nav_active('subscription', $active) ?>" href="<?= e(url('/business/subscription')) ?>"><?= icon('credit-card') ?> Subscription</a>
            <?php if ($canFeature('notifications')): ?><a class="nav-link <?= business_nav_active('notifications', $active) ?>" href="<?= e(url('/business/notifications')) ?>"><?= icon('notifications') ?> Notifications <?php if ($businessUnreadNotifications): ?><span class="pill"><?= (int) $businessUnreadNotifications ?></span><?php endif; ?></a><?php endif; ?>
            <?php if ($canFeature('public_website') && !empty($websiteAccess['public_access'])): ?><a class="nav-link" href="<?= e(url('/p/' . ($business['slug'] ?? ''))) ?>" target="_blank" rel="noopener"><?= icon('public') ?> View My Website</a><?php endif; ?>
            <div class="nav-section-title">Session</div>
            <form method="post" action="<?= e(url('/logout')) ?>" style="margin:0;">
                <?= csrf_field() ?>
                <button class="nav-link" type="submit" style="width:100%;background:none;border:none;border-left:4px solid transparent;font:inherit;color:inherit;cursor:pointer;"><?= icon('logout') ?> Logout</button>
            </form>
        </nav>
        <div style="margin-top:auto;padding:18px 16px 0;">
            <?php if (!$canFeature('public_website') || empty($websiteAccess['public_access'])): ?>
                <a class="btn btn-primary btn-block" href="<?= e(url('/business/subscription')) ?>" style="margin-bottom:12px;"><?= icon('bolt') ?> Upgrade Plan</a>
            <?php endif; ?>
        </div>
    </aside>
    <div class="main-wrap">
        <header class="topbar">
            <div class="actions">
                <button class="btn btn-outline sidebar-toggle" data-sidebar-toggle type="button" aria-label="Toggle navigation"><?= icon('menu') ?></button>
                <h1><?= e($pageTitle ?? 'Business') ?></h1>
            </div>
            <div class="topbar-actions">
                <?= status_badge($effectiveSubscriptionStatus ?? 'unknown') ?>
                <div class="user-chip">
                    <span class="avatar"><?= e(strtoupper(substr($businessUser['name'] ?? 'U', 0, 1))) ?></span>
                    <small><?= e($businessUser['name'] ?? 'User') ?></small>
                </div>
            </div>
        </header>
        <main class="content">
            <?php if (in_array((string)($business['status'] ?? ''), ['pending','under_review','changes_requested'], true)): ?>
                <div class="notice warning"><strong>Registration review:</strong>&nbsp; Your business is <em><?= e(str_replace('_', ' ', (string)$business['status'])) ?></em>. Complete your profile and website content, then submit for review — your business is not publicly listed until the Super Admin approves it<?= $business['status'] === 'changes_requested' ? ' and the requested changes are resolved' : '' ?>.<?= !empty($business['review_note']) ? '<br><span class="table-muted">Platform note: ' . e($business['review_note']) . '</span>' : '' ?></div>
            <?php endif; ?>
            <?php if (!$portalAllowed && ($active ?? '') !== 'subscription'): ?>
                <div class="alert warning"><strong>Restricted:</strong>&nbsp; Your current status is <?= e(str_replace('_', ' ', $effectiveSubscriptionStatus ?? 'restricted')) ?>. Some actions are disabled until the subscription/business is active.</div>
            <?php endif; ?>
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
