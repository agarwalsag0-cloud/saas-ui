<section class="page-header">
    <div>
        <div class="page-kicker">Notifications</div>
        <h2 class="page-title">Business notifications</h2>
        <p class="page-description">Internal tenant-scoped notifications for orders, enquiries and subscription events.</p>
    </div>
    <form method="post" action="<?= e(url('/business/notifications/read')) ?>"><?= csrf_field() ?><button class="btn btn-outline" type="submit">Mark all read</button></form>
</section>

<div class="card">
    <div class="table-responsive"><table><thead><tr><th>Notification</th><th>Type</th><th>Status</th><th>When</th></tr></thead><tbody>
    <?php foreach ($notifications as $notification): ?>
        <tr><td><strong><?= e($notification['title']) ?></strong><div class="table-muted"><?= e($notification['body']) ?></div></td><td><?= e($notification['type']) ?></td><td><?= $notification['is_read'] ? status_badge('closed') : status_badge('new') ?></td><td><?= e(time_ago($notification['created_at'])) ?></td></tr>
    <?php endforeach; if (!$notifications): ?><tr><td colspan="4"><div class="empty-state"><strong>No notifications</strong></div></td></tr><?php endif; ?>
    </tbody></table></div>
</div>
