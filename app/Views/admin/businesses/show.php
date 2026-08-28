<?php
$sub = $subscription ?? [];
$planIncludesWebsite = isset($planFeatures['public_website']);
$websiteFeatureIsActive = isset($currentFeatureAccess['public_website']);
if ($websiteFeatureIsActive && !empty($websiteSettings['website_enabled'])) {
    $websiteFeatureStatus = 'Enabled';
    $websiteFeatureBadge = 'active';
} elseif ($websiteFeatureIsActive && empty($websiteSettings['website_enabled'])) {
    $websiteFeatureStatus = 'Admin Disabled';
    $websiteFeatureBadge = 'inactive';
} elseif ($planIncludesWebsite) {
    $websiteFeatureStatus = 'Included but subscription/business restricted';
    $websiteFeatureBadge = 'pending';
} else {
    $websiteFeatureStatus = 'Not Included';
    $websiteFeatureBadge = 'inactive';
}
?>
<section class="page-header">
    <div>
        <div class="page-kicker">Tenant Detail</div>
        <h2 class="page-title"><?= e($business['name']) ?></h2>
        <p class="page-description"><?= e($business['category']) ?> · <?= e($business['city'] ?? '') ?> <?= e($business['state'] ?? '') ?> · Public URL: <strong>/p/<?= e($business['slug']) ?></strong></p>
    </div>
    <div class="actions">
        <a class="btn btn-outline" target="_blank" href="<?= e(url('/p/' . $business['slug'])) ?>">Preview Website</a>
        <a class="btn btn-outline" href="<?= e(url('/admin/businesses/' . $business['id'] . '/edit')) ?>">Edit</a>
        <a class="btn btn-outline" href="<?= e(url('/admin/businesses')) ?>">Back</a>
    </div>
</section>

<div class="grid stats-grid">
    <div class="card metric-card"><div class="metric-label">Listings</div><div class="metric-value"><?= (int) $stats['listings'] ?></div></div>
    <div class="card metric-card"><div class="metric-label">Customers</div><div class="metric-value"><?= (int) $stats['customers'] ?></div></div>
    <div class="card metric-card"><div class="metric-label">Enquiries</div><div class="metric-value"><?= (int) $stats['enquiries'] ?></div></div>
    <div class="card metric-card"><div class="metric-label">Orders</div><div class="metric-value"><?= (int) $stats['orders'] ?></div></div>
</div>

