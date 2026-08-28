<?php
$isLimit = ($reason ?? '') === 'limit_reached';
$title = $isLimit ? 'Plan limit reached' : (($featureName ?? 'Feature') . ' is not included');
?>
<div style="max-width:900px;margin:10px auto 40px;">
    <section class="feature-lock">
        <div class="feature-lock-copy">
            <div class="page-kicker">Subscription gating</div>
            <h1 class="page-title"><?= e($title) ?></h1>
            <p class="page-description">
                <?php if ($isLimit): ?>
                    <?= e($limitMessage ?? 'You have reached the usage limit for this feature in your current plan.') ?>
                    Upgrade for higher limits — your existing data stays intact.
                <?php else: ?>
                    <strong><?= e($featureName ?? 'This feature') ?></strong> is enforced by the backend: the module, its pages and its API actions stay locked for this tenant until your active subscription plan includes it.
                    Saved content and configuration are preserved and unlock automatically if you upgrade back.
                <?php endif; ?>
            </p>
            <div class="actions" style="margin-top:6px;">
                <a class="btn btn-primary" href="<?= e(url('/business/subscription')) ?>"><?= icon('bolt') ?> View plans &amp; upgrade</a>
                <a class="btn btn-outline" href="<?= e(url('/business')) ?>">Back to dashboard</a>
            </div>
            <p class="help-text">Need a custom package? Ask the platform team — plans and features are fully configurable by the Super Admin.</p>
        </div>
        <div class="feature-lock-art" aria-hidden="true">
            <div class="lock-disc"><?= $isLimit ? '📈' : '🔒' ?></div>
        </div>
    </section>

    <?php if (!empty($plans)): ?>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Plans that may unlock this</h3><p class="card-subtitle">Feature availability is read live from the platform feature registry.</p></div></div>
        <div class="plan-list">
            <?php foreach ($plans as $plan): ?>
                <div class="plan-row">
                    <div>
                        <strong><?= e($plan['name']) ?></strong>
                        <div class="table-muted"><?= e(text_excerpt((string) ($plan['description'] ?? ''), 120)) ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div class="price"><?= e(format_money($plan['monthly_price'] ?? $plan['price'], $plan['currency'])) ?><small class="table-muted">/mo</small></div>
                        <?php if (!empty($plan['yearly_price'])): ?><div class="table-muted">or <?= e(format_money($plan['yearly_price'], $plan['currency'])) ?>/yr</div><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
