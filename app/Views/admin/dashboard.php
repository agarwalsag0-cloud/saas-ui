<section class="page-header">
    <div>
        <div class="page-kicker">Platform Overview</div>
        <h2 class="page-title">Central SaaS dashboard</h2>
        <p class="page-description">Monitor tenants, subscription health, revenue, orders and enquiries across the full platform.</p>
    </div>
    <div class="actions">
        <a class="btn btn-primary" href="<?= e(url('/admin/businesses/create')) ?>">+ Add business</a>
        <a class="btn btn-outline" href="<?= e(url('/admin/plans')) ?>">Manage plans</a>
    </div>
</section>

<div class="grid stats-grid">
    <div class="card metric-card"><div class="metric-label">Total businesses</div><div class="metric-value"><?= (int) $metrics['total_businesses'] ?></div><div class="metric-help">Non-archived tenants</div></div>
    <div class="card metric-card"><div class="metric-label">Active businesses</div><div class="metric-value"><?= (int) $metrics['active_businesses'] ?></div><div class="metric-help">Approved or active</div></div>
    <div class="card metric-card"><div class="metric-label">Pending</div><div class="metric-value"><?= (int) $metrics['pending_businesses'] ?></div><div class="metric-help">Awaiting approval</div></div>
    <div class="card metric-card"><div class="metric-label">Suspended</div><div class="metric-value"><?= (int) $metrics['suspended_businesses'] ?></div><div class="metric-help">Restricted tenants</div></div>
    <div class="card metric-card"><div class="metric-label">Expired subscriptions</div><div class="metric-value"><?= (int) $metrics['expired_subscriptions'] ?></div><div class="metric-help">Past expiry and grace</div></div>
    <div class="card metric-card"><div class="metric-label">MRR</div><div class="metric-value"><?= e(format_money($metrics['monthly_recurring_revenue'])) ?></div><div class="metric-help">Active monthly plans</div></div>
    <div class="card metric-card"><div class="metric-label">Year revenue</div><div class="metric-value"><?= e(format_money($metrics['yearly_revenue'])) ?></div><div class="metric-help">Paid payments this year</div></div>
    <div class="card metric-card"><div class="metric-label">Pending payments</div><div class="metric-value"><?= e(format_money($metrics['pending_payments'])) ?></div><div class="metric-help">Manual payment follow-up</div></div>
</div>

<div class="grid grid-2" style="margin-top:18px;">
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Revenue trend</h3><p class="card-subtitle">Paid payments grouped by month</p></div></div>
        <?php $maxRevenue = max(array_map(fn($r) => (float) $r['total'], $monthlyRevenue ?: [['total'=>1]])); ?>
        <div class="chart-bars">
            <?php if ($monthlyRevenue): foreach ($monthlyRevenue as $row): $width = $maxRevenue > 0 ? ((float) $row['total'] / $maxRevenue) * 100 : 0; ?>
                <div class="chart-row"><span><?= e($row['label']) ?></span><div class="bar-track"><div class="bar-fill" style="width:<?= (float) $width ?>%"></div></div><strong><?= e(format_money($row['total'])) ?></strong></div>
            <?php endforeach; else: ?>
                <div class="empty-state"><strong>No revenue yet</strong>Record subscription payments to see revenue charts.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Subscription distribution</h3><p class="card-subtitle">Current subscription records by status</p></div></div>
        <?php $maxSubs = max(array_map(fn($r) => (int) $r['total'], $subscriptionStats ?: [['total'=>1]])); ?>
        <div class="chart-bars">
            <?php if ($subscriptionStats): foreach ($subscriptionStats as $row): $width = $maxSubs > 0 ? ((int) $row['total'] / $maxSubs) * 100 : 0; ?>
                <div class="chart-row"><span><?= e($row['status']) ?></span><div class="bar-track"><div class="bar-fill" style="width:<?= (float) $width ?>%"></div></div><strong><?= (int) $row['total'] ?></strong></div>
            <?php endforeach; else: ?>
                <div class="empty-state"><strong>No subscriptions</strong>Create plans and assign subscriptions to businesses.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-2" style="margin-top:18px;">
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Recent registrations</h3><p class="card-subtitle">Latest businesses added to the platform</p></div><a class="btn btn-sm btn-outline" href="<?= e(url('/admin/businesses')) ?>">View all</a></div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Business</th><th>Category</th><th>Status</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ($recentBusinesses as $business): ?>
                    <tr>
                        <td><a class="table-title" href="<?= e(url('/admin/businesses/' . $business['id'])) ?>"><?= e($business['name']) ?></a><div class="table-muted">/p/<?= e($business['slug']) ?></div></td>
                        <td><?= e($business['category']) ?></td>
                        <td><?= status_badge($business['status']) ?></td>
                        <td><?= e(time_ago($business['created_at'])) ?></td>
                    </tr>
                <?php endforeach; if (!$recentBusinesses): ?>
                    <tr><td colspan="4"><div class="empty-state"><strong>No businesses yet</strong>Add the first tenant to begin.</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Business growth</h3><p class="card-subtitle">Registrations grouped by month</p></div></div>
        <?php $maxGrowth = max(array_map(fn($r) => (int) $r['total'], $growth ?: [['total'=>1]])); ?>
        <div class="chart-bars">
            <?php foreach ($growth as $row): $width = $maxGrowth > 0 ? ((int) $row['total'] / $maxGrowth) * 100 : 0; ?>
                <div class="chart-row"><span><?= e($row['label']) ?></span><div class="bar-track"><div class="bar-fill" style="width:<?= (float) $width ?>%"></div></div><strong><?= (int) $row['total'] ?></strong></div>
            <?php endforeach; if (!$growth): ?>
                <div class="empty-state"><strong>No growth data</strong>Business registrations will appear here.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-2" style="margin-top:18px;">
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Recent enquiries</h3><p class="card-subtitle">Across all tenants</p></div></div>
        <div class="table-responsive"><table><thead><tr><th>Customer</th><th>Business</th><th>Status</th><th>When</th></tr></thead><tbody>
            <?php foreach ($recentEnquiries as $enquiry): ?>
                <tr><td><?= e($enquiry['name']) ?></td><td><?= e($enquiry['business_name']) ?></td><td><?= status_badge($enquiry['status']) ?></td><td><?= e(time_ago($enquiry['created_at'])) ?></td></tr>
            <?php endforeach; if (!$recentEnquiries): ?><tr><td colspan="4"><div class="empty-state"><strong>No enquiries yet</strong></div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Recent orders/requests</h3><p class="card-subtitle">Across all tenants</p></div></div>
        <div class="table-responsive"><table><thead><tr><th>Ref</th><th>Customer</th><th>Business</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($recentOrders as $order): ?>
                <tr><td><?= e($order['order_number']) ?></td><td><?= e($order['customer_name']) ?></td><td><?= e($order['business_name']) ?></td><td><?= status_badge($order['status']) ?></td></tr>
            <?php endforeach; if (!$recentOrders): ?><tr><td colspan="4"><div class="empty-state"><strong>No orders yet</strong></div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>
