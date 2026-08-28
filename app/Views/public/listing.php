<?php
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

$specs = json_decode($listing['specifications'] ?: '[]', true) ?: [];
$allowEnquiries = !empty($settings['allow_public_enquiries']);
$allowOrders = !empty($settings['allow_public_orders']);
$requestOptions = [];
if (($listing['type'] ?? '') === 'product' && !empty($featureAccess['product_management'])) {
    $requestOptions['product_order'] = 'Product order';
}
if (($listing['type'] ?? '') !== 'product' && !empty($featureAccess['service_management'])) {
    if (!empty($featureAccess['service_requests'])) {
        $requestOptions['service_request'] = 'Service request';
    }
    if (($listing['type'] ?? '') === 'package') {
        $requestOptions['package_enquiry'] = 'Package enquiry';
    }
    if (($listing['type'] ?? '') === 'booking' && !empty($featureAccess['booking_requests'])) {
        $requestOptions['booking_request'] = 'Booking request';
    }
    $requestOptions['custom_request'] = 'Custom request';
}
?>
<section class="website-container">
    <a class="site-btn ghost site-btn-small" href="<?= e(url('/p/' . $business['slug'])) ?>">← Back to Website</a>
    <div class="grid grid-2" style="margin-top:22px;align-items:start;">
        <div class="website-card">
            <?php if ($listing['image_path']): ?>
                <img src="<?= e(upload_url($listing['image_path'])) ?>" alt="<?= e($listing['title']) ?> image" style="width:100%;max-height:520px;object-fit:cover;">
            <?php else: ?>
                <div class="website-card-image" style="height:380px;">No image</div>
            <?php endif; ?>
        </div>
        <div class="website-about-card">
            <div class="actions"><span class="badge info"><?= e($listing['type']) ?></span><?php if (!empty($featureAccess['featured_listings']) && $listing['is_featured']): ?><span class="badge warning">Featured</span><?php endif; ?></div>
            <h1 class="page-title"><?= e($listing['title']) ?></h1>
            <?php if ($listing['short_description']): ?><p class="page-description"><?= e($listing['short_description']) ?></p><?php endif; ?>
            <div class="website-price" style="margin:16px 0;"><?= $listing['price'] !== null ? e(format_money($listing['price'])) : e($listing['price_label'] ?: 'On request') ?></div>
            <?php if ($listing['description']): ?><p><?= nl2br(e($listing['description'])) ?></p><?php endif; ?>
            <?php if ($specs): ?>
                <h3>Specifications / Details</h3>
                <div class="website-info-list">
                    <?php foreach ($specs as $key => $value): ?>
                        <div class="website-info-item"><span><?= e(is_string($key) ? $key : 'Detail') ?></span><span><?= e(is_scalar($value) ? (string) $value : json_encode($value)) ?></span></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="website-cta-row">
                <?php if ($allowEnquiries): ?><a class="site-btn" href="#enquiry">Enquire Now</a><?php endif; ?>
                <?php if ($allowOrders && $requestOptions): ?><a class="site-btn ghost" href="#request">Submit Request</a><?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($allowEnquiries || ($allowOrders && $requestOptions)): ?>
<section class="website-container">
    <div class="website-contact-grid">
        <?php if ($allowEnquiries): ?>
        <div id="enquiry" class="website-about-card">
            <div class="website-section-title"><h2>Enquire about this</h2><p>Send your details directly to <?= e($business['name']) ?>.</p></div>
        <?php if ($pf): ?>
            <div class="notice" style="margin-bottom:14px;"><?= icon('person') ?> &nbsp;Signed in as <strong><?= e($pf['name']) ?></strong> — this request will be saved to your customer account so you can track it from the portal.</div>
        <?php else: ?>
            <p class="table-muted" style="margin:0 0 12px;">Have a customer account? <a href="<?= e(url('/customer/login')) ?>">Sign in</a> to save and track this request.</p>
        <?php endif; ?>
            <form method="post" action="<?= e(url('/p/' . $business['slug'] . '/enquiry')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="listing_id" value="<?= (int) $listing['id'] ?>">
                <div class="form-grid">
                    <div class="form-row"><label>Name</label><input type="text" name="name" value="<?= e(public_form_val('name', $pf)) ?>" required></div>
                    <div class="form-row"><label>Phone</label><input type="text" name="phone" value="<?= e(public_form_val('phone', $pf)) ?>" required></div>
                    <div class="form-row full"><label>Email</label><input type="email" name="email" value="<?= e(public_form_val('email', $pf)) ?>"></div>
                    <div class="form-row full"><label>Message</label><textarea name="message" required>I am interested in <?= e($listing['title']) ?>.</textarea></div>
                </div>
                <button class="site-btn" type="submit">Send Enquiry</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($allowOrders && $requestOptions): ?>
        <div id="request" class="website-about-card">
            <div class="website-section-title"><h2>Submit order/request</h2><p>Use this for product orders, services, bookings or custom requests.</p></div>
            <form method="post" action="<?= e(url('/p/' . $business['slug'] . '/order')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="listing_id" value="<?= (int) $listing['id'] ?>">
                <div class="form-grid">
                    <div class="form-row"><label>Name</label><input type="text" name="name" value="<?= e(public_form_val('name', $pf)) ?>" required></div>
                    <div class="form-row"><label>Phone</label><input type="text" name="phone" value="<?= e(public_form_val('phone', $pf)) ?>" required></div>
                    <div class="form-row"><label>Email</label><input type="email" name="email" value="<?= e(public_form_val('email', $pf)) ?>"></div>
                    <div class="form-row"><label>Quantity</label><input type="number" min="1" name="quantity" value="1"></div>
                    <div class="form-row full"><label>Request type</label><select name="request_type"><?php foreach ($requestOptions as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
                    <div class="form-row full"><label>Details</label><textarea name="details" required>I would like to request <?= e($listing['title']) ?>.</textarea></div>
                </div>
                <button class="site-btn" type="submit">Submit Request</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($relatedListings): ?>
<section class="website-container">
    <div class="website-section-title"><h2>More from <?= e($business['name']) ?></h2></div>
    <div class="website-card-grid">
        <?php foreach ($relatedListings as $related): ?>
            <article class="website-card">
                <div class="website-card-image"><?php if ($related['image_path']): ?><img src="<?= e(upload_url($related['image_path'])) ?>" alt="<?= e($related['title']) ?> image"><?php else: ?><?= e(ucfirst($related['type'])) ?><?php endif; ?></div>
                <div class="website-card-body">
                    <h3><?= e($related['title']) ?></h3>
                    <p><?= e(text_excerpt($related['short_description'] ?? '', 90)) ?></p>
                    <div class="website-price"><?= $related['price'] !== null ? e(format_money($related['price'])) : e($related['price_label'] ?: 'On request') ?></div>
                    <a class="site-btn site-btn-small" href="<?= e(url('/p/' . $business['slug'] . '/listing/' . $related['slug'])) ?>">View Details</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
