<?php
$filterBusiness = (int) ($filters['business_id'] ?? 0);
$filterAction = (string) ($filters['action'] ?? '');
$actionTones = [
    'website_published' => 'success',
    'website_unpublished' => 'neutral',
    'business_submitted_for_review' => 'warning',
    'business_status_changed' => 'info',
    'business_archived' => 'danger',
];

?>
<section class="page-header">
    <div>
        <div class="page-kicker">Platform Oversight</div>
        <h2 class="page-title">Activity &amp; Audit</h2>
        <p class="page-description">Registrations, moderation decisions, publish actions, subscription changes and portal mutations are journalled here with actor, tenant and IP. The stream is append-only — nothing in it can be edited from the application.</p>
    </div>
    <span class="badge muted"><?= (int) $total ?> entries</span>
</section>

<div class="card">
    <form class="filter-bar" method="get" action="<?= e(url('/admin/activity')) ?>">
        <input type="number" min="1" name="business_id" placeholder="Business ID" value="<?= $filterBusiness > 0 ? e((string) $filterBusiness) : '' ?>" style="max-width:150px;">
        <input type="text" name="action" placeholder="Action contains… e.g. website_published" value="<?= e($filterAction) ?>" style="flex:1;">
        <button class="btn btn-primary" type="submit">Filter</button>
        <a class="btn btn-outline" href="<?= e(url('/admin/activity')) ?>">Reset</a>
    </form>

    <?php if (empty($logs)): ?>
        <div class="empty-state"><p><strong>No activity matches this filter.</strong><br>The audit stream records events as they happen.</p></div>
    <?php else: ?>
    <ul class="timeline" style="padding:6px 18px 14px 40px;">
        <?php foreach ($logs as $log): ?>
            <?php
            $label = ucwords(str_replace(['.', '_'], ' ', (string) $log['action']));
            $tone = $actionTones[$log['action']] ?? null;
            $toneColors = ['success' => 'var(--success)', 'warning' => 'var(--warning)', 'danger' => 'var(--danger)', 'info' => 'var(--info)', 'neutral' => 'var(--secondary)'];
            $props = null;
            if (!empty($log['properties'])) {
                $decoded = json_decode((string) $log['properties'], true);
                $props = is_array($decoded) ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $log['properties'];
            }
            ?>
            <li class="timeline-item">
                <strong><?= $tone ? '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' . e($toneColors[$tone] ?? 'var(--secondary)') . ';margin-right:6px;vertical-align:middle;"></span>' : '' ?><?= e($label) ?></strong> <span class="badge muted"><?= e(format_datetime($log['created_at'])) ?></span>
                <div>
                    <?= e($log['subject_type'] ? $log['subject_type'] . (!empty($log['subject_id']) ? ' #' . (int) $log['subject_id'] : '') : 'system') ?>
                    <?php if (!empty($log['business_id'])): ?>
                        · <a href="<?= e(url('/admin/businesses/' . (int) $log['business_id'])) ?>"><?= e($log['business_name'] ?? ('Business #' . (int) $log['business_id'])) ?></a>
                    <?php endif; ?>
                    · actor: <?= e($log['actor_name'] ?? 'system') ?><?php if (!empty($log['actor_role'])): ?> (<?= e($log['actor_role']) ?>)<?php endif; ?>
                    <?php if (!empty($log['ip_address'])): ?> · ip <?= e($log['ip_address']) ?><?php endif; ?>
                </div>
                <?php if ($props !== null): ?>
                    <details><summary class="table-muted" style="cursor:pointer;">Payload</summary><pre style="background:var(--surface-low);border:1px solid var(--outline);border-radius:8px;padding:10px;font-size:12px;overflow:auto;"><?= e($props) ?></pre></details>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php $qs = http_build_query(array_filter(['business_id' => $filterBusiness ?: null, 'action' => $filterAction !== '' ? $filterAction : null])); ?>
        <?php if ($page > 1): ?><a class="btn btn-sm btn-outline" href="<?= e(url('/admin/activity?' . ($qs ? $qs . '&' : '') . 'page=' . ($page - 1))) ?>">Previous</a><?php endif; ?>
        <span class="table-muted">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
        <?php if ($page < $totalPages): ?><a class="btn btn-sm btn-outline" href="<?= e(url('/admin/activity?' . ($qs ? $qs . '&' : '') . 'page=' . ($page + 1))) ?>">Next</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>
