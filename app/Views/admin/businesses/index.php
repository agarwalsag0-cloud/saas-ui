<section class="page-header">
    <div>
        <div class="page-kicker">Tenant Management</div>
        <h2 class="page-title">Businesses</h2>
        <p class="page-description">Add, approve, suspend and inspect each tenant. Business-owned data remains isolated through business_id checks in every tenant query.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/admin/businesses/create')) ?>">+ Add business</a>
</section>

<div class="card">
    <form class="filter-bar" method="get" action="<?= e(url('/admin/businesses')) ?>">
        <input type="text" name="q" placeholder="Search name, city, phone..." value="<?= e($q) ?>">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (['pending','under_review','changes_requested','approved','active','inactive','suspended','rejected'] as $option): ?>
                <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Filter</button>
        <a class="btn btn-outline" href="<?= e(url('/admin/businesses')) ?>">Reset</a>
        <span class="table-muted">Showing <?= count($businesses) ?> of <?= (int) $total ?></span>
    </form>

    <div class="table-responsive">
        <table id="business-table">
            <thead>
                <tr><th>Business</th><th>Owner</th><th>Status</th><th>Website</th><th>Subscription</th><th>Location</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($businesses as $business): ?>
                <tr>
                    <td>
                        <a class="table-title" href="<?= e(url('/admin/businesses/' . $business['id'])) ?>"><?= e($business['name']) ?></a>
                        <div class="table-muted"><?= e($business['category']) ?> · /p/<?= e($business['slug']) ?></div>
                    </td>
                    <td><?= e($business['owner_name'] ?? '—') ?><div class="table-muted"><?= e($business['owner_email'] ?? '') ?></div></td>
                    <td><?= status_badge($business['status']) ?></td>
                    <td>
                        <?php if (!empty($business['includes_public_website'])): ?>
                            <?php if (empty($business['includes_public_website'])): ?><?= status_badge('no_subscription') ?> Plan: no website
                            <?php elseif (empty($business['website_enabled'] ?? 1)): ?><?= status_badge('website_disabled') ?>
                            <?php elseif (empty($business['website_published'] ?? 0)): ?><?= status_badge('unpublished') ?>
                            <?php else: ?><?= status_badge('published') ?><?php endif; ?>
                            <div class="table-muted"><?= ($business['show_in_directory'] ?? 1) ? 'Directory visible' : 'Hidden from directory' ?></div>
                        <?php else: ?>
                            <span class="badge muted">Not Included</span>
                            <div class="table-muted">Plan does not include Public Website</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $business['subscription_status'] ? status_badge($business['subscription_status']) : '<span class="badge muted">No plan</span>' ?>
                        <div class="table-muted"><?= e($business['plan_name'] ?? '') ?> <?= $business['expires_at'] ? '· expires ' . e(format_date($business['expires_at'])) : '' ?></div>
                    </td>
                    <td><?= e(trim(($business['city'] ?? '') . ', ' . ($business['state'] ?? ''), ', ')) ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-sm btn-outline" href="<?= e(url('/admin/businesses/' . $business['id'])) ?>">Details</a>
                            <a class="btn btn-sm btn-outline" href="<?= e(url('/p/' . $business['slug'])) ?>" target="_blank">Website</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; if (!$businesses): ?>
                <tr><td colspan="7"><div class="empty-state"><strong>No businesses found</strong>Try changing filters or add a new business.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?><a class="btn btn-sm btn-outline" href="<?= e(url('/admin/businesses?page=' . ($page - 1) . '&q=' . urlencode($q) . '&status=' . urlencode($status))) ?>">Previous</a><?php endif; ?>
        <span class="table-muted">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
        <?php if ($page < $totalPages): ?><a class="btn btn-sm btn-outline" href="<?= e(url('/admin/businesses?page=' . ($page + 1) . '&q=' . urlencode($q) . '&status=' . urlencode($status))) ?>">Next</a><?php endif; ?>
    </div>
</div>
