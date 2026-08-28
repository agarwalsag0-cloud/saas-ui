<?php
$settings = $settings ?? [];
$business = $business ?? ['name' => 'Business Website', 'slug' => ''];
$preview = !empty($preview);
$noindex = !empty($noindex) || $preview;
$canonicalUrl = $canonicalUrl ?? null;
$primary = valid_hex_color($settings['primary_color'] ?? ($settings['theme_color'] ?? '#2563eb'), '#2563eb');
$secondary = valid_hex_color($settings['secondary_color'] ?? '#0f172a', '#0f172a');
$accent = valid_hex_color($settings['accent_color'] ?? '#f97316', '#f97316');
$buttonText = contrast_text_color($primary);
$businessName = $business['name'] ?? 'Business Website';
$metaDescription = $seoDescription ?? ($settings['seo_description'] ?? text_excerpt($business['description'] ?? ($business['tagline'] ?? ''), 160));
$layoutClass = 'website-layout-' . preg_replace('/[^a-z_\-]/', '', (string) ($settings['layout_style'] ?? 'classic'));
$bgClass = 'website-bg-' . preg_replace('/[^a-z_\-]/', '', (string) ($settings['background_style'] ?? 'light'));
$buttonClass = 'website-buttons-' . preg_replace('/[^a-z_\-]/', '', (string) ($settings['button_style'] ?? 'rounded'));
$textClass = 'website-text-' . preg_replace('/[^a-z_\-]/', '', (string) ($settings['text_style'] ?? 'system'));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? $businessName) ?></title>
    <meta name="description" content="<?= e($metaDescription ?: $businessName) ?>">
    <meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow' ?>">
    <?php if (!$noindex && $canonicalUrl): ?><link rel="canonical" href="<?= e($canonicalUrl) ?>"><?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle ?? $businessName) ?>">
    <meta property="og:description" content="<?= e($metaDescription ?: $businessName) ?>">
    <?php if (!$noindex && $canonicalUrl): ?><meta property="og:url" content="<?= e($canonicalUrl) ?>"><?php endif; ?>
    <?php if (!empty($business['cover_path'])): ?><meta property="og:image" content="<?= e(upload_url($business['cover_path'])) ?>"><?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <style>
        :root {
            --site-primary: <?= e($primary) ?>;
            --site-secondary: <?= e($secondary) ?>;
            --site-accent: <?= e($accent) ?>;
            --site-button-text: <?= e($buttonText) ?>;
        }
    </style>
</head>
<body class="website-page <?= e($layoutClass . ' ' . $bgClass . ' ' . $buttonClass . ' ' . $textClass) ?><?= $preview ? ' has-preview' : '' ?>">
<?php if ($preview): ?>
    <div class="preview-banner">
        <span class="badge info">Preview mode</span>
        This is a live preview of your website configuration. Search engines and visitors cannot see it until you <strong>Publish</strong>.
        <a class="btn btn-sm btn-outline" href="<?= e(url('/business/website')) ?>" style="background:#fff;">Back to settings</a>
    </div>
<?php endif; ?>
<header class="website-header">
    <a class="website-brand" href="<?= e(url('/p/' . ($business['slug'] ?? ''))) ?>">
        <?php if (!empty($business['logo_path'])): ?>
            <img src="<?= e(upload_url($business['logo_path'])) ?>" alt="<?= e($businessName) ?> logo">
        <?php else: ?>
            <span class="website-brand-mark"><?= e(strtoupper(substr($businessName, 0, 1))) ?></span>
        <?php endif; ?>
        <span>
            <strong><?= e($businessName) ?></strong>
            <?php if (!empty($business['category'])): ?><small><?= e($business['category']) ?></small><?php endif; ?>
        </span>
    </a>
    <nav class="website-nav">
        <a href="<?= e(url('/p/' . ($business['slug'] ?? '') . '#about')) ?>">About</a>
        <a href="<?= e(url('/p/' . ($business['slug'] ?? '') . '#listings')) ?>">Products/Services</a>
        <a href="<?= e(url('/p/' . ($business['slug'] ?? '') . '#contact')) ?>">Contact</a>
        <?php if (!empty($business['phone'])): ?><a class="site-btn site-btn-small" href="tel:<?= e($business['phone']) ?>">Call Now</a><?php endif; ?>
    </nav>
</header>

<?php $messages = flash_messages(); if ($messages): ?>
    <div class="website-container" style="padding-top:18px;padding-bottom:0;">
        <div class="alerts">
            <?php foreach ($messages as $message): ?>
                <div class="alert <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<main>
    <?= $content ?>
</main>

<footer class="website-footer">
    <div>
        <strong><?= e($businessName) ?></strong>
        <?php if (!empty($business['tagline'])): ?><p><?= e($business['tagline']) ?></p><?php endif; ?>
    </div>
    <div class="website-footer-links">
        <?php if (!empty($business['phone'])): ?><span><?= e($business['phone']) ?></span><?php endif; ?>
        <?php if (!empty($business['email'])): ?><span><?= e($business['email']) ?></span><?php endif; ?>
        <?php if (!empty($business['city'])): ?><span><?= e($business['city']) ?></span><?php endif; ?>
        <span><a href="<?= e(url('/')) ?>">Business Directory</a></span>
    </div>
</footer>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
