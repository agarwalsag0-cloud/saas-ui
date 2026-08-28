<section class="page-header">
    <div>
        <div class="page-kicker">Feature / Module Registry</div>
        <h2 class="page-title">Platform features</h2>
        <p class="page-description">Register modules once, then include them dynamically in subscription plans. New future modules can be added here without hard-coding plan packages.</p>
    </div>
    <a class="btn btn-outline" href="<?= e(url('/admin/plans')) ?>">Back to plans</a>
</section>

<div class="grid grid-3">
    <div class="card">
        <div class="card-header"><div><h3 class="card-title">Register new feature</h3><p class="card-subtitle">Use a stable identifier that future code can check, e.g. <code>reviews</code> or <code>custom_domain</code>.</p></div></div>
        <form method="post" action="<?= e(url('/admin/features')) ?>">
            <?= csrf_field() ?>
            <div class="form-row"><label>Feature name</label><input type="text" name="name" value="<?= e(old('name')) ?>" required></div>
            <div class="form-row"><label>Internal identifier</label><input type="text" name="identifier" value="<?= e(old('identifier')) ?>" placeholder="example_feature" required><div class="help-text">Use lowercase letters, numbers, underscores, dashes or dots.</div></div>
            <div class="form-row"><label>Category</label><input type="text" name="category" value="<?= e(old('category', 'Future Features')) ?>" required></div>
            <div class="form-row"><label>Icon</label><input type="text" name="icon" value="<?= e(old('icon')) ?>" placeholder="⭐"></div>
            <div class="form-row"><label>Description</label><textarea name="description"><?= e(old('description')) ?></textarea></div>
            <div class="form-row"><label>Sort order</label><input type="number" name="sort_order" value="<?= e(old('sort_order', '0')) ?>"></div>
            <label class="checkbox-row"><input type="checkbox" name="is_active" checked> Active</label>
            <label class="checkbox-row" style="margin-top:8px;"><input type="checkbox" name="available_for_plans" checked> Available for plan selection</label>
            <button class="btn btn-primary btn-block" style="margin-top:14px;" type="submit">Register feature</button>
        </form>
    </div>

    <div class="card" style="grid-column: span 2;">
        <div class="card-header"><div><h3 class="card-title">Registered features</h3><p class="card-subtitle">Edit carefully. Code-enforced modules depend on stable identifiers.</p></div></div>
        <?php foreach ($featureGroups as $category => $features): ?>
            <h3 style="margin:18px 0 10px;"><?= e($category) ?></h3>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Feature</th><th>Identifier</th><th>Status</th><th>Plans</th><th>Update</th></tr></thead>
                    <tbody>
                    <?php foreach ($features as $feature): ?>
                        <tr>
                            <td>
                                <form id="feature-<?= (int) $feature['id'] ?>" method="post" action="<?= e(url('/admin/features/' . $feature['id'])) ?>">
                                    <?= csrf_field() ?>
                                    <input type="text" name="name" value="<?= e($feature['name']) ?>" required>
                                    <textarea name="description" style="margin-top:8px;min-height:70px;"><?= e($feature['description']) ?></textarea>
                                </form>
                            </td>
                            <td>
                                <input form="feature-<?= (int) $feature['id'] ?>" type="text" name="identifier" value="<?= e($feature['identifier']) ?>" required>
                                <input form="feature-<?= (int) $feature['id'] ?>" type="text" name="category" value="<?= e($feature['category']) ?>" style="margin-top:8px;">
                                <input form="feature-<?= (int) $feature['id'] ?>" type="text" name="icon" value="<?= e($feature['icon']) ?>" style="margin-top:8px;width:80px;" placeholder="Icon">
                            </td>
                            <td>
                                <label class="checkbox-row"><input form="feature-<?= (int) $feature['id'] ?>" type="checkbox" name="is_active" <?= $feature['is_active'] ? 'checked' : '' ?>> Active</label>
                                <input form="feature-<?= (int) $feature['id'] ?>" type="number" name="sort_order" value="<?= (int) $feature['sort_order'] ?>" style="margin-top:8px;width:90px;">
                            </td>
                            <td><label class="checkbox-row"><input form="feature-<?= (int) $feature['id'] ?>" type="checkbox" name="available_for_plans" <?= $feature['available_for_plans'] ? 'checked' : '' ?>> Available</label></td>
                            <td><button form="feature-<?= (int) $feature['id'] ?>" class="btn btn-sm btn-primary" type="submit">Save</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; if (!$featureGroups): ?>
            <div class="empty-state"><strong>No features registered</strong>Add the first feature/module to build configurable plans.</div>
        <?php endif; ?>
    </div>
</div>
