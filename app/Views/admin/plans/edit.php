<?php
$selectedById = [];
foreach ($selectedFeatures as $feature) {
    $selectedById[(int) $feature['id']] = $feature;
}
$oldFeatureIdsRaw = old('feature_ids', null);
$checkedFeatureIds = is_array($oldFeatureIdsRaw) ? array_map('intval', $oldFeatureIdsRaw) : array_keys($selectedById);
$oldLimits = old('feature_limits', []);
$oldLimits = is_array($oldLimits) ? $oldLimits : [];
?>
<section class="page-header">
    <div>
        <div class="page-kicker">Subscription Management</div>
        <h2 class="page-title">Edit configurable plan</h2>
        <p class="page-description">This plan grants access only to selected features. Changes affect all businesses currently assigned to this plan.</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline" href="<?= e(url('/admin/features')) ?>">Feature Registry</a>
        <a class="btn btn-outline" href="<?= e(url('/admin/plans')) ?>">Back to plans</a>
    </div>
</section>

<form method="post" action="<?= e(url('/admin/plans/' . $plan['id'])) ?>" class="grid grid-2">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Plan details</h3><p class="card-subtitle">Pricing is data-driven. Features are stored in the many-to-many plan-feature table.</p></div></div>
        <div class="form-grid">
            <div class="form-row"><label>Name</label><input type="text" name="name" value="<?= e(old('name', $plan['name'])) ?>" required></div>
            <div class="form-row"><label>Default cycle</label><select name="billing_cycle"><?php foreach (['monthly','yearly','custom'] as $cycle): ?><option value="<?= e($cycle) ?>" <?= old('billing_cycle', $plan['billing_cycle']) === $cycle ? 'selected' : '' ?>><?= e(ucfirst($cycle)) ?></option><?php endforeach; ?></select></div>
            <div class="form-row"><label>Monthly price</label><input type="number" step="0.01" min="0" name="monthly_price" value="<?= e(old('monthly_price', $plan['monthly_price'] ?? $plan['price'])) ?>" required></div>
            <div class="form-row"><label>Yearly price</label><input type="number" step="0.01" min="0" name="yearly_price" value="<?= e(old('yearly_price', $plan['yearly_price'] ?? '')) ?>"></div>
            <div class="form-row"><label>Currency</label><input type="text" name="currency" value="<?= e(old('currency', $plan['currency'])) ?>"></div>
            <div class="form-row"><label>Sort order</label><input type="number" name="sort_order" value="<?= e(old('sort_order', $plan['sort_order'])) ?>"></div>
            <div class="form-row full"><label>Description</label><textarea name="description"><?= e(old('description', $plan['description'])) ?></textarea></div>
        </div>
        <label class="checkbox-row"><input type="checkbox" name="is_active" <?= old('is_active', $plan['is_active']) ? 'checked' : '' ?>> Active</label>
        <button class="btn btn-primary" type="submit" style="margin-top:16px;">Save plan</button>
    </div>

    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Plan Features / Included Features</h3><p class="card-subtitle">Check modules included in this plan. Optional limits can be a number or JSON such as <code>{"max_items":50}</code>.</p></div></div>
        <?php foreach ($featureGroups as $category => $features): ?>
            <h3 style="margin:18px 0 10px;"><?= e($category) ?></h3>
            <div class="grid">
                <?php foreach ($features as $feature): ?>
                    <?php
                    $featureId = (int) $feature['id'];
                    $selected = in_array($featureId, $checkedFeatureIds, true);
                    $existingLimit = $oldLimits[$featureId] ?? ($selectedById[$featureId]['limits_json'] ?? '');
                    ?>
                    <div class="card soft" style="padding:14px;">
                        <label class="checkbox-row">
                            <input type="checkbox" name="feature_ids[]" value="<?= $featureId ?>" <?= $selected ? 'checked' : '' ?>>
                            <strong><?= e(($feature['icon'] ? $feature['icon'] . ' ' : '') . $feature['name']) ?></strong>
                        </label>
                        <div class="table-muted" style="margin-top:4px;"><?= e($feature['identifier']) ?> · <?= e($feature['description']) ?></div>
                        <input type="text" name="feature_limits[<?= $featureId ?>]" value="<?= e($existingLimit) ?>" placeholder='Limit e.g. 50 or {"max_items":50}' style="margin-top:10px;">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; if (!$featureGroups): ?>
            <div class="empty-state"><strong>No features registered</strong>Create features in Feature Registry first.</div>
        <?php endif; ?>
    </div>
</form>
