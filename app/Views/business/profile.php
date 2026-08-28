<?php
$value = fn(string $key, $default = '') => old($key, $business[$key] ?? $default);
$socialLinks = $socialLinks ?? [];
?>
<section class="page-header">
    <div>
        <div class="page-kicker">Business Profile</div>
        <h2 class="page-title">Public portal settings</h2>
        <p class="page-description">Control the public information customers see. Customization is limited to safe branding options.</p>
    </div>
    <a class="btn btn-outline" target="_blank" href="<?= e(url('/p/' . $business['slug'])) ?>">Preview Website</a>
</section>

<form method="post" action="<?= e(url('/business/profile')) ?>" enctype="multipart/form-data" class="grid grid-2">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Identity</h3><p class="card-subtitle">Business details and customer-facing content.</p></div></div>
        <div class="form-grid">
            <div class="form-row"><label>Business name</label><input type="text" name="name" value="<?= e($value('name')) ?>" required></div>
            <div class="form-row"><label>Category</label><input type="text" name="category" value="<?= e($value('category')) ?>" required></div>
            <div class="form-row"><label>Tagline</label><input type="text" name="tagline" value="<?= e($value('tagline')) ?>" placeholder="Short public website headline"></div>
            <div class="form-row"><label>Phone</label><input type="text" name="phone" value="<?= e($value('phone')) ?>"></div>
            <div class="form-row"><label>Email</label><input type="email" name="email" value="<?= e($value('email')) ?>"></div>
            <div class="form-row"><label>City</label><input type="text" name="city" value="<?= e($value('city')) ?>"></div>
            <div class="form-row"><label>State</label><input type="text" name="state" value="<?= e($value('state')) ?>"></div>
            <div class="form-row"><label>Country</label><input type="text" name="country" value="<?= e($value('country', 'India')) ?>"></div>
            <div class="form-row"><label>Website</label><input type="url" name="website" value="<?= e($value('website')) ?>"></div>
            <div class="form-row full"><label>Address</label><textarea name="address"><?= e($value('address')) ?></textarea></div>
            <div class="form-row full"><label>Description</label><textarea name="description"><?= e($value('description')) ?></textarea></div>
            <div class="form-row full"><label>Business timings</label><textarea name="timings" placeholder="Mon-Sat: 10:00 AM - 8:00 PM&#10;Sunday: Closed"><?= e($value('timings')) ?></textarea></div>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <div class="card-header"><div><h3 class="card-title">Branding</h3><p class="card-subtitle">Images are validated server-side and stored per tenant.</p></div></div>
            <div class="form-grid">
                <div class="form-row full">
                    <label>Logo</label>
                    <?php if (!empty($business['logo_path'])): ?><img src="<?= e(upload_url($business['logo_path'])) ?>" alt="Logo" style="width:88px;height:88px;border-radius:18px;object-fit:cover;margin-bottom:10px;"><?php endif; ?>
                    <input type="file" name="logo" accept="image/*">
                </div>
                <div class="form-row full">
                    <label>Cover / banner image</label>
                    <?php if (!empty($business['cover_path'])): ?><img src="<?= e(upload_url($business['cover_path'])) ?>" alt="Cover" style="width:100%;height:140px;border-radius:18px;object-fit:cover;margin-bottom:10px;"><?php endif; ?>
                    <input type="file" name="cover" accept="image/*">
                </div>
                <div class="form-row"><label>Theme color</label><input type="color" name="theme_color" value="<?= e(old('theme_color', $settings['theme_color'] ?? '#2563eb')) ?>"></div>
                <div class="form-row"><label>Accent color</label><input type="color" name="accent_color" value="<?= e(old('accent_color', $settings['accent_color'] ?? '#f97316')) ?>"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div><h3 class="card-title">Social links</h3><p class="card-subtitle">Optional links displayed on public portal.</p></div></div>
            <div class="form-grid">
                <?php foreach (['facebook','instagram','whatsapp','youtube','linkedin'] as $social): ?>
                    <div class="form-row"><label><?= e(ucfirst($social)) ?></label><input type="text" name="<?= e($social) ?>" value="<?= e(old($social, $socialLinks[$social] ?? '')) ?>"></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <button class="btn btn-primary btn-block" type="submit">Save profile</button>
        </div>
    </div>
</form>
