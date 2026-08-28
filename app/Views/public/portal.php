<?php
$primary = valid_hex_color($settings['primary_color'] ?? ($settings['theme_color'] ?? '#2563eb'), '#2563eb');
$cover = $business['cover_path'] ? upload_url($business['cover_path']) : null;
$social = json_decode($business['social_links'] ?: '{}', true) ?: [];
$showAbout = website_section_enabled($settings, 'about');
$showCategories = website_section_enabled($settings, 'categories');
$showFeatured = website_section_enabled($settings, 'featured');
$showListings = website_section_enabled($settings, 'listings');
$showOffers = website_section_enabled($settings, 'offers');
$showContact = website_section_enabled($settings, 'contact') && !empty($settings['allow_public_enquiries']);
$allowOrders = !empty($settings['allow_public_orders']);
$showLocation = website_section_enabled($settings, 'location');
$showSocial = website_section_enabled($settings, 'social_links') && $social;
$pf = $customerPrefill ?? null;
$pv = !empty($preview);
$siteQuery = $pv ? '?preview=1' : '';
if (!function_exists('public_form_val')) {
function public_form_val(string $key, ?array $pf): string {
    $old = old($key);
    if ($old !== '' && $old !== null) { return (string) $old; }
    return (string) ($pf[$key] ?? '');
}
}
$requestOptions = [];
if (!empty($featureAccess['service_management'])) {
    $requestOptions['custom_request'] = 'Custom request';
    if (!empty($featureAccess['service_requests'])) {
        $requestOptions['service_request'] = 'Service request';
    }
    $requestOptions['package_enquiry'] = 'Package enquiry';
}
if (!empty($featureAccess['product_management'])) {
    $requestOptions['product_order'] = 'Product order';
}
if (!empty($featureAccess['service_management']) && !empty($featureAccess['booking_requests'])) {
    $requestOptions['booking_request'] = 'Booking request';
}
?>
<section class="website-hero <?= $cover ? 'with-cover' : '' ?>" style="<?= $cover ? "background-image: linear-gradient(90deg, rgba(15,23,42,.88), rgba(15,23,42,.42)), url('" . e($cover) . "');" : '' ?>">
    <div class="website-hero-inner">
        <div>
            <div class="site-kicker"><?= e($business['category']) ?></div>
            <h1><?= e($business['name']) ?></h1>
            <p><?= e($business['tagline'] ?: ($business['description'] ?: 'Explore our products, services and latest offers.')) ?></p>
            <div class="website-meta-pills">
                <?php if (!empty($business['city'])): ?><span class="website-meta-pill">📍 <?= e($business['city']) ?><?= !empty($business['state']) ? ', ' . e($business['state']) : '' ?></span><?php endif; ?>
                <?php if (!empty($business['phone'])): ?><span class="website-meta-pill">☎ <?= e($business['phone']) ?></span><?php endif; ?>
                <?php if (!empty($business['timings'])): ?><span class="website-meta-pill">🕘 Open timings available</span><?php endif; ?>
            </div>
            <div class="website-cta-row">
                <?php if ($showContact): ?><a class="site-btn secondary" href="#contact">Send Enquiry</a><?php endif; ?>
                <?php if (!empty($business['phone'])): ?><a class="site-btn ghost" href="tel:<?= e($business['phone']) ?>">Call Now</a><?php endif; ?>
                <?php if ($showListings && $listings): ?><a class="site-btn ghost" href="#listings">View Products/Services</a><?php endif; ?>
            </div>
        </div>
        <div class="website-hero-card">
            <h2 style="margin:0 0 12px;">Quick contact</h2>
            <div class="website-info-list">
                <?php if (!empty($business['phone'])): ?><div class="website-info-item"><span>Phone</span><span><?= e($business['phone']) ?></span></div><?php endif; ?>
                <?php if (!empty($business['email'])): ?><div class="website-info-item"><span>Email</span><span><?= e($business['email']) ?></span></div><?php endif; ?>
                <?php if (!empty($business['city'])): ?><div class="website-info-item"><span>Location</span><span><?= e($business['city']) ?></span></div><?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($showAbout && ($business['description'] || $business['address'] || $business['timings'])): ?>
