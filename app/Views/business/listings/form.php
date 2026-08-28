<?php
$isEdit = $mode === 'edit';
$value = fn(string $key, $default = '') => old($key, $listing[$key] ?? $default);
$action = $isEdit ? url('/business/listings/' . $listing['id']) : url('/business/listings');
?>
<section class="page-header">
    <div>
        <div class="page-kicker"><?= $isEdit ? 'Edit Listing' : 'New Listing' ?></div>
        <h2 class="page-title"><?= $isEdit ? 'Edit product/service' : 'Add product/service' ?></h2>
        <p class="page-description">Use listing type to represent products, services, bookings, tour packages or custom offerings without changing the database.</p>
    </div>
    <a class="btn btn-outline" href="<?= e(url('/business/listings')) ?>">Back to listings</a>
</section>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="grid grid-2">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Core details</h3><p class="card-subtitle">Information displayed on the public portal.</p></div></div>
        <div class="form-grid">
            <div class="form-row"><label>Title *</label><input type="text" name="title" value="<?= e($value('title')) ?>" required></div>
            <div class="form-row"><label>Slug</label><input type="text" name="slug" value="<?= e($value('slug')) ?>" placeholder="auto-generated"></div>
            <div class="form-row"><label>Type</label><select name="type"><?php foreach (($allowedListingTypes ?? ['product' => 'Product']) as $type => $label): ?><option value="<?= e($type) ?>" <?= $value('type', array_key_first($allowedListingTypes ?? ['product' => 'Product'])) === $type ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
            <div class="form-row"><label>Category</label><select name="category_id"><option value="0">No category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $value('category_id', 0) === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-row"><label>Status</label><select name="status"><?php foreach (['active','inactive'] as $s): ?><option value="<?= e($s) ?>" <?= $value('status', 'active') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
            <div class="form-row">
                <?php if (isset($featureAccess['featured_listings'])): ?><label class="checkbox-row" style="margin-top:30px;"><input type="checkbox" name="is_featured" <?= $value('is_featured', 0) ? 'checked' : '' ?>> Featured listing</label><?php endif; ?>
                <?php if (isset($featureAccess['public_website'])): ?><label class="checkbox-row" style="margin-top:8px;"><input type="checkbox" name="visible_on_website" <?= $value('visible_on_website', 1) ? 'checked' : '' ?>> Visible on Website</label><?php endif; ?>
            </div>
            <div class="form-row full"><label>Short description</label><input type="text" name="short_description" value="<?= e($value('short_description')) ?>"></div>
            <div class="form-row full"><label>Full description</label><textarea name="description"><?= e($value('description')) ?></textarea></div>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <div class="card-header"><div><h3 class="card-title">Pricing and inventory</h3><p class="card-subtitle">Stock is optional because not every business uses inventory.</p></div></div>
            <div class="form-grid">
                <div class="form-row"><label>Price</label><input type="number" step="0.01" min="0" name="price" value="<?= e($value('price')) ?>"></div>
                <div class="form-row"><label>Price label</label><input type="text" name="price_label" value="<?= e($value('price_label')) ?>" placeholder="On request / Starts from..."></div>
                <div class="form-row"><label>Compare at price</label><input type="number" step="0.01" min="0" name="compare_at_price" value="<?= e($value('compare_at_price')) ?>"></div>
                <?php if (isset($featureAccess['inventory'])): ?>
                    <div class="form-row"><label>Stock quantity</label><input type="number" min="0" name="stock_quantity" value="<?= e($value('stock_quantity', '0')) ?>"></div>
                    <div class="form-row full"><label class="checkbox-row"><input type="checkbox" name="manage_stock" <?= $value('manage_stock', 0) ? 'checked' : '' ?>> Track stock for this listing</label></div>
                <?php else: ?>
                    <div class="form-row"><label>Inventory</label><input type="text" value="Upgrade plan to track stock" disabled></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><div><h3 class="card-title">Media and specifications</h3><p class="card-subtitle">Use key:value lines or paste JSON for advanced specs.</p></div></div>
            <?php if ($isEdit && !empty($listing['image_path'])): ?><img src="<?= e(upload_url($listing['image_path'])) ?>" alt="Listing image" style="width:100%;height:180px;border-radius:18px;object-fit:cover;margin-bottom:12px;"><?php endif; ?>
            <div class="form-row"><label>Main image</label><input type="file" name="image" accept="image/*"></div>
            <div class="form-row"><label>Specifications</label><textarea name="specifications" placeholder="Brand: Example&#10;Warranty: 1 year&#10;Duration: 5 days"><?= e(old('specifications', $specificationsText)) ?></textarea></div>
        </div>
        <div class="card"><button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'Save listing' : 'Create listing' ?></button></div>
    </div>
</form>
