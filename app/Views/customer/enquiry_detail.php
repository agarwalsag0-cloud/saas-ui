<div class="page-header">
    <div>
        <div class="page-kicker">Enquiry #<?= (int) $enquiry['id'] ?></div>
        <h1 class="page-title"><?= e($enquiry['business_name']) ?></h1>
    </div>
    <div class="actions">
        <?= status_badge((string) $enquiry['status']) ?>
        <a class="btn btn-outline" href="<?= e(url('/customer/enquiries')) ?>">Back to list</a>
    </div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Your enquiry</h2></div>
        <div class="detail-list" style="margin-bottom:16px;">
            <div class="detail-item"><small>Sent</small><?= e(format_datetime($enquiry['created_at'])) ?></div>
            <div class="detail-item"><small>Requested item</small><?= e($enquiry['requested_item'] ?: '—') ?></div>
        </div>
        <p style="white-space:pre-wrap;margin:0;"><?= e($enquiry['message']) ?></p>
        <?php if (!empty($enquiry['listing_title'])): ?><p class="help-text">Listing: <?= e($enquiry['listing_title']) ?></p><?php endif; ?>
        <div class="actions" style="margin-top:16px;">
            <a class="btn btn-sm btn-primary" href="<?= e(url('/p/' . $enquiry['business_slug'])) ?>" target="_blank" rel="noopener">Open business website</a>
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