<div class="grid grid-3" style="margin-top:18px;">
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Business status</h3><p class="card-subtitle">Controls tenant access and public portal availability.</p></div></div>
        <div class="detail-list">
            <div class="detail-item"><span>Status</span><span><?= status_badge($business['status']) ?></span></div>
            <div class="detail-item"><span>Current plan</span><span><?= e($sub['plan_name'] ?? 'No plan') ?></span></div>
            <div class="detail-item"><span>Subscription</span><span><?= status_badge($effectiveStatus) ?></span></div>
            <div class="detail-item"><span>Public Website feature</span><span><?= status_badge($websiteFeatureBadge) ?> <?= e($websiteFeatureStatus) ?></span></div>
            <div class="detail-item"><span>Admin website toggle</span><span><?= !empty($websiteSettings['website_enabled']) ? status_badge('active') : status_badge('inactive') ?></span></div>
            <div class="detail-item"><span>Published by owner</span><span><?= !empty($websiteSettings['website_published']) ? status_badge('published') . ' <small class="table-muted">' . e(format_datetime($websiteSettings['website_published_at'] ?? null)) . '</small>' : status_badge('unpublished') ?></span></div>
            <div class="detail-item"><span>Directory visible</span><span><?= !empty($websiteSettings['show_in_directory']) ? status_badge('active') : status_badge('inactive') ?></span></div>
            <div class="detail-item"><span>Owner</span><span><?= e($owner['name'] ?? '—') ?></span></div>
            <div class="detail-item"><span>Owner email</span><span><?= e($owner['email'] ?? '—') ?></span></div>
            <div class="detail-item"><span>Created</span><span><?= e(format_date($business['created_at'])) ?></span></div>
        </div>
        <?php if (!empty($business['submitted_for_review_at'])): ?>
            <div class="notice" style="margin-top:14px;"><?= icon('flag') ?> &nbsp;Submitted for review <?= e(format_datetime($business['submitted_for_review_at'])) ?><?= !empty($business['review_note']) ? '<br><span class="table-muted">Last review note: ' . e($business['review_note']) . '</span>' : '' ?></div>
        <?php endif; ?>
        <div class="actions" style="margin-top:14px;">
            <form method="post" action="<?= e(url('/admin/businesses/' . $business['id'] . '/status')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="status" value="approved"><button class="btn btn-success btn-sm" type="submit">✓ Approve</button></form>
            <form method="post" action="<?= e(url('/admin/businesses/' . $business['id'] . '/status')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="status" value="changes_requested"><button class="btn btn-warning btn-sm" type="submit">Request changes</button></form>
            <form method="post" action="<?= e(url('/admin/businesses/' . $business['id'] . '/status')) ?>" data-confirm="Reject this business registration?" class="inline-form"><?= csrf_field() ?><input type="hidden" name="status" value="rejected"><button class="btn btn-danger btn-sm" type="submit">✕ Reject</button></form>
            <a class="btn btn-outline btn-sm" target="_blank" href="<?= e(url('/admin/businesses/' . (int) $business['id'] . '/preview')) ?>"><?= icon('visibility') ?> Preview website</a>
        </div>
        <form method="post" action="<?= e(url('/admin/businesses/' . $business['id'] . '/status')) ?>" style="margin-top:14px;">
            <?= csrf_field() ?>
            <div class="form-row">
                <label>Advanced status transition &amp; review note</label>
                <div class="filter-bar" style="margin:0;">
                    <select name="status">
                        <?php foreach (['pending','under_review','changes_requested','approved','active','inactive','suspended','rejected'] as $statusOption): ?>
                            <option value="<?= e($statusOption) ?>" <?= $business['status'] === $statusOption ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $statusOption))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="note" maxlength="1000" placeholder="Optional note sent to the owner (required context for rejections / changes)" style="flex:1;">
                    <button class="btn btn-primary" type="submit">Update Status</button>
                </div>
            </div>
        </form>
        <form method="post" action="<?= e(url('/admin/businesses/' . $business['id'] . '/website')) ?>" class="inline-form" style="margin-top:10px;">
            <?= csrf_field() ?>
            <input type="hidden" name="website_enabled" value="<?= !empty($websiteSettings['website_enabled']) ? '0' : '1' ?>">
            <button class="btn btn-outline" type="submit"><?= !empty($websiteSettings['website_enabled']) ? 'Disable Website' : 'Enable Website' ?></button>
        </form>
        <div class="help-text" style="margin-top:8px;">This toggle never overrides subscription features. If the plan does not include Public Business Website, the public website stays locked.</div>
        <form method="post" action="<?= e(url('/admin/businesses/' . $business['id'] . '/archive')) ?>" style="margin-top:10px;">
            <?= csrf_field() ?>
            <button class="btn btn-danger btn-sm" data-confirm="Archive this business? It will be hidden from normal lists." type="submit">Archive business</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Subscription</h3><p class="card-subtitle">Manual control; gateway integration can update the same tables later.</p></div></div>
        <form method="post" action="<?= e(url('/admin/businesses/' . $business['id'] . '/subscription')) ?>">
            <?= csrf_field() ?>
            <div class="form-row"><label>Plan</label><select name="plan_id" required><?php foreach ($plans as $plan): ?><option value="<?= (int) $plan['id'] ?>" <?= (($sub['plan_id'] ?? null) == $plan['id']) ? 'selected' : '' ?>><?= e($plan['name']) ?> · <?= e(format_money($plan['monthly_price'] ?? $plan['price'], $plan['currency'])) ?>/mo<?= !empty($plan['yearly_price']) ? ' · ' . e(format_money($plan['yearly_price'], $plan['currency'])) . '/yr' : '' ?></option><?php endforeach; ?></select></div>
            <div class="form-grid">
                <div class="form-row"><label>Status</label><select name="subscription_status"><?php foreach (['pending','active','expired','suspended','cancelled'] as $s): ?><option value="<?= e($s) ?>" <?= (($sub['status'] ?? 'active') === $s) ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
                <div class="form-row"><label>Renewal</label><select name="renewal_status"><?php foreach (['manual','pending','renewed','cancelled'] as $s): ?><option value="<?= e($s) ?>" <?= (($sub['renewal_status'] ?? 'manual') === $s) ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
                <div class="form-row"><label>Start</label><input type="date" name="starts_at" value="<?= e(isset($sub['starts_at']) ? substr($sub['starts_at'], 0, 10) : date('Y-m-d')) ?>" required></div>
                <div class="form-row"><label>Expiry</label><input type="date" name="expires_at" value="<?= e(isset($sub['expires_at']) ? substr($sub['expires_at'], 0, 10) : date('Y-m-d', strtotime('+30 days'))) ?>" required></div>
                <div class="form-row full"><label>Grace ends</label><input type="date" name="grace_ends_at" value="<?= e(isset($sub['grace_ends_at']) ? substr((string) $sub['grace_ends_at'], 0, 10) : '') ?>"></div>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Save subscription</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Record payment</h3><p class="card-subtitle">Tracks cash, UPI, bank transfer, cheque or future gateway payments.</p></div></div>
        <form method="post" action="<?= e(url('/admin/businesses/' . $business['id'] . '/payments')) ?>">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-row"><label>Amount</label><input type="number" step="0.01" min="0" name="amount" required></div>
                <div class="form-row"><label>Currency</label><input type="text" name="currency" value="INR" required></div>
                <div class="form-row"><label>Date</label><input type="date" name="payment_date" value="<?= e(date('Y-m-d')) ?>" required></div>
                <div class="form-row"><label>Status</label><select name="payment_status"><?php foreach (['paid','pending','failed','refunded','cancelled'] as $s): ?><option value="<?= e($s) ?>"><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
                <div class="form-row"><label>Method</label><input type="text" name="method" placeholder="Cash, UPI, Bank transfer" required></div>
                <div class="form-row"><label>Reference</label><input type="text" name="reference" placeholder="Txn/receipt number"></div>
                <div class="form-row"><label>Plan for renewal</label><select name="payment_plan_id"><option value="0">Do not change subscription</option><?php foreach ($plans as $plan): ?><option value="<?= (int) $plan['id'] ?>"><?= e($plan['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-row"><label>Period start</label><input type="date" name="period_start"></div>
                <div class="form-row"><label>Period end</label><input type="date" name="period_end"></div>
                <div class="form-row"><label>Grace ends</label><input type="date" name="payment_grace_ends_at"></div>
                <div class="form-row full"><label>Notes</label><textarea name="notes" placeholder="Internal payment notes"></textarea></div>
            </div>
            <button class="btn btn-success btn-block" type="submit">Record payment</button>
        </form>
    </div>
</div>

<div class="card" style="margin-top:18px;">
    <div class="card-header"><div><h3 class="card-title">Plan features and current access</h3><p class="card-subtitle">Feature access is calculated from business status + subscription status + selected plan features.</p></div></div>
    <?php if (!empty($planFeatures)): ?>
        <div class="grid grid-3">
            <?php foreach ($planFeatures as $identifier => $feature): ?>
                <div class="card soft">
                    <div class="actions" style="justify-content:space-between;"><strong><?= e(($feature['icon'] ? $feature['icon'] . ' ' : '') . $feature['name']) ?></strong><?= isset($currentFeatureAccess[$identifier]) ? status_badge('active') : status_badge('inactive') ?></div>
                    <div class="table-muted"><?= e($identifier) ?></div>
                    <?php if (!empty($feature['limits'])): ?><div class="table-muted">Limits: <?= e(json_encode($feature['limits'], JSON_UNESCAPED_SLASHES)) ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state"><strong>No features assigned</strong>Edit the subscription plan and select included features.</div>
    <?php endif; ?>
</div>

<div class="grid grid-2" style="margin-top:18px;">
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Payment history</h3><p class="card-subtitle">Latest payments for this tenant.</p></div></div>
        <div class="table-responsive"><table><thead><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Status</th><th>Reference</th></tr></thead><tbody>
            <?php foreach ($payments as $payment): ?>
                <tr><td><?= e(format_date($payment['payment_date'])) ?></td><td><?= e($payment['plan_name'] ?? '—') ?></td><td><?= e(format_money($payment['amount'], $payment['currency'])) ?></td><td><?= status_badge($payment['status']) ?></td><td><?= e($payment['reference'] ?? '—') ?></td></tr>
            <?php endforeach; if (!$payments): ?><tr><td colspan="5"><div class="empty-state"><strong>No payments recorded</strong>Record manual payments from the form above.</div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Recent activity</h3><p class="card-subtitle">Important admin and tenant actions.</p></div></div>
        <div class="table-responsive"><table><thead><tr><th>Action</th><th>Subject</th><th>When</th></tr></thead><tbody>
            <?php foreach ($activityLogs as $log): ?>
                <tr><td><?= e(str_replace('_', ' ', $log['action'])) ?></td><td><?= e(($log['subject_type'] ?? '—') . ($log['subject_id'] ? ' #' . $log['subject_id'] : '')) ?></td><td><?= e(time_ago($log['created_at'])) ?></td></tr>
            <?php endforeach; if (!$activityLogs): ?><tr><td colspan="3"><div class="empty-state"><strong>No activity yet</strong></div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>

<div class="grid grid-3" style="margin-top:18px;">
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Recent listings</h3><p class="card-subtitle">Tenant catalog preview</p></div></div>
        <div class="table-responsive"><table><thead><tr><th>Listing</th><th>Type</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($recentBusinessListings as $listing): ?>
                <tr><td><?= e($listing['title']) ?><div class="table-muted"><?= $listing['price'] !== null ? e(format_money($listing['price'])) : '—' ?></div></td><td><?= e($listing['type']) ?></td><td><?= status_badge($listing['status']) ?></td></tr>
            <?php endforeach; if (!$recentBusinessListings): ?><tr><td colspan="3"><div class="empty-state"><strong>No listings</strong></div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Recent enquiries</h3><p class="card-subtitle">Customer enquiries for this tenant</p></div></div>
        <div class="table-responsive"><table><thead><tr><th>Customer</th><th>Requested</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($recentBusinessEnquiries as $enquiry): ?>
                <tr><td><?= e($enquiry['name']) ?><div class="table-muted"><?= e($enquiry['phone']) ?></div></td><td><?= e($enquiry['requested_item'] ?? 'General') ?></td><td><?= status_badge($enquiry['status']) ?></td></tr>
            <?php endforeach; if (!$recentBusinessEnquiries): ?><tr><td colspan="3"><div class="empty-state"><strong>No enquiries</strong></div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Recent orders</h3><p class="card-subtitle">Orders/requests for this tenant</p></div></div>
        <div class="table-responsive"><table><thead><tr><th>Ref</th><th>Customer</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($recentBusinessOrders as $order): ?>
                <tr><td><?= e($order['order_number']) ?><div class="table-muted"><?= $order['total_amount'] !== null ? e(format_money($order['total_amount'])) : '—' ?></div></td><td><?= e($order['customer_name']) ?></td><td><?= status_badge($order['status']) ?></td></tr>
            <?php endforeach; if (!$recentBusinessOrders): ?><tr><td colspan="3"><div class="empty-state"><strong>No orders</strong></div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>
