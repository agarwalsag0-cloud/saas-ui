<div class="page-header">
    <div>
        <div class="page-kicker">Customer Portal</div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-description">Updates about your enquiries and requests across all businesses.</p>
    </div>
    <form method="post" action="<?= e(url('/customer/notifications')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline" name="mark_read" value="1" type="submit">Mark all as read</button>
    </form>
</div>

<div class="card">
    <?php if ($notifications): ?>
        <div class="table-responsive" style="border:none;box-shadow:none;">
            <table>
                <tbody>
                <?php foreach ($notifications as $notification): ?>
                    <tr>
                        <td style="width:34px;"><span class="badge <?= (int) $notification['is_read'] ? 'muted' : 'info' ?>"><?= (int) $notification['is_read'] === 0 ? 'new' : '•' ?></span></td>
                        <td>
                            <div class="table-title"><?= e($notification['title']) ?></div>
                            <div class="table-muted"><?= e($notification['body'] ?? '') ?></div>
                        </td>
                        <td class="table-muted" style="text-align:right;white-space:nowrap;"><?= e(time_ago($notification['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><strong>No notifications</strong>We will notify you when a business updates one of your enquiries or requests.</div>
    <?php endif; ?>
</div>
