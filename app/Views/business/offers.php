<section class="page-header">
    <div>
        <div class="page-kicker">Promotions</div>
        <h2 class="page-title">Offers</h2>
        <p class="page-description">Create active offers for the whole business or a specific product/service listing.</p>
    </div>
</section>

<div class="grid grid-3">
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Add offer</h3><p class="card-subtitle">Displayed on your public portal when active and within dates.</p></div></div>
        <form method="post" action="<?= e(url('/business/offers')) ?>">
            <?= csrf_field() ?>
            <div class="form-row"><label>Title</label><input type="text" name="title" required></div>
            <div class="form-row"><label>Listing</label><select name="listing_id"><option value="0">General business offer</option><?php foreach ($listings as $listing): ?><option value="<?= (int) $listing['id'] ?>"><?= e($listing['title']) ?></option><?php endforeach; ?></select></div>
            <div class="form-grid">
                <div class="form-row"><label>Discount type</label><select name="discount_type"><option value="percentage">Percentage</option><option value="fixed">Fixed</option><option value="custom">Custom</option></select></div>
                <div class="form-row"><label>Value</label><input type="number" step="0.01" name="discount_value" value="0"></div>
                <div class="form-row"><label>Start date</label><input type="date" name="start_at"></div>
                <div class="form-row"><label>End date</label><input type="date" name="end_at"></div>
            </div>
            <div class="form-row"><label>Description</label><textarea name="description"></textarea></div>
            <label class="checkbox-row"><input type="checkbox" name="is_active" checked> Active</label>
            <?php if (isset($featureAccess['public_website'])): ?><label class="checkbox-row" style="margin-top:8px;"><input type="checkbox" name="visible_on_website" checked> Visible on Website</label><?php endif; ?>
            <button class="btn btn-primary btn-block" type="submit" style="margin-top:14px;">Create offer</button>
        </form>
    </div>

    <div class="card" style="grid-column: span 2;">
        <div class="card-header"><div><h3 class="card-title">Existing offers</h3><p class="card-subtitle">Update or remove offers scoped to this business.</p></div></div>
        <div class="table-responsive"><table><thead><tr><th>Offer</th><th>Discount</th><th>Dates</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($offers as $offer): ?>
            <tr>
                <td>
                    <form id="offer-<?= (int) $offer['id'] ?>" method="post" action="<?= e(url('/business/offers/' . $offer['id'])) ?>">
                        <?= csrf_field() ?>
                        <input type="text" name="title" value="<?= e($offer['title']) ?>" required>
                        <select name="listing_id" style="margin-top:8px;"><option value="0">General business offer</option><?php foreach ($listings as $listing): ?><option value="<?= (int) $listing['id'] ?>" <?= (int) $offer['listing_id'] === (int) $listing['id'] ? 'selected' : '' ?>><?= e($listing['title']) ?></option><?php endforeach; ?></select>
                        <textarea name="description" style="margin-top:8px;min-height:70px;"><?= e($offer['description']) ?></textarea>
                    </form>
                    <div class="table-muted">Linked: <?= e($offer['listing_title'] ?? 'General') ?></div>
                </td>
                <td><select form="offer-<?= (int) $offer['id'] ?>" name="discount_type"><?php foreach (['percentage','fixed','custom'] as $type): ?><option value="<?= e($type) ?>" <?= $offer['discount_type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select><input form="offer-<?= (int) $offer['id'] ?>" type="number" step="0.01" name="discount_value" value="<?= e($offer['discount_value']) ?>" style="margin-top:8px;width:110px;"></td>
                <td><input form="offer-<?= (int) $offer['id'] ?>" type="date" name="start_at" value="<?= e($offer['start_at'] ? substr($offer['start_at'],0,10) : '') ?>"><input form="offer-<?= (int) $offer['id'] ?>" type="date" name="end_at" value="<?= e($offer['end_at'] ? substr($offer['end_at'],0,10) : '') ?>" style="margin-top:8px;"></td>
                <td><label class="checkbox-row"><input form="offer-<?= (int) $offer['id'] ?>" type="checkbox" name="is_active" <?= $offer['is_active'] ? 'checked' : '' ?>> Active</label><?php if (isset($featureAccess['public_website'])): ?><label class="checkbox-row" style="margin-top:8px;"><input form="offer-<?= (int) $offer['id'] ?>" type="checkbox" name="visible_on_website" <?= ($offer['visible_on_website'] ?? 1) ? 'checked' : '' ?>> Visible on Website</label><?php endif; ?></td>
                <td><div class="actions"><button form="offer-<?= (int) $offer['id'] ?>" class="btn btn-sm btn-primary" type="submit">Save</button><form method="post" action="<?= e(url('/business/offers/' . $offer['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="Delete this offer?" type="submit">Delete</button></form></div></td>
            </tr>
        <?php endforeach; if (!$offers): ?><tr><td colspan="5"><div class="empty-state"><strong>No offers yet</strong>Create a promotion to feature on the public portal.</div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>
