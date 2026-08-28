<div class="page-header">
    <div>
        <div class="page-kicker">Customer Portal</div>
        <h1 class="page-title">My orders &amp; requests</h1>
        <p class="page-description">Product orders, booking and service requests that you submitted through business websites.</p>
    </div>
</div>

<?php if ($orders): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Reference</th><th>Business</th><th>Type</th><th>Amount</th><th>Submitted</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td class="table-title"><?= e($order['order_number']) ?></td>
                    <td>
                        <div class="table-title"><?= e($order['business_name']) ?></div>
                        <a class="table-muted" href="<?= e(url('/p/' . $order['business_slug'])) ?>" target="_blank" rel="noopener">View website ↗</a>
                    </td>
                    <td class="table-muted"><?= e(ucwords(str_replace('_', ' ', (string) $order['request_type']))) ?></td>
                    <td><?= $order['total_amount'] !== null ? e(format_money($order['total_amount'])) : '<span class="table-muted">On request</span>' ?></td>
                    <td class="table-muted"><?= e(format_date($order['created_at'])) ?></td>
                    <td><?= status_badge((string) $order['status']) ?></td>
                    <td style="text-align:right;"><a class="btn btn-sm btn-outline" href="<?= e(url('/customer/orders/' . (int) $order['id'])) ?>">Details</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="card"><div class="empty-state"><strong>No orders or requests yet</strong>Requests submitted from business websites will be tracked here.</div></div>
<?php endif; ?>
