<section class="page-header">
    <div>
        <div class="page-kicker">Business Portal</div>
        <h2 class="page-title">Welcome, <?= e($business['name']) ?></h2>
        <p class="page-description">Manage your listings, enquiries, orders and automatic public business website. Tenant data is scoped to this business only.</p>
    </div>
    <div class="actions">
        <?php if (isset($featureAccess['product_management']) || isset($featureAccess['service_management'])): ?><a class="btn btn-primary" href="<?= e(url('/business/listings/create')) ?>">+ Add listing</a><?php endif; ?>
        <?php if (isset($featureAccess['public_website'])): ?>
<div class="card " style="margin-bottom:18px;">
    <div class="card-header" style="margin-bottom:8px;">
        <div>
            <h3 class="card-title" style="font-size:22px;"><?= icon('public') ?> &nbsp;Your public business website</h3>
            <p style="margin:6px 0 0;color:var(--on-surface-variant);">
                <?php if (!empty($websiteAccess['public_access'])): ?>
                    Your website is live at <code>/p/<?= e($business['slug']) ?></code>. Configuration edits are saved instantly, but visitors only see them after you Publish.
                <?php elseif (empty($websiteAccess['enabled_by_admin'])): ?>
                    Your website content is saved, but the platform admin has currently switched public access off.
                <?php elseif (!empty($websiteAccess['published']) && empty($websiteAccess['business_approved'])): ?>
                    Your website is published but stays private until the platform approves your business.
                <?php else: ?>
                    Your website configuration is ready. Preview it any time, then Publish when you want it visible to customers (approval required).
                <?php endif; ?>
            </p>
        </div>
        <div class="actions">
            <?php if (!empty($websiteAccess['public_access'])): ?><?= status_badge('published') ?><?php else: ?><?= status_badge('unpublished') ?><?php endif; ?>
            <?php if (empty($websiteAccess['enabled_by_admin'])): ?><?= status_badge('website_disabled') ?><?php endif; ?>
        </div>
    </div>
    <div class="actions" style="margin-top:14px;">
        <a class="btn btn-outline" target="_blank" href="<?= e(url('/business/website/preview')) ?>"><?= icon('visibility') ?> Preview</a>
        <?php if (!empty($websiteAccess['public_access'])): ?>
            <a class="btn btn-primary" target="_blank" href="<?= e($websitePath) ?>">View live website</a>
            <button class="btn btn-outline" type="button" data-copy-url="<?= e($websitePath) ?>">Copy link</button>
        <?php endif; ?>
        <a class="btn <?= !empty($websiteAccess['public_access']) ? 'btn-outline' : 'btn-primary' ?>" href="<?= e(url('/business/website')) ?>"><?= isset($featureAccess['website_customization']) ? 'Customize & publish' : 'Website settings' ?></a>
    </div>
</div>
<?php endif; ?>

<div class="grid stats-grid">
    <?php if (isset($featureAccess['categories'])): ?><div class="card metric-card"><div class="metric-label">Categories</div><div class="metric-value"><?= (int) $metrics['categories'] ?></div></div><?php endif; ?>
    <?php if (isset($featureAccess['product_management']) || isset($featureAccess['service_management'])): ?><div class="card metric-card"><div class="metric-label">Listings</div><div class="metric-value"><?= (int) $metrics['listings'] ?></div><div class="metric-help"><?= (int) $metrics['active_listings'] ?> active</div></div><?php endif; ?>
    <?php if (isset($featureAccess['enquiries'])): ?><div class="card metric-card"><div class="metric-label">Enquiries</div><div class="metric-value"><?= (int) $metrics['enquiries'] ?></div><div class="metric-help"><?= (int) $metrics['new_enquiries'] ?> new</div></div><?php endif; ?>
    <?php if (isset($featureAccess['orders'])): ?><div class="card metric-card"><div class="metric-label">Orders/Requests</div><div class="metric-value"><?= (int) $metrics['orders'] ?></div><div class="metric-help"><?= (int) $metrics['open_orders'] ?> open</div></div><?php endif; ?>
    <?php if (isset($featureAccess['offers'])): ?><div class="card metric-card"><div class="metric-label">Active offers</div><div class="metric-value"><?= (int) $metrics['offers'] ?></div></div><?php endif; ?>
    <?php if (isset($featureAccess['orders'])): ?><div class="card metric-card"><div class="metric-label">Estimated revenue</div><div class="metric-value"><?= e(format_money($metrics['revenue'])) ?></div><div class="metric-help">From confirmed/completed requests</div></div><?php endif; ?>
    <div class="card metric-card"><div class="metric-label">Subscription</div><div class="metric-value" style="font-size:24px;"><?= e(str_replace('_', ' ', ucfirst($effectiveSubscriptionStatus))) ?></div><div class="metric-help">Expires <?= e(format_date($subscription['expires_at'] ?? null)) ?></div></div>
    <?php if (isset($featureAccess['public_website'])): ?><div class="card metric-card"><div class="metric-label">Public URL</div><div class="metric-value" style="font-size:18px;">/p/<?= e($business['slug']) ?></div><div class="metric-help">Share with customers</div></div><?php endif; ?>
