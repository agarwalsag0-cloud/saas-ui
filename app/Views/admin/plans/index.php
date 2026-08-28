<?php
$oldFeatureIdsRaw = old('feature_ids', []);
$oldFeatureIds = is_array($oldFeatureIdsRaw) ? array_map('intval', $oldFeatureIdsRaw) : [];
$oldLimits = old('feature_limits', []);
$oldLimits = is_array($oldLimits) ? $oldLimits : [];
?>
<section class="page-header">
    <div>
        <div class="page-kicker">Configurable Product Packages</div>
        <h2 class="page-title">Subscription plans</h2>
        <p class="page-description">Plans are now built from the centralized feature registry. Super Admin decides exactly which modules and limits each plan receives.</p>
    </div>
    <a class="btn btn-outline" href="<?= e(url('/admin/features')) ?>">Manage Feature Registry</a>
</section>

<div class="grid grid-3">
    <div class="card" style="grid-column: span 1;">
        <div class="card-header"><div><h3 class="card-title">Create plan</h3><p class="card-subtitle">Select features dynamically from the platform feature registry.</p></div></div>
        <form method="post" action="<?= e(url('/admin/plans')) ?>">
            <?= csrf_field() ?>
            <div class="form-row"><label>Name</label><input type="text" name="name" value="<?= e(old('name')) ?>" required></div>
            <div class="form-row"><label>Description</label><textarea name="description"><?= e(old('description')) ?></textarea></div>
            <div class="form-grid">
                <div class="form-row"><label>Default cycle</label><select name="billing_cycle"><option value="monthly">Monthly</option><option value="yearly">Yearly</option><option value="custom">Custom</option></select></div>
                <div class="form-row"><label>Monthly price</label><input type="number" step="0.01" min="0" name="monthly_price" value="<?= e(old('monthly_price', '0')) ?>" required></div>
                <div class="form-row"><label>Yearly price</label><input type="number" step="0.01" min="0" name="yearly_price" value="<?= e(old('yearly_price', '0')) ?>"></div>
                <div class="form-row"><label>Currency</label><input type="text" name="currency" value="<?= e(old('currency', 'INR')) ?>"></div>
                <div class="form-row"><label>Sort order</label><input type="number" name="sort_order" value="<?= e(old('sort_order', '0')) ?>"></div>
            </div>

            <div class="card soft" style="margin:16px 0;">
                <h4 style="margin:0 0 10px;">Plan Features / Included Features</h4>
                <p class="help-text">Optional limit can be a number like <code>50</code> or JSON like <code>{"max_items":50}</code>.</p>
                <?php foreach ($featureGroups as $category => $features): ?>
                    <h5 style="margin:14px 0 8px;"><?= e($category) ?></h5>
                    <?php foreach ($features as $feature): ?>
                        <div style="border:1px solid var(--border);border-radius:12px;padding:10px;margin-bottom:8px;background:#fff;">
                            <label class="checkbox-row">
                                <input type="checkbox" name="feature_ids[]" value="<?= (int) $feature['id'] ?>" <?= in_array((int) $feature['id'], $oldFeatureIds, true) ? 'checked' : '' ?>>
                                <?= e(($feature['icon'] ? $feature['icon'] . ' ' : '') . $feature['name']) ?>
                            </label>
                            <div class="table-muted"><?= e($feature['identifier']) ?></div>
                            <input type="text" name="feature_limits[<?= (int) $feature['id'] ?>]" value="<?= e($oldLimits[$feature['id']] ?? '') ?>" placeholder='Limit e.g. 50 or {"max_items":50}' style="margin-top:8px;">
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; if (!$featureGroups): ?>
                    <div class="empty-state"><strong>No plan features registered</strong>Create features from Feature Registry first.</div>
                <?php endif; ?>
            </div>

            <label class="checkbox-row"><input type="checkbox" name="is_active" checked> Active</label>
            <button class="btn btn-primary btn-block" type="submit" style="margin-top:14px;">Create plan</button>
        </form>
    </div>

    <div class="card" style="grid-column: span 2;">
        <div class="card-header"><div><h3 class="card-title">Existing plans</h3><p class="card-subtitle">Each plan is a configurable feature package. Edit a plan to change its included modules and limits.</p></div></div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Plan</th><th>Pricing</th><th>Features</th><th>Subscriptions</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td><strong><?= e($plan['name']) ?></strong><div class="table-muted"><?= e($plan['description'] ?? '') ?></div></td>
                        <td>
                            <strong><?= e(format_money($plan['monthly_price'] ?? $plan['price'], $plan['currency'])) ?>/mo</strong>
                            <?php if (!empty($plan['yearly_price'])): ?><div class="table-muted"><?= e(format_money($plan['yearly_price'], $plan['currency'])) ?>/yr</div><?php endif; ?>
                            <div class="table-muted">Default: <?= e($plan['billing_cycle']) ?></div>
                        </td>
                        <td><span class="badge info"><?= (int) $plan['feature_count'] ?> feature(s)</span></td>
                        <td><?= (int) $plan['subscription_count'] ?></td>
                        <td><?= $plan['is_active'] ? status_badge('active') : status_badge('inactive') ?></td>
                        <td><div class="actions"><a class="btn btn-sm btn-outline" href="<?= e(url('/admin/plans/' . $plan['id'] . '/edit')) ?>">Edit Features</a><form method="post" action="<?= e(url('/admin/plans/' . $plan['id'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline" type="submit"><?= $plan['is_active'] ? 'Deactivate' : 'Activate' ?></button></form></div></td>
                    </tr>
                <?php endforeach; if (!$plans): ?>
                    <tr><td colspan="6"><div class="empty-state"><strong>No plans yet</strong>Create a plan and select included features.</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
