<section class="page-header">
    <div>
        <div class="page-kicker">Subscription</div>
        <h2 class="page-title">Plan and payment status</h2>
        <p class="page-description">Current subscription controls portal access. Payments are recorded by the platform admin in this initial version.</p>
    </div>
</section>

<div class="grid grid-3">
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Current status</h3><p class="card-subtitle">Access is calculated from business status, expiry and grace period.</p></div></div>
        <div class="detail-list">
            <div class="detail-item"><span>Business status</span><span><?= status_badge($business['status']) ?></span></div>
            <div class="detail-item"><span>Effective status</span><span><?= status_badge($effectiveSubscriptionStatus) ?></span></div>
            <div class="detail-item"><span>Plan</span><span><?= e($subscription['plan_name'] ?? 'No plan') ?></span></div>
            <div class="detail-item"><span>Started</span><span><?= e(format_date($subscription['starts_at'] ?? null)) ?></span></div>
            <div class="detail-item"><span>Expires</span><span><?= e(format_date($subscription['expires_at'] ?? null)) ?></span></div>
            <div class="detail-item"><span>Grace ends</span><span><?= e(format_date($subscription['grace_ends_at'] ?? null)) ?></span></div>
        </div>
        <?php if (!$portalAllowed): ?><div class="alert warning" style="margin-top:16px;">Portal write access is restricted. Please contact the platform admin for renewal or activation.</div><?php endif; ?>
    </div>
    <div class="card" style="grid-column: span 2;">
        <div class="card-header"><div><h3 class="card-title">Included features in current plan</h3><p class="card-subtitle">Access is based on active subscription status plus these plan features.</p></div></div>
        <?php if (!empty($currentFeatures)): ?>
            <div class="grid grid-3">
                <?php foreach ($currentFeatures as $feature): ?>
                    <div class="card soft">
                        <strong><?= e(($feature['icon'] ? $feature['icon'] . ' ' : '') . $feature['name']) ?></strong>
                        <div class="table-muted"><?= e($feature['category']) ?></div>
                        <?php if (!empty($feature['limits'])): ?><div class="table-muted">Limits: <?= e(json_encode($feature['limits'], JSON_UNESCAPED_SLASHES)) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><strong>No features assigned</strong>Please contact the platform admin to configure your plan.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-top:18px;">
        <div class="card-header"><div><h3 class="card-title">Available plans</h3><p class="card-subtitle">Plans are data-driven and can be changed by Super Admin.</p></div></div>
        <div class="grid grid-3">
            <?php foreach ($plans as $plan): ?>
                <?php $planFeatures = $featuresByPlan[(int) $plan['id']] ?? []; ?>
                <div class="card soft">
                    <h4 style="margin:0 0 6px;"><?= e($plan['name']) ?></h4>
                    <div class="price"><?= e(format_money($plan['monthly_price'] ?? $plan['price'], $plan['currency'])) ?>/mo</div>
                    <?php if (!empty($plan['yearly_price'])): ?><div class="table-muted"><?= e(format_money($plan['yearly_price'], $plan['currency'])) ?>/yr</div><?php endif; ?>
                    <div style="margin:10px 0;"><span class="badge <?= isset($planFeatures['public_website']) ? 'success' : 'muted' ?>">Public Website: <?= isset($planFeatures['public_website']) ? 'Included' : 'Not Included' ?></span></div>
                    <p><?= e($plan['description']) ?></p>
                    <?php if ($planFeatures): ?>
                        <div class="actions" style="gap:6px;">
                            <?php foreach (array_slice($planFeatures, 0, 6) as $feature): ?><span class="badge info"><?= e($feature['name']) ?></span><?php endforeach; ?>
                            <?php if (count($planFeatures) > 6): ?><span class="badge muted">+<?= count($planFeatures) - 6 ?> more</span><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="help-text" style="margin-top:12px;">Contact the platform admin to activate or upgrade this plan.</div>
                </div>
            <?php endforeach; if (!$plans): ?><div class="empty-state"><strong>No public plans</strong></div><?php endif; ?>
        </div>
    </div>

<div class="card" style="margin-top:18px;">
    <div class="card-header"><div><h3 class="card-title">Payment history</h3><p class="card-subtitle">Manual payments now; online gateways can be integrated later using gateway columns.</p></div></div>
    <div class="table-responsive"><table><thead><tr><th>Date</th><th>Plan</th><th>Period</th><th>Amount</th><th>Status</th><th>Reference</th></tr></thead><tbody>
        <?php foreach ($payments as $payment): ?>
            <tr><td><?= e(format_date($payment['payment_date'])) ?></td><td><?= e($payment['plan_name'] ?? '—') ?></td><td><?= e(format_date($payment['period_start'])) ?> - <?= e(format_date($payment['period_end'])) ?></td><td><?= e(format_money($payment['amount'], $payment['currency'])) ?></td><td><?= status_badge($payment['status']) ?></td><td><?= e($payment['reference'] ?? '—') ?></td></tr>
        <?php endforeach; if (!$payments): ?><tr><td colspan="6"><div class="empty-state"><strong>No payments yet</strong></div></td></tr><?php endif; ?>
    </tbody></table></div>
</div>