<section id="about" class="website-container">
    <div class="website-about-card">
        <div class="website-section-title">
            <h2>About <?= e($business['name']) ?></h2>
            <?php if ($business['tagline']): ?><p><?= e($business['tagline']) ?></p><?php endif; ?>
        </div>
        <?php if ($business['description']): ?><p style="font-size:18px;max-width:900px;"><?= nl2br(e($business['description'])) ?></p><?php endif; ?>
        <div class="website-info-list" style="margin-top:18px;">
            <?php if ($showLocation && !empty($business['address'])): ?><div class="website-info-item"><span>Address</span><span><?= nl2br(e($business['address'])) ?></span></div><?php endif; ?>
            <?php if (!empty($business['timings'])): ?><div class="website-info-item"><span>Timings</span><span><?= nl2br(e($business['timings'])) ?></span></div><?php endif; ?>
            <?php if (!empty($business['website'])): ?><div class="website-info-item"><span>Website</span><span><a href="<?= e($business['website']) ?>" target="_blank" rel="noopener"><?= e($business['website']) ?></a></span></div><?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($showOffers && $offers): ?>
<section id="offers" class="website-container">
    <div class="website-section-title"><h2>Latest offers</h2><p>Active promotions from <?= e($business['name']) ?>.</p></div>
    <div class="grid grid-3">
        <?php foreach ($offers as $offer): ?>
            <article class="website-offer">
                <span class="badge warning">Offer</span>
                <h3 style="margin:12px 0 8px;"><?= e($offer['title']) ?></h3>
                <?php if ($offer['description']): ?><p><?= e($offer['description']) ?></p><?php endif; ?>
                <strong>
                    <?php if ($offer['discount_type'] === 'percentage'): ?><?= e($offer['discount_value']) ?>% off<?php elseif ($offer['discount_type'] === 'fixed'): ?><?= e(format_money($offer['discount_value'])) ?> off<?php else: ?><?= e($offer['discount_value']) ?><?php endif; ?>
                </strong>
                <?php if ($offer['listing_title']): ?><div class="table-muted" style="margin-top:8px;">On <?= e($offer['listing_title']) ?></div><?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($showCategories && $categories): ?>
<section id="categories" class="website-container">
    <div class="website-section-title"><h2>Categories</h2><p>Browse active categories and related public listings.</p></div>
    <div class="website-category-grid">
        <a class="website-category-card" href="<?= e(url('/p/' . $business['slug'])) ?>" style="border-color:<?= $currentCategory === '' ? e($primary) : 'var(--border)' ?>;">
            <strong>All</strong>
            <p class="table-muted">All products and services</p>
        </a>
        <?php foreach ($categories as $category): ?>
            <a class="website-category-card" href="<?= e(url('/p/' . $business['slug'] . '?category=' . urlencode($category['slug']))) ?>" style="border-color:<?= $currentCategory === $category['slug'] ? e($primary) : 'var(--border)' ?>;">
                <strong><?= e($category['name']) ?></strong>
                <p class="table-muted"><?= (int) $category['public_listing_count'] ?> listing(s)</p>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($showFeatured && $featuredListings): ?>
<section id="featured" class="website-container">
    <div class="website-section-title"><h2>Featured items</h2><p>Highlighted products, services and packages.</p></div>
    <div class="website-card-grid">
        <?php foreach ($featuredListings as $listing): ?>
            <article class="website-card">
                <div class="website-card-image"><?php if ($listing['image_path']): ?><img src="<?= e(upload_url($listing['image_path'])) ?>" alt="<?= e($listing['title']) ?> image"><?php else: ?><?= e(ucfirst($listing['type'])) ?><?php endif; ?></div>
                <div class="website-card-body">
                    <span class="badge info"><?= e($listing['type']) ?></span>
                    <h3><?= e($listing['title']) ?></h3>
                    <p><?= e(text_excerpt($listing['short_description'] ?: $listing['description'], 110)) ?></p>
                    <div class="website-price"><?= $listing['price'] !== null ? e(format_money($listing['price'])) : e($listing['price_label'] ?: 'On request') ?></div>
                    <a class="site-btn site-btn-small" href="<?= e(url('/p/' . $business['slug'] . '/listing/' . $listing['slug'] . $siteQuery)) ?>">View Details</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($showListings && $listings): ?>
