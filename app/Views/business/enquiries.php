<section class="page-header">
    <div>
        <div class="page-kicker">Customer Pipeline</div>
        <h2 class="page-title">Enquiries</h2>
        <p class="page-description">Search, filter, update status and add internal notes. Each enquiry query is scoped to business_id.</p>
    </div>
</section>

<div class="card">
    <form class="filter-bar" method="get" action="<?= e(url('/business/enquiries')) ?>">
        <input type="text" name="q" placeholder="Search customer, phone, message..." value="<?= e($q) ?>">
        <select name="status"><option value="">All statuses</option><?php foreach (['new','contacted','in_progress','converted','closed'] as $s): ?><option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($s))) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary" type="submit">Filter</button><a class="btn btn-outline" href="<?= e(url('/business/enquiries')) ?>">Reset</a>
    </form>

    <div class="table-responsive"><table><thead><tr><th>Customer</th><th>Request</th><th>Message</th><th>Status / Notes</th><th>When</th></tr></thead><tbody>
    <?php foreach ($enquiries as $enquiry): ?>
        <tr>
            <td><strong><?= e($enquiry['name']) ?></strong><div class="table-muted"><?= e($enquiry['phone']) ?><br><?= e($enquiry['email'] ?? '') ?></div></td>
            <td><?= e($enquiry['listing_title'] ?? $enquiry['requested_item'] ?? 'General') ?><div class="table-muted">Source: <?= e($enquiry['source']) ?></div></td>
            <td style="max-width:280px;"><?= e($enquiry['message']) ?></td>
            <td>
                <?= status_badge($enquiry['status']) ?>
                <form method="post" action="<?= e(url('/business/enquiries/' . $enquiry['id'] . '/status')) ?>" style="margin-top:10px;min-width:260px;">
                    <?= csrf_field() ?>
                    <select name="status"><?php foreach (['new','contacted','in_progress','converted','closed'] as $s): ?><option value="<?= e($s) ?>" <?= $enquiry['status'] === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($s))) ?></option><?php endforeach; ?></select>
                    <textarea name="internal_notes" placeholder="Internal notes" style="min-height:70px;margin-top:8px;"><?= e($enquiry['internal_notes']) ?></textarea>
                    <button class="btn btn-sm btn-primary" type="submit" style="margin-top:8px;">Save</button>
                </form>
            </td>
            <td><?= e(format_datetime($enquiry['created_at'])) ?></td>
        </tr>
    <?php endforeach; if (!$enquiries): ?><tr><td colspan="5"><div class="empty-state"><strong>No enquiries found</strong>Public portal submissions will appear here.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</div>
