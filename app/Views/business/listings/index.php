<section class="page-header">
    <div>
        <div class="page-kicker">Products / Services / Packages</div>
        <h2 class="page-title">Listings</h2>
        <p class="page-description">One flexible listing model supports physical products, services, bookings, tour packages and custom requests.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/business/listings/create')) ?>">+ Add listing</a>
</section>

<div class="card">
    <form class="filter-bar" method="get" action="<?= e(url('/business/listings')) ?>">
        <input type="text" name="q" placeholder="Search listings..." value="<?= e($filters['q']) ?>">
        <select name="type"><option value="">All types</option><?php foreach (($allowedListingTypes ?? ['product' => 'Product']) as $type => $label): ?><option value="<?= e($type) ?>" <?= $filters['type'] === $type ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
        <select name="status"><option value="">All statuses</option><?php foreach (['active','inactive'] as $s): ?><option value="<?= e($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select>
        <select name="category_id"><option value="0">All categories</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $filters['categoryId'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary" type="submit">Filter</button>
        <a class="btn btn-outline" href="<?= e(url('/business/listings')) ?>">Reset</a>
    </form>

    <div class="table-responsive"><table><thead><tr><th>Listing</th><th>Type</th><th>Category</th><th>Price</th><th><?= isset($featureAccess['inventory']) ? 'Stock' : 'Inventory' ?></th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($listings as $listing): ?>
        <tr>
            <td>
                <div class="actions" style="align-items:flex-start;">
                    <?php if ($listing['image_path']): ?><img src="<?= e(upload_url($listing['image_path'])) ?>" alt="" style="width:56px;height:56px;border-radius:12px;object-fit:cover;"> <?php endif; ?>
                    <div>
                        <?php if (isset($featureAccess['public_website']) && ($listing['visible_on_website'] ?? 1)): ?><a class="table-title" target="_blank" href="<?= e(url('/p/' . $business['slug'] . '/listing/' . $listing['slug'])) ?>"><?= e($listing['title']) ?></a><?php else: ?><strong><?= e($listing['title']) ?></strong><?php endif; ?>
                        <div class="table-muted"><?= e($listing['short_description'] ?? '') ?></div>
                        <?php if (isset($featureAccess['featured_listings']) && $listing['is_featured']): ?><span class="badge info">Featured</span><?php endif; ?>
                        <?php if (isset($featureAccess['public_website'])): ?><?= ($listing['visible_on_website'] ?? 1) ? '<span class="badge success">Website</span>' : '<span class="badge muted">Hidden on Website</span>' ?><?php endif; ?>
                    </div>
                </div>
            </td>
            <td><?= e($listing['type']) ?></td>
            <td><?= e($listing['category_name'] ?? '—') ?></td>
            <td><?= $listing['price'] !== null ? e(format_money($listing['price'])) : e($listing['price_label'] ?: 'On request') ?></td>
            <td><?= isset($featureAccess['inventory']) ? ($listing['manage_stock'] ? (int) $listing['stock_quantity'] : 'N/A') : 'Not included' ?></td>
            <td><?= status_badge($listing['status']) ?></td>
            <td><div class="actions"><a class="btn btn-sm btn-outline" href="<?= e(url('/business/listings/' . $listing['id'] . '/edit')) ?>">Edit</a><form method="post" action="<?= e(url('/business/listings/' . $listing['id'] . '/archive')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="Archive this listing?" type="submit">Archive</button></form></div></td>
        </tr>
    <?php endforeach; if (!$listings): ?><tr><td colspan="7"><div class="empty-state"><strong>No listings found</strong>Add products, services or packages for your public portal.</div></td></tr><?php endif; ?>
    </tbody></table></div>
    <div class="pagination">
        <?php if ($page > 1): ?><a class="btn btn-sm btn-outline" href="<?= e(url('/business/listings?page=' . ($page - 1))) ?>">Previous</a><?php endif; ?>
        <span class="table-muted">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
        <?php if ($page < $totalPages): ?><a class="btn btn-sm btn-outline" href="<?= e(url('/business/listings?page=' . ($page + 1))) ?>">Next</a><?php endif; ?>
    </div>
</div>
