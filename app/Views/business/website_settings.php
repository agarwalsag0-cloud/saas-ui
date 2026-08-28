<?php
$value = fn(string $key, $default = '') => old($key, $business[$key] ?? $default);
$setting = fn(string $key, $default = '') => old($key, $settings[$key] ?? $default);
$primary = valid_hex_color($setting('primary_color', $settings['theme_color'] ?? '#2563eb'), '#2563eb');
$secondary = valid_hex_color($setting('secondary_color', '#0f172a'), '#0f172a');
$accent = valid_hex_color($setting('accent_color', '#f97316'), '#f97316');
$canWebsiteCustomization = isset($featureAccess['website_customization']);
$canCustomBranding = isset($featureAccess['custom_branding']);
$canSeo = isset($featureAccess['basic_seo']);
$canEnquiries = isset($featureAccess['enquiries']);
$canOrders = isset($featureAccess['orders']);
?>
<?php
$websiteAccess = $websiteAccess ?? [];
$isLive = !empty($websiteAccess['public_access']);
$isEnabledByAdmin = ($websiteAccess['enabled_by_admin'] ?? true) !== false;
?>
<section class="page-header">
    <div>
        <div class="page-kicker">Public Business Website</div>
        <h2 class="page-title">Website Builder &amp; Publishing</h2>
        <p class="page-description">Your website content comes from your profile, listings, categories and offers. Edits save to your configuration — the public site changes only when you <strong>Publish</strong>.</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline" target="_blank" href="<?= e(url('/business/website/preview')) ?>"><?= icon('visibility') ?> Preview</a>
        <?php if ($isLive): ?>
            <a class="btn btn-primary" target="_blank" href="<?= e($websitePath) ?>"><?= icon('public') ?> View Live Website</a>
            <button class="btn btn-outline" type="button" data-copy-url="<?= e($websitePath) ?>">Copy Website Link</button>
        <?php endif; ?>
    </div>
</section>

<div class="card">
    <div class="card-header">
        <div><h3 class="card-title">Publishing status</h3><p class="card-subtitle">Website configuration, preview, business approval, publishing, directory visibility and search indexing are separate states.</p></div>
        <div class="actions">
            <?php if ($isLive): ?>
                <form method="post" action="<?= e(url('/business/website/unpublish')) ?>" data-confirm="Unpublishing immediately hides your public website from visitors. Continue?">
                    <?= csrf_field() ?>
                    <button class="btn btn-warning btn-sm" type="submit">Unpublish</button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e(url('/business/website/publish')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-primary btn-sm" type="submit" <?= ($isEnabledByAdmin && !empty($websiteAccess['portal_usable']) && !empty($websiteAccess['business_approved'])) ? '' : 'disabled title="Publishing requires an approved business and an active subscription"' ?>><?= icon('public') ?> Publish Website</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="section-status">
        <div class="status-line"><span><?= icon('palette') ?> &nbsp;Website configured</span><?= !empty($websiteAccess['configured']) ? status_badge('active') : status_badge('pending') ?></div>
        <div class="status-line"><span><?= icon('visibility') ?> &nbsp;Private preview available</span><?= !empty($websiteAccess['preview_available']) ? status_badge('active') : status_badge('no_subscription') ?></div>
        <div class="status-line"><span><?= icon('check') ?> &nbsp;Business approved by platform</span><?= !empty($websiteAccess['business_approved']) ? status_badge('approved') : status_badge(str_replace('_', ' ', (string)($business['status'] ?? 'pending'))) ?></div>
        <div class="status-line"><span><?= icon('public') ?> &nbsp;Published publicly</span><?= !empty($websiteAccess['published']) ? status_badge('published') : status_badge('unpublished') ?><?= $isLive ? '<code style="margin-left:10px;font-size:11px;">' . e($websitePath) . '</code>' : '' ?></div>
        <div class="status-line"><span><?= icon('search') ?> &nbsp;Visible in directory</span><?= !empty($websiteAccess['directory_visible']) ? status_badge('active') : status_badge('muted') ?></div>
        <div class="status-line"><span>🔎 &nbsp;Search-indexing eligible</span><?= !empty($websiteAccess['indexing_eligible']) ? status_badge('success') : status_badge('muted') ?></div>
        <?php if (in_array((string)($business['status'] ?? ''), ['pending','under_review','changes_requested'], true)): ?>
        <div class="status-line">
            <span><?= icon('flag') ?> &nbsp;Review status: <strong><?= e(ucwords(str_replace('_', ' ', (string)$business['status']))) ?></strong><?= !empty($business['review_note']) ? '<br><small class="table-muted">' . e($business['review_note']) . '</small>' : '' ?></span>
            <?php if (in_array((string)$business['status'], ['pending','changes_requested'], true)): ?>
            <form method="post" action="<?= e(url('/business/submit-review')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-primary" type="submit">Submit for Review</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isEnabledByAdmin): ?>
    <div class="alert warning"><strong>Website disabled by Super Admin.</strong>&nbsp; Your public page stays offline even when published, until the platform re-enables it.</div>
