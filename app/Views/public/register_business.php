<section class="public-section">
    <div class="page-header">
        <div>
            <div class="page-kicker">Business Registration</div>
            <h1 class="page-title">Register your business</h1>
            <p class="page-description">Your owner login will be created now. Choose a preferred plan if available; Super Admin must approve and activate the subscription before paid features unlock.</p>
        </div>
        <a class="btn btn-outline" href="<?= e(url('/login')) ?>">Already registered? Login</a>
    </div>

    <form method="post" action="<?= e(url('/register-business')) ?>" class="grid grid-2">
        <?= csrf_field() ?>
        <div class="card">
            <div class="card-header"><div><h2 class="card-title">Business details</h2><p class="card-subtitle">This becomes the tenant profile after approval.</p></div></div>
            <div class="form-grid">
                <div class="form-row"><label>Business name *</label><input type="text" name="business_name" value="<?= e(old('business_name')) ?>" required></div>
                <div class="form-row"><label>Category *</label><input type="text" name="category" value="<?= e(old('category')) ?>" placeholder="Travel, Electronics, Restaurant..." required></div>
                <div class="form-row"><label>Tagline</label><input type="text" name="tagline" value="<?= e(old('tagline')) ?>" placeholder="Short website headline"></div>
                <div class="form-row"><label>Phone</label><input type="text" name="phone" value="<?= e(old('phone')) ?>"></div>
                <div class="form-row"><label>Business email</label><input type="email" name="business_email" value="<?= e(old('business_email')) ?>"></div>
                <div class="form-row"><label>City</label><input type="text" name="city" value="<?= e(old('city')) ?>"></div>
                <div class="form-row"><label>State</label><input type="text" name="state" value="<?= e(old('state')) ?>"></div>
                <div class="form-row"><label>Country</label><input type="text" name="country" value="<?= e(old('country', 'India')) ?>"></div>
                <div class="form-row"><label>Website</label><input type="url" name="website" value="<?= e(old('website')) ?>"></div>
                <div class="form-row full"><label>Address</label><textarea name="address"><?= e(old('address')) ?></textarea></div>
                <div class="form-row full"><label>Description</label><textarea name="description"><?= e(old('description')) ?></textarea></div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <div class="card-header"><div><h2 class="card-title">Owner login</h2><p class="card-subtitle">These credentials belong to this business owner only.</p></div></div>
                <div class="form-grid">
                    <div class="form-row"><label>Owner name *</label><input type="text" name="owner_name" value="<?= e(old('owner_name')) ?>" required></div>
                    <div class="form-row"><label>Owner phone</label><input type="text" name="owner_phone" value="<?= e(old('owner_phone')) ?>"></div>
                    <div class="form-row full"><label>Owner email *</label><input type="email" name="owner_email" value="<?= e(old('owner_email')) ?>" required></div>
                    <div class="form-row"><label>Password *</label><input type="password" name="owner_password" minlength="8" required></div>
                    <div class="form-row"><label>Confirm password *</label><input type="password" name="owner_password_confirmation" minlength="8" required></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><div><h2 class="card-title">Preferred subscription plan</h2><p class="card-subtitle">Your selected plan controls which features unlock after approval and activation.</p></div></div>
                <?php if (!empty($plans)): ?>
                    <label class="card soft" style="cursor:pointer;margin-bottom:10px;">
                        <input type="radio" name="plan_id" value="0" <?= (int) old('plan_id', 0) === 0 ? 'checked' : '' ?>>
                        <strong>Decide later / ask admin</strong>
                        <div class="table-muted">No website or paid module is unlocked until a valid active subscription is assigned.</div>
                    </label>
                    <?php foreach ($plans as $plan): ?>
                        <label class="card soft" style="cursor:pointer;margin-bottom:10px;">
                            <input type="radio" name="plan_id" value="<?= (int) $plan['id'] ?>" <?= (int) old('plan_id', 0) === (int) $plan['id'] ? 'checked' : '' ?>>
                            <strong><?= e($plan['name']) ?></strong>
                            <div class="price" style="font-size:20px;"><?= e(format_money($plan['monthly_price'] ?? $plan['price'], $plan['currency'])) ?>/mo</div>
                            <?php if (!empty($plan['yearly_price'])): ?><div class="table-muted"><?= e(format_money($plan['yearly_price'], $plan['currency'])) ?>/yr</div><?php endif; ?>
                            <div style="margin-top:8px;">
                                <span class="badge <?= !empty($plan['includes_public_website']) ? 'success' : 'muted' ?>">Public Website: <?= !empty($plan['includes_public_website']) ? 'Included' : 'Not Included' ?></span>
                                <span class="badge info"><?= (int) $plan['feature_count'] ?> feature(s)</span>
                            </div>
                            <?php if (!empty($plan['description'])): ?><p class="help-text"><?= e($plan['description']) ?></p><?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state"><strong>No plans are published yet</strong>The Super Admin can assign a subscription after reviewing your registration.</div>
                <?php endif; ?>
            </div>
            <div class="card soft">
                <strong>Approval flow</strong>
                <p class="help-text">After registration, the business status is <b>pending</b>. Website management remains locked unless the activated plan includes <b>Public Business Website</b>.</p>
            </div>
            <div class="card"><button class="btn btn-primary btn-block" type="submit">Submit registration</button></div>
        </div>
    </form>
</section>