</div>

<?php if (isset($featureAccess['enquiries']) || isset($featureAccess['orders'])): ?>
<div class="grid grid-2" style="margin-top:18px;">
    <?php if (isset($featureAccess['enquiries'])): ?>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Recent enquiries</h3><p class="card-subtitle">Latest customer conversations</p></div><a class="btn btn-sm btn-outline" href="<?= e(url('/business/enquiries')) ?>">View all</a></div>
        <div class="table-responsive"><table><thead><tr><th>Customer</th><th>Requested</th><th>Status</th><th>When</th></tr></thead><tbody>
            <?php foreach ($recentEnquiries as $enquiry): ?>
                <tr><td><?= e($enquiry['name']) ?><div class="table-muted"><?= e($enquiry['phone']) ?></div></td><td><?= e($enquiry['requested_item'] ?? 'General') ?></td><td><?= status_badge($enquiry['status']) ?></td><td><?= e(time_ago($enquiry['created_at'])) ?></td></tr>
            <?php endforeach; if (!$recentEnquiries): ?><tr><td colspan="4"><div class="empty-state"><strong>No enquiries yet</strong>Your public portal enquiry form will populate this table.</div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
    <?php endif; ?>
    <?php if (isset($featureAccess['orders'])): ?>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Recent orders/requests</h3><p class="card-subtitle">Flexible product, service or booking requests</p></div><a class="btn btn-sm btn-outline" href="<?= e(url('/business/orders')) ?>">View all</a></div>
        <div class="table-responsive"><table><thead><tr><th>Ref</th><th>Customer</th><th>Status</th><th>Amount</th></tr></thead><tbody>
            <?php foreach ($recentOrders as $order): ?>
                <tr><td><?= e($order['order_number']) ?></td><td><?= e($order['customer_name']) ?><div class="table-muted"><?= e($order['phone']) ?></div></td><td><?= status_badge($order['status']) ?></td><td><?= $order['total_amount'] !== null ? e(format_money($order['total_amount'])) : '—' ?></td></tr>
            <?php endforeach; if (!$recentOrders): ?><tr><td colspan="4"><div class="empty-state"><strong>No requests yet</strong>Customers can request products, services, bookings or custom work.</div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (isset($featureAccess['notifications']) || isset($featureAccess['activity_tracking'])): ?>
<div class="grid grid-2" style="margin-top:18px;">
    <?php if (isset($featureAccess['notifications'])): ?>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Notifications</h3><p class="card-subtitle">Important events for your business</p></div></div>
        <div class="detail-list">
            <?php foreach ($notifications as $notification): ?>
                <div class="detail-item"><span><strong><?= e($notification['title']) ?></strong><br><small><?= e($notification['body']) ?></small></span><span><?= e(time_ago($notification['created_at'])) ?></span></div>
            <?php endforeach; if (!$notifications): ?><div class="empty-state"><strong>No notifications</strong></div><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (isset($featureAccess['activity_tracking'])): ?>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Recent activity</h3><p class="card-subtitle">Actions scoped to this tenant</p></div></div>
        <div class="table-responsive"><table><thead><tr><th>Action</th><th>When</th></tr></thead><tbody>
            <?php foreach ($activityLogs as $log): ?><tr><td><?= e(str_replace('_', ' ', $log['action'])) ?></td><td><?= e(time_ago($log['created_at'])) ?></td></tr><?php endforeach; if (!$activityLogs): ?><tr><td colspan="2"><div class="empty-state"><strong>No activity yet</strong></div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
