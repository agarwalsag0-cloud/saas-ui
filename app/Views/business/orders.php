<section class="page-header">
    <div>
        <div class="page-kicker">Orders & Requests</div>
        <h2 class="page-title">Orders / Requests</h2>
        <p class="page-description">A flexible request system for product orders, service requests, booking requests, package enquiries and custom requests.</p>
    </div>
</section>

<div class="card">
    <form class="filter-bar" method="get" action="<?= e(url('/business/orders')) ?>">
        <input type="text" name="q" placeholder="Search reference, customer, details..." value="<?= e($q) ?>">
        <select name="status"><option value="">All statuses</option><?php foreach (['new','confirmed','in_progress','completed','cancelled','closed'] as $s): ?><option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($s))) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary" type="submit">Filter</button><a class="btn btn-outline" href="<?= e(url('/business/orders')) ?>">Reset</a>
    </form>

    <div class="table-responsive"><table><thead><tr><th>Reference</th><th>Customer</th><th>Listing / Type</th><th>Details</th><th>Status / Notes</th><th>Amount</th></tr></thead><tbody>
    <?php foreach ($orders as $order): ?>
        <tr>
            <td><strong><?= e($order['order_number']) ?></strong><div class="table-muted"><?= e(format_datetime($order['created_at'])) ?></div></td>
            <td><?= e($order['customer_name']) ?><div class="table-muted"><?= e($order['phone']) ?><br><?= e($order['email'] ?? '') ?></div></td>
            <td><?= e($order['listing_title'] ?? 'General') ?><div class="table-muted"><?= e(str_replace('_', ' ', $order['request_type'])) ?> · Qty <?= (int) $order['quantity'] ?></div></td>
            <td style="max-width:260px;"><?= e($order['details']) ?></td>
            <td>
                <?= status_badge($order['status']) ?>
                <form method="post" action="<?= e(url('/business/orders/' . $order['id'] . '/status')) ?>" style="margin-top:10px;min-width:260px;">
                    <?= csrf_field() ?>
                    <select name="status"><?php foreach (['new','confirmed','in_progress','completed','cancelled','closed'] as $s): ?><option value="<?= e($s) ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($s))) ?></option><?php endforeach; ?></select>
                    <textarea name="internal_notes" placeholder="Internal notes" style="min-height:70px;margin-top:8px;"><?= e($order['internal_notes']) ?></textarea>
                    <button class="btn btn-sm btn-primary" type="submit" style="margin-top:8px;">Save</button>
                </form>
            </td>
            <td><?= $order['total_amount'] !== null ? e(format_money($order['total_amount'])) : '—' ?></td>
        </tr>
    <?php endforeach; if (!$orders): ?><tr><td colspan="6"><div class="empty-state"><strong>No requests found</strong>Public portal request forms will appear here.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</div>
