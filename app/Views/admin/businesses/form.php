<?php
$isEdit = $mode === 'edit';
$value = fn(string $key, $default = '') => old($key, $business[$key] ?? $default);
$action = $isEdit ? url('/admin/businesses/' . $business['id']) : url('/admin/businesses');
?>
<section class="page-header">
    <div>
        <div class="page-kicker"><?= $isEdit ? 'Edit Tenant' : 'New Tenant' ?></div>
        <h2 class="page-title"><?= $isEdit ? 'Edit business' : 'Add a business' ?></h2>
        <p class="page-description">Create a tenant-agnostic business account. Specific business features can be added later without changing the core platform.</p>
    </div>
    <a class="btn btn-outline" href="<?= e(url('/admin/businesses')) ?>">Back to businesses</a>
</section>

<form method="post" action="<?= e($action) ?>" class="grid grid-2">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Business information</h3><p class="card-subtitle">Public and administrative tenant details.</p></div></div>
        <div class="form-grid">
            <div class="form-row"><label>Name *</label><input type="text" name="name" value="<?= e($value('name')) ?>" required></div>
            <div class="form-row"><label>Slug</label><input type="text" name="slug" value="<?= e($value('slug')) ?>" placeholder="unique-public-slug"><div class="help-text">Used in /p/business-slug. Leave blank to generate.</div></div>
            <div class="form-row"><label>Category *</label><input type="text" name="category" value="<?= e($value('category')) ?>" placeholder="Travel, Electronics, Salon..." required></div>
            <div class="form-row"><label>Tagline</label><input type="text" name="tagline" value="<?= e($value('tagline')) ?>" placeholder="Short website headline"></div>
            <div class="form-row"><label>Status</label><select name="status"><?php foreach (['pending','approved','active','inactive','suspended','rejected','archived'] as $statusOption): if (!$isEdit && $statusOption === 'archived') continue; ?><option value="<?= e($statusOption) ?>" <?= $value('status', 'pending') === $statusOption ? 'selected' : '' ?>><?= e(ucfirst($statusOption)) ?></option><?php endforeach; ?></select></div>
            <div class="form-row"><label>Phone</label><input type="text" name="phone" value="<?= e($value('phone')) ?>"></div>
            <div class="form-row"><label>Business email</label><input type="email" name="email" value="<?= e($value('email')) ?>"></div>
            <div class="form-row"><label>City</label><input type="text" name="city" value="<?= e($value('city')) ?>"></div>
            <div class="form-row"><label>State</label><input type="text" name="state" value="<?= e($value('state')) ?>"></div>
            <div class="form-row"><label>Country</label><input type="text" name="country" value="<?= e($value('country', 'India')) ?>"></div>
            <div class="form-row"><label>Website</label><input type="url" name="website" value="<?= e($value('website')) ?>"></div>
            <div class="form-row full"><label>Address</label><textarea name="address"><?= e($value('address')) ?></textarea></div>
            <div class="form-row full"><label>Description</label><textarea name="description"><?= e($value('description')) ?></textarea></div>
        </div>
    </div>

    <div class="grid">
        <?php if (!$isEdit): ?>
        <div class="card">
            <div class="card-header"><div><h3 class="card-title">Owner login</h3><p class="card-subtitle">Creates the first business_owner user for this tenant.</p></div></div>
            <div class="form-grid">
                <div class="form-row"><label>Owner name *</label><input type="text" name="owner_name" value="<?= e(old('owner_name')) ?>" required></div>
                <div class="form-row"><label>Owner phone</label><input type="text" name="owner_phone" value="<?= e(old('owner_phone')) ?>"></div>
                <div class="form-row"><label>Owner email *</label><input type="email" name="owner_email" value="<?= e(old('owner_email')) ?>" required></div>
                <div class="form-row"><label>Temporary password *</label><input type="password" name="owner_password" required><div class="help-text">Minimum 8 characters. Owner can be asked to change manually later.</div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div><h3 class="card-title">Initial subscription</h3><p class="card-subtitle">Optional. You can also assign it later from business details.</p></div></div>
            <div class="form-grid">
                <div class="form-row"><label>Plan</label><select name="plan_id"><option value="0">No plan now</option><?php foreach ($plans as $plan): ?><option value="<?= (int) $plan['id'] ?>"><?= e($plan['name']) ?> · <?= e(format_money($plan['monthly_price'] ?? $plan['price'], $plan['currency'])) ?>/mo<?= !empty($plan['yearly_price']) ? ' · ' . e(format_money($plan['yearly_price'], $plan['currency'])) . '/yr' : '' ?></option><?php endforeach; ?></select></div>
                <div class="form-row"><label>Subscription status</label><select name="subscription_status"><?php foreach (['active','pending','expired','suspended'] as $s): ?><option value="<?= e($s) ?>"><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
                <div class="form-row"><label>Starts at</label><input type="date" name="starts_at" value="<?= e(date('Y-m-d')) ?>"></div>
                <div class="form-row"><label>Expires at</label><input type="date" name="expires_at" value="<?= e(date('Y-m-d', strtotime('+30 days'))) ?>"></div>
                <div class="form-row full"><label>Grace ends at</label><input type="date" name="grace_ends_at"><div class="help-text">Optional temporary access date after expiry.</div></div>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header"><div><h3 class="card-title">Edit note</h3><p class="card-subtitle">Owner login, payments and subscriptions are managed from the detail page.</p></div></div>
            <a class="btn btn-outline" href="<?= e(url('/admin/businesses/' . $business['id'])) ?>">Open business detail</a>
        </div>
        <?php endif; ?>

        <div class="card">
            <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'Save changes' : 'Create business' ?></button>
        </div>
    </div>
</form>