<section id="listings" class="website-container">
    <div class="website-section-title"><h2>Products & services</h2><p>Public listings maintained by <?= e($business['name']) ?>.</p></div>
    <div class="website-card-grid">
        <?php foreach ($listings as $listing): ?>
            <article class="website-card">
                <div class="website-card-image"><?php if ($listing['image_path']): ?><img src="<?= e(upload_url($listing['image_path'])) ?>" alt="<?= e($listing['title']) ?> image"><?php else: ?><?= e(ucfirst($listing['type'])) ?><?php endif; ?></div>
                <div class="website-card-body">
                    <div class="actions"><span class="badge info"><?= e($listing['type']) ?></span><?php if (!empty($featureAccess['featured_listings']) && $listing['is_featured']): ?><span class="badge warning">Featured</span><?php endif; ?></div>
                    <h3><?= e($listing['title']) ?></h3>
                    <?php if ($listing['category_name']): ?><div class="table-muted"><?= e($listing['category_name']) ?></div><?php endif; ?>
                    <p><?= e(text_excerpt($listing['short_description'] ?: $listing['description'], 120)) ?></p>
                    <div class="website-price"><?= $listing['price'] !== null ? e(format_money($listing['price'])) : e($listing['price_label'] ?: 'On request') ?></div>
                    <a class="site-btn site-btn-small" href="<?= e(url('/p/' . $business['slug'] . '/listing/' . $listing['slug'] . $siteQuery)) ?>">View Details</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($showContact || ($allowOrders && $requestOptions) || $showLocation || $showSocial): ?>
<section id="contact" class="website-container">
    <div class="website-section-title"><h2>Contact <?= e($business['name']) ?></h2><p>Send an enquiry or use the contact details below.</p></div>
    <?php if ($pf): ?>
        <div class="notice" style="margin-bottom:14px;max-width:760px;"><?= icon('person') ?> &nbsp;Signed in as <strong><?= e($pf['name']) ?></strong> — enquiries and requests you send here are saved to your customer account for tracking.</div>
    <?php else: ?>
        <p class="table-muted" style="margin:0 0 14px;">Have a customer account? <a href="<?= e(url('/customer/login')) ?>">Sign in</a> to save and track your enquiries with this business.</p>
    <?php endif; ?>
    <div class="website-contact-grid">
        <?php if ($showContact): ?>
        <div class="website-about-card">
            <h3 style="margin-top:0;">Send enquiry</h3>
            <form method="post" action="<?= e(url('/p/' . $business['slug'] . '/enquiry')) ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="form-row"><label>Name</label><input type="text" name="name" value="<?= e(public_form_val('name', $pf)) ?>" required></div>
                    <div class="form-row"><label>Phone</label><input type="text" name="phone" value="<?= e(public_form_val('phone', $pf)) ?>" required></div>
                    <div class="form-row"><label>Email</label><input type="email" name="email" value="<?= e(public_form_val('email', $pf)) ?>"></div>
                    <div class="form-row"><label>Requested item</label><input type="text" name="requested_item"></div>
                    <div class="form-row full"><label>Message</label><textarea name="message" required></textarea></div>
                </div>
                <button class="site-btn" type="submit">Submit Enquiry</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($allowOrders && $requestOptions): ?>
        <div class="website-about-card">
            <h3 style="margin-top:0;">Submit order / booking request</h3>
            <form method="post" action="<?= e(url('/p/' . $business['slug'] . '/order')) ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="form-row"><label>Name</label><input type="text" name="name" value="<?= e(public_form_val('name', $pf)) ?>" required></div>
                    <div class="form-row"><label>Phone</label><input type="text" name="phone" value="<?= e(public_form_val('phone', $pf)) ?>" required></div>
                    <div class="form-row"><label>Email</label><input type="email" name="email" value="<?= e(public_form_val('email', $pf)) ?>"></div>
                    <div class="form-row"><label>Quantity / People</label><input type="number" min="1" name="quantity" value="1"></div>
                    <div class="form-row full"><label>Request type</label><select name="request_type"><?php foreach ($requestOptions as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
                    <div class="form-row full"><label>Details</label><textarea name="details" required></textarea></div>
                </div>
                <button class="site-btn" type="submit">Submit Request</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="website-about-card">
            <h3 style="margin-top:0;">Business details</h3>
            <div class="website-info-list">
                <?php if (!empty($business['phone'])): ?><div class="website-info-item"><span>Phone</span><span><?= e($business['phone']) ?></span></div><?php endif; ?>
                <?php if (!empty($business['email'])): ?><div class="website-info-item"><span>Email</span><span><?= e($business['email']) ?></span></div><?php endif; ?>
                <?php if ($showLocation && !empty($business['address'])): ?><div class="website-info-item"><span>Address</span><span><?= nl2br(e($business['address'])) ?></span></div><?php endif; ?>
                <?php if (!empty($business['timings'])): ?><div class="website-info-item"><span>Timings</span><span><?= nl2br(e($business['timings'])) ?></span></div><?php endif; ?>
            </div>
            <?php if ($showSocial): ?>
                <div class="website-cta-row">
                    <?php foreach ($social as $name => $link): ?><a class="site-btn ghost site-btn-small" href="<?= e($link) ?>" target="_blank" rel="noopener"><?= e(ucfirst($name)) ?></a><?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>