<?php endif; ?>
<?php if (empty($websiteAccess['portal_usable'])): ?>
    <div class="alert danger"><strong>Subscription restricted.</strong>&nbsp; Publishing changes require an active subscription. <a href="<?= e(url('/business/subscription')) ?>">View subscription</a></div>
<?php endif; ?>

<form method="post" action="<?= e(url('/business/website')) ?>" enctype="multipart/form-data" class="grid grid-2">
    <?= csrf_field() ?>

    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Website branding</h3><p class="card-subtitle">These details appear on your public website header, hero, about and contact sections.</p></div></div>
        <div class="form-grid">
            <div class="form-row"><label>Business name</label><input type="text" name="name" value="<?= e($value('name')) ?>" required></div>
            <div class="form-row"><label>Category</label><input type="text" name="category" value="<?= e($value('category')) ?>" required></div>
            <div class="form-row full"><label>Tagline</label><input type="text" name="tagline" maxlength="255" value="<?= e($value('tagline')) ?>" placeholder="A short headline for your website hero"></div>
            <div class="form-row full"><label>About section</label><textarea name="description" placeholder="Describe your business for website visitors"><?= e($value('description')) ?></textarea></div>
            <div class="form-row"><label>Phone</label><input type="text" name="phone" value="<?= e($value('phone')) ?>"></div>
            <div class="form-row"><label>Email</label><input type="email" name="email" value="<?= e($value('email')) ?>"></div>
            <div class="form-row"><label>City</label><input type="text" name="city" value="<?= e($value('city')) ?>"></div>
            <div class="form-row"><label>State</label><input type="text" name="state" value="<?= e($value('state')) ?>"></div>
            <div class="form-row"><label>Country</label><input type="text" name="country" value="<?= e($value('country', 'India')) ?>"></div>
            <div class="form-row"><label>Website</label><input type="url" name="website" value="<?= e($value('website')) ?>"></div>
            <div class="form-row full"><label>Address</label><textarea name="address"><?= e($value('address')) ?></textarea></div>
            <div class="form-row full"><label>Business timings</label><textarea name="timings"><?= e($value('timings')) ?></textarea></div>
            <?php if ($canCustomBranding): ?>
            <div class="form-row full">
                <label>Logo</label>
                <?php if (!empty($business['logo_path'])): ?><img src="<?= e(upload_url($business['logo_path'])) ?>" alt="Logo" style="width:88px;height:88px;border-radius:18px;object-fit:cover;margin-bottom:10px;"><?php endif; ?>
                <input type="file" name="logo" accept="image/*">
            </div>
            <div class="form-row full">
                <label>Cover / hero image</label>
                <?php if (!empty($business['cover_path'])): ?><img src="<?= e(upload_url($business['cover_path'])) ?>" alt="Cover" style="width:100%;height:150px;border-radius:18px;object-fit:cover;margin-bottom:10px;"><?php endif; ?>
                <input type="file" name="cover" accept="image/*">
            </div>
            <?php else: ?>
            <div class="form-row full"><div class="alert info">Custom logo and cover image uploads are not included in this plan.</div></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid">
        <?php if ($canWebsiteCustomization): ?>
        <div class="card">
            <div class="card-header"><div><h3 class="card-title">Theme preset</h3><p class="card-subtitle">Choose a professional website style, then customize colors if needed.</p></div></div>
            <div class="grid grid-2">
                <?php foreach ($presets as $key => $preset): ?>
                    <label class="card soft" style="cursor:pointer;border-color:<?= $setting('theme_preset', 'modern') === $key ? e($preset['primary_color']) : 'var(--border)' ?>;">
                        <input type="radio" name="theme_preset" value="<?= e($key) ?>" data-theme-preset='<?= e(json_encode($preset)) ?>' <?= $setting('theme_preset', 'modern') === $key ? 'checked' : '' ?>>
                        <strong><?= e($preset['label']) ?></strong>
                        <span class="table-muted" style="display:block;">Layout: <?= e($preset['layout_style']) ?></span>
                        <span style="display:flex;gap:6px;margin-top:10px;">
                            <span style="width:24px;height:24px;border-radius:50%;background:<?= e($preset['primary_color']) ?>;"></span>
                            <span style="width:24px;height:24px;border-radius:50%;background:<?= e($preset['secondary_color']) ?>;"></span>
                            <span style="width:24px;height:24px;border-radius:50%;background:<?= e($preset['accent_color']) ?>;"></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div><h3 class="card-title">Appearance controls</h3><p class="card-subtitle">Safe settings only; text colors are calculated for readable contrast.</p></div></div>
            <div class="form-grid">
                <div class="form-row"><label>Primary color</label><input data-theme-field="primary_color" type="color" name="primary_color" value="<?= e($primary) ?>"></div>
                <div class="form-row"><label>Secondary color</label><input data-theme-field="secondary_color" type="color" name="secondary_color" value="<?= e($secondary) ?>"></div>
                <div class="form-row"><label>Accent color</label><input data-theme-field="accent_color" type="color" name="accent_color" value="<?= e($accent) ?>"></div>
                <div class="form-row"><label>Layout style</label><select data-theme-field="layout_style" name="layout_style"><?php foreach (['classic','showcase','compact'] as $option): ?><option value="<?= e($option) ?>" <?= $setting('layout_style', 'classic') === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option><?php endforeach; ?></select></div>
                <div class="form-row"><label>Background style</label><select data-theme-field="background_style" name="background_style"><?php foreach (['light','soft','gradient','dark'] as $option): ?><option value="<?= e($option) ?>" <?= $setting('background_style', 'light') === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option><?php endforeach; ?></select></div>
                <div class="form-row"><label>Button style</label><select data-theme-field="button_style" name="button_style"><?php foreach (['rounded','pill','square'] as $option): ?><option value="<?= e($option) ?>" <?= $setting('button_style', 'rounded') === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option><?php endforeach; ?></select></div>
                <div class="form-row full"><label>Text style</label><select data-theme-field="text_style" name="text_style"><?php foreach (['system','serif','modern'] as $option): ?><option value="<?= e($option) ?>" <?= $setting('text_style', 'system') === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option><?php endforeach; ?></select></div>
            </div>
        </div>
        <?php else: ?>
        <div class="card"><div class="empty-state"><strong>Website customization not included</strong>Your current plan keeps the saved/default theme. Upgrade to change presets, layout, colors, buttons and typography.</div></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><div><h3 class="card-title">Public visibility</h3><p class="card-subtitle">Turn website sections and visitor actions on/off.</p></div></div>
            <div class="form-grid">
                <label class="checkbox-row"><input type="checkbox" name="show_in_directory" <?= $setting('show_in_directory', 1) ? 'checked' : '' ?>> Show in platform directory</label>
                <label class="checkbox-row"><input type="checkbox" name="allow_public_enquiries" <?= $setting('allow_public_enquiries', 1) ? 'checked' : '' ?> <?= $canEnquiries ? '' : 'disabled' ?>> Allow enquiries<?= $canEnquiries ? '' : ' (not included)' ?></label>
                <label class="checkbox-row"><input type="checkbox" name="allow_public_orders" <?= $setting('allow_public_orders', 1) ? 'checked' : '' ?> <?= $canOrders ? '' : 'disabled' ?>> Allow orders / booking requests<?= $canOrders ? '' : ' (not included)' ?></label>
                <?php foreach ($sectionVisibility as $section => $enabled): ?>
                    <?php
                    $sectionAllowed = !(
                        ($section === 'categories' && !isset($featureAccess['categories'])) ||
                        ($section === 'featured' && !isset($featureAccess['featured_listings'])) ||
                        ($section === 'listings' && !isset($featureAccess['product_management']) && !isset($featureAccess['service_management'])) ||
                        ($section === 'offers' && !isset($featureAccess['offers']))
                    );
                    ?>
                    <label class="checkbox-row"><input type="checkbox" name="sections[<?= e($section) ?>]" <?= $enabled ? 'checked' : '' ?> <?= $sectionAllowed ? '' : 'disabled' ?>> Show <?= e(str_replace('_', ' ', ucwords($section, '_'))) ?> section<?= $sectionAllowed ? '' : ' (not included)' ?></label>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($canSeo): ?>
        <div class="card">
            <div class="card-header"><div><h3 class="card-title">SEO basics</h3><p class="card-subtitle">Natural, readable SEO text for your public website.</p></div></div>
            <div class="form-row"><label>SEO page title</label><input type="text" name="seo_title" maxlength="190" value="<?= e($setting('seo_title')) ?>" placeholder="Defaults to business name"></div>
            <div class="form-row"><label>SEO meta description</label><textarea name="seo_description" maxlength="300" placeholder="Short natural description for search engines"><?= e($setting('seo_description')) ?></textarea></div>
            <label class="checkbox-row full"><input type="checkbox" name="allow_indexing" <?= $setting('allow_indexing', 1) ? 'checked' : '' ?>> Allow search engines to index this website (canonical + sitemap). Unpublished, preview and inactive pages are always noindex.</label>
            <p class="help-text full">SEO-friendly structure, titles, descriptions and canonical URLs help search engines understand your pages — they cannot guarantee rankings or indexing.</p>
        </div>
        <?php else: ?>
        <div class="card"><div class="empty-state"><strong>SEO settings not included</strong>Upgrade to edit custom page titles and meta descriptions.</div></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><div><h3 class="card-title">Social links</h3><p class="card-subtitle">Displayed only if Social Links section is enabled.</p></div></div>
            <div class="form-grid">
                <?php foreach (['facebook','instagram','whatsapp','youtube','linkedin'] as $social): ?>
                    <div class="form-row"><label><?= e(ucfirst($social)) ?></label><input type="text" name="<?= e($social) ?>" value="<?= e(old($social, $socialLinks[$social] ?? '')) ?>"></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card"><button class="btn btn-primary btn-block" type="submit">Save Website Settings</button></div>
    </div>
</form>
