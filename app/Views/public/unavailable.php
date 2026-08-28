<?php
$statusLabel = str_replace('_', ' ', (string) ($effectiveStatus ?? 'unavailable'));
$message = 'This business website is currently unavailable.';
if (($effectiveStatus ?? '') === 'unpublished') {
    $message = 'This website is configured but its owner has not published it yet. New visitors cannot see it until publishing is enabled from the business dashboard.';
} elseif (($effectiveStatus ?? '') === 'feature_not_included') {
    $message = 'The public website is not included in this business\'s current subscription plan.';
} elseif (($effectiveStatus ?? '') === 'website_disabled') {
    $message = 'This public website has been disabled by the platform administrator.';
} elseif (($effectiveStatus ?? '') === 'expired') {
    $message = 'This public website is unavailable because the subscription has expired.';
} elseif (($effectiveStatus ?? '') === 'suspended') {
    $message = 'This public website is unavailable because the business is currently suspended.';
} elseif (($effectiveStatus ?? '') === 'pending') {
    $message = 'This public website is unavailable while the business is awaiting approval or subscription activation.';
}
?>
<section class="website-container">
    <div class="website-about-card" style="text-align:center;max-width:760px;margin:60px auto;">
        <span class="badge warning">Website unavailable</span>
        <h1 class="page-title"><?= e($business['name']) ?></h1>
        <p class="page-description" style="margin:0 auto 18px;"><?= e($message) ?></p>
        <p class="table-muted">Current state: <?= e($statusLabel) ?>. Public website data is preserved and can become available again when the subscription and platform rules allow it.</p>
    </div>
</section>
