<section class="customer-hero">
    <div>
        <h1>Welcome back, <?= e($customer['name'] ?? 'there') ?> 👋</h1>
        <p>Browse verified businesses, submit enquiries, and follow every request in one place.</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline" href="<?= e(url('/')) ?>"><?= icon('search') ?> Browse businesses</a>
    </div>
</section>

<div class="stats-grid">
    <div class="metric-card"><span class="metric-label">My enquiries</span><span class="metric-value"><?= (int) $metrics['enquiries'] ?></span><span class="metric-help"><?= (int) $metrics['open_enquiries'] ?> awaiting a response right now</span></div>
    <div class="metric-card"><span class="metric-label">My requests</span><span class="metric-value"><?= (int) $metrics['orders'] ?></span><span class="metric-help"><?= (int) $metrics['open_orders'] ?> currently open</span></div>
    <div class="metric-card"><span class="metric-label">Notifications</span><span class="metric-value"><?= (int) $customerUnreadNotifications ?></span><span class="metric-help"><a href="<?= e(url('/customer/notifications')) ?>">View all</a></span></div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <div><h2 class="card-title">Recent enquiries</h2><p class="card-subtitle">Questions you sent to business websites.</p></div>
            <a class="btn btn-sm btn-outline" href="<?= e(url('/customer/enquiries')) ?>">View all</a>
        </div>
        <?php if ($recentEnquiries): ?>
            <div class="table-responsive" style="border:none;box-shadow:none;">
                <table>
                    <tbody>
                    <?php foreach ($recentEnquiries as $enquiry): ?>
                        <tr>
                            <td>
                                <div class="table-title"><a href="<?= e(url('/customer/enquiries/' . (int) $enquiry['id'])) ?>"><?= e(text_excerpt((string) $enquiry['message'], 60)) ?></a></div>
                                <span class="table-muted"><?= e($enquiry['business_name']) ?> · <?= e(format_date($enquiry['created_at'])) ?></span>
                            </td>
                            <td style="text-align:right;"><?= status_badge((string) $enquiry['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">You have not sent any enquiries yet.<br><a class="btn btn-sm btn-primary" href="<?= e(url('/')) ?>">Find a business</a></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div><h2 class="card-title">Recent requests</h2><p class="card-subtitle">Orders and booking requests with businesses.</p></div>
            <a class="btn btn-sm btn-outline" href="<?= e(url('/customer/orders')) ?>">View all</a>
        </div>
        <?php if ($recentOrders): ?>
            <div class="table-responsive" style="border:none;box-shadow:none;">
                <table>
                    <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>
                                <div class="table-title"><a href="<?= e(url('/customer/orders/' . (int) $order['id'])) ?>"><?= e($order['order_number']) ?></a></div>
                                <span class="table-muted"><?= e($order['business_name']) ?> · <?= e(str_replace('_', ' ', (string) $order['request_type'])) ?></span>
                            </td>
                            <td style="text-align:right;"><?= status_badge((string) $order['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No orders or requests yet.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div><h2 class="card-title">Featured business websites</h2><p class="card-subtitle">Approved, published and currently active on the platform.</p></div>
    </div>
    <div class="listing-grid">
        <?php foreach ($recentBusinesses as $site): ?>
            <article class="listing-card">
                <div class="listing-image"><?php if ($site['cover_path']): ?><img src="<?= e(upload_url($site['cover_path'])) ?>" alt="<?= e($site['name']) ?> cover"><?php else: ?><?= e($site['category']) ?><?php endif; ?></div>
                <div class="listing-body">
                    <h3><?= e($site['name']) ?></h3>
                    <span class="table-muted"><?= e($site['category']) ?><?= !empty($site['city']) ? ' · ' . e($site['city']) : '' ?></span>
                    <p><?= e(text_excerpt((string) ($site['tagline'] ?: 'Visit the website to explore products, services and current offers.'), 110)) ?></p>
                    <a class="btn btn-sm btn-primary" href="<?= e(url('/p/' . $site['slug'])) ?>">Visit website</a>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$recentBusinesses): ?><div class="empty-state" style="grid-column:1/-1;">No published business websites yet. Check back soon.</div><?php endif; ?>
    </div>
</div>
