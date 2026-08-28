<section class="page-header">
    <div>
        <div class="page-kicker">Catalog Structure</div>
        <h2 class="page-title">Categories</h2>
        <p class="page-description">Categories are tenant-scoped and reused by products, services, packages and other listing types.</p>
    </div>
</section>

<div class="grid grid-3">
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Add category</h3><p class="card-subtitle">Create a public category for this business.</p></div></div>
        <form method="post" action="<?= e(url('/business/categories')) ?>">
            <?= csrf_field() ?>
            <div class="form-row"><label>Name</label><input type="text" name="name" value="<?= e(old('name')) ?>" required></div>
            <div class="form-row"><label>Slug</label><input type="text" name="slug" value="<?= e(old('slug')) ?>" placeholder="auto-generated"></div>
            <div class="form-row"><label>Description</label><textarea name="description"><?= e(old('description')) ?></textarea></div>
            <div class="form-row"><label>Sort order</label><input type="number" name="sort_order" value="<?= e(old('sort_order', '0')) ?>"></div>
            <label class="checkbox-row"><input type="checkbox" name="is_active" checked> Active</label>
            <?php if (isset($featureAccess['public_website'])): ?><label class="checkbox-row" style="margin-top:8px;"><input type="checkbox" name="visible_on_website" checked> Visible on Website</label><?php endif; ?>
            <button class="btn btn-primary btn-block" type="submit" style="margin-top:14px;">Create category</button>
        </form>
    </div>

    <div class="card" style="grid-column: span 2;">
        <div class="card-header"><div><h3 class="card-title">Existing categories</h3><p class="card-subtitle">Edit inline. Deleting a category with listings will deactivate it.</p></div></div>
        <div class="table-responsive"><table><thead><tr><th>Category</th><th>Listings</th><th>Status</th><th>Update</th><th>Remove</th></tr></thead><tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td>
                    <form id="cat-<?= (int) $category['id'] ?>" method="post" action="<?= e(url('/business/categories/' . $category['id'])) ?>">
                        <?= csrf_field() ?>
                        <input type="text" name="name" value="<?= e($category['name']) ?>" required style="min-width:180px;">
                        <input type="hidden" name="slug" value="<?= e($category['slug']) ?>">
                        <textarea name="description" style="margin-top:8px;min-width:220px;min-height:70px;"><?= e($category['description']) ?></textarea>
                    </form>
                </td>
                <td><?= (int) $category['listing_count'] ?></td>
                <td><label class="checkbox-row" form="cat-<?= (int) $category['id'] ?>"><input form="cat-<?= (int) $category['id'] ?>" type="checkbox" name="is_active" <?= $category['is_active'] ? 'checked' : '' ?>> Active</label><?php if (isset($featureAccess['public_website'])): ?><label class="checkbox-row" form="cat-<?= (int) $category['id'] ?>" style="margin-top:8px;"><input form="cat-<?= (int) $category['id'] ?>" type="checkbox" name="visible_on_website" <?= ($category['visible_on_website'] ?? 1) ? 'checked' : '' ?>> Visible on Website</label><?php endif; ?><input form="cat-<?= (int) $category['id'] ?>" type="number" name="sort_order" value="<?= (int) $category['sort_order'] ?>" style="width:90px;margin-top:8px;"></td>
                <td><button form="cat-<?= (int) $category['id'] ?>" class="btn btn-sm btn-primary" type="submit">Save</button></td>
                <td><form method="post" action="<?= e(url('/business/categories/' . $category['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="Delete/deactivate this category?" type="submit">Remove</button></form></td>
            </tr>
        <?php endforeach; if (!$categories): ?><tr><td colspan="5"><div class="empty-state"><strong>No categories yet</strong>Add your first category.</div></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>
