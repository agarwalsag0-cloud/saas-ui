<div class="page-header">
    <div>
        <div class="page-kicker">Customer Portal</div>
        <h1 class="page-title">My enquiries</h1>
        <p class="page-description">Every enquiry you send through a business website appears here with its live status. Only you can see this history.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>"><?= icon('search') ?> Send a new enquiry</a>
</div>

<?php if ($enquiries): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Business</th><th>About</th><th>Sent</th><th>Updated</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($enquiries as $enquiry): ?>
                <tr>
                    <td>
                        <div class="table-title"><?= e($enquiry['business_name']) ?></div>
                        <a class="table-muted" href="<?= e(url('/p/' . $enquiry['business_slug'])) ?>" target="_blank" rel="noopener">View website ↗</a>
                    </td>
                    <td><?= e(text_excerpt((string) ($enquiry['requested_item'] ?: $enquiry['message']), 70)) ?></td>
                    <td class="table-muted"><?= e(format_datetime($enquiry['created_at'])) ?></td>
                    <td class="table-muted"><?= e(format_date($enquiry['updated_at'])) ?></td>
                    <td><?= status_badge((string) $enquiry['status']) ?></td>
                    <td style="text-align:right;"><a class="btn btn-sm btn-outline" href="<?= e(url('/customer/enquiries/' . (int) $enquiry['id'])) ?>">Details</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="card"><div class="empty-state"><strong>No enquiries yet</strong>Open any published business website and use its enquiry form — it will appear here automatically.</div></div>
<?php endif; ?>
