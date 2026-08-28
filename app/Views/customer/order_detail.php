<div class="page-header">
    <div>
        <div class="page-kicker">Request <?= e($order['order_number']) ?></div>
        <h1 class="page-title"><?= e($order['business_name']) ?></h1>
    </div>
    <div class="actions">
        <?= status_badge((string) $order['status']) ?>
        <a class="btn btn-outline" href="<?= e(url('/customer/orders')) ?>">Back to list</a>
    </div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Request details</h2></div>
        <div class="detail-list" style="margin-bottom:16px;">
            <div class="detail-item"><small>Type</small><?= e(ucwords(str_replace('_', ' ', (string) $order['request_type']))) ?></div>
            <div class="detail-item"><small>Quantity</small><?= (int) $order['quantity'] ?></div>
            <div class="detail-item"><small>Total</small><?= $order['total_amount'] !== null ? e(format_money($order['total_amount'])) : 'Quoted by business' ?></div>
            <div class="detail-item"><small>Listing</small><?= e($order['listing_title'] ?: '—') ?></div>
        </div>
        <p style="white-space:pre-wrap;margin:0;"><?= e($order['details']) ?></p>
        <div class="actions" style="margin-top:16px;">
            <a class="btn btn-sm btn-primary" href="<?= e(url('/p/' . $order['business_slug'])) ?>" target="_blank" rel="noopener">Open business website</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h2 class="card-title">Progress</h2></div>
        <div class="timeline">
            <?php foreach ($history as $entry): ?>
                <div class="timeline-item">
                    <strong><?= e(ucwords(str_replace('_', ' ', (string) $entry['new_status']))) ?></strong>
                    <?php if (!empty($entry['note'])): ?><div class="table-muted"><?= e($entry['note']) ?></div><?php endif; ?>
                    <small><?= e(format_datetime($entry['created_at'])) ?></small>
                </div>
            <?php endforeach; ?>
            <?php if (!$history): ?><div class="table-muted">No status updates yet.</div><?php endif; ?>
        </div>
    </div>
</div>
