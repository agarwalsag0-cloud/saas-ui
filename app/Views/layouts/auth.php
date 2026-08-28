<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Login') ?> · Multi-Business Platform</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="auth-page">
    <aside class="auth-side" aria-hidden="true">
        <span class="brand-mark" style="width:48px;height:48px;border-radius:12px;font-size:16px;">MB</span>
        <h2>Independent businesses.<br>One secure platform.</h2>
        <p>Manage profiles, catalogs, enquiries, subscription-gated features and public websites — all isolated per tenant and enforced server-side.</p>
        <ul>
            <li>Feature-based subscription plans</li>
            <li>Publish-controlled public websites</li>
            <li>Enquiries routed directly to the right business</li>
        </ul>
        <div class="auth-foot">&copy; <?= date('Y') ?> Multi-Business Subscription Platform</div>
    </aside>
    <div class="auth-card">
        <?php $messages = flash_messages(); if ($messages): ?>
            <div class="alerts">
                <?php foreach ($messages as $message): ?>
                    <div class="alert <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?= $content ?>
    </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
