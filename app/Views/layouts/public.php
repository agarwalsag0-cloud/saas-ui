<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Business Portal') ?> · Multi-Business Platform</title>
    <meta name="robots" content="<?= e($noindex ?? false ? 'noindex, nofollow' : 'index, follow') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="public-body">
<nav class="public-nav">
    <a class="public-brand" href="<?= e(url('/')) ?>">
        <span class="brand-mark">MB</span>
        <span>Multi-Business Platform</span>
    </a>
    <div class="actions">
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/')) ?>"><?= icon('search') ?> Directory</a>
        <?php if (\App\Core\CustomerAuth::check()): ?>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/customer')) ?>"><?= icon('person') ?> My Account</a>
        <?php else: ?>
            <a class="btn btn-ghost btn-sm" href="<?= e(url('/login')) ?>"><?= icon('person') ?> Log in</a>
        <?php endif; ?>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/register-business')) ?>"><?= icon('storefront') ?> List your business</a>
    </div>
</nav>
<main class="public-main">
    <?php $messages = flash_messages(); if ($messages): ?>
        <div class="public-section" style="padding-bottom:0;">
            <div class="alerts">
                <?php foreach ($messages as $message): ?>
                    <div class="alert <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>
<footer class="public-footer">
    <small>Powered by Multi-Business Subscription Platform. Each public portal is an isolated tenant under one central SaaS system.</small>
</footer>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
