<div class="page-header">
    <div>
        <div class="page-kicker">Customer Portal</div>
        <h1 class="page-title">My profile</h1>
        <p class="page-description">Keep your contact details current — businesses use them when responding to your enquiries.</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Contact details</h2></div>
        <form method="post" action="<?= e(url('/customer/profile')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-row full">
                    <label>Profile photo</label>
                    <div class="actions" style="align-items:center;">
                        <span class="avatar" style="width:56px;height:56px;font-size:18px;">
                            <?php if (!empty($customer['avatar_path'])): ?><img src="<?= e(upload_url($customer['avatar_path'])) ?>" alt="Your avatar"><?php else: ?><?= e(strtoupper(substr($customer['name'] ?? 'C', 0, 1))) ?><?php endif; ?>
                        </span>
                        <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif">
                    </div>
                    <span class="help-text">JPG, PNG, WEBP or GIF · images only. <label class="checkbox-row" style="display:inline-flex;"><input type="checkbox" name="avatar_remove" value="1"> Remove current photo</label></span>
                </div>
                <div class="form-row full">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" required maxlength="190" value="<?= e($customer['name'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" maxlength="190" value="<?= e($customer['email'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" type="tel" maxlength="40" value="<?= e($customer['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="actions"><button class="btn btn-primary" type="submit">Save changes</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">Security</h2><span class="table-muted">Password hashed with bcrypt · sessions regenerate on login</span></div>
        <?php if (!empty($customer['google_sub'])): ?>
            <div class="notice success">Your account is linked to Google<?= !empty($customer['password_hash']) ? ' and also has a password' : '' ?>.</div>
        <?php endif; ?>
        <?php if (empty($customer['password_hash'])): ?>
            <p class="table-muted">Google accounts can optionally set a password as a backup sign-in method.</p>
            <form method="post" action="<?= e(url('/customer/password/set-initial')) ?>" class="form-grid" style="gap:12px;">
                <?= csrf_field() ?>
                <div class="form-row"><label for="setpw">New password</label><input id="setpw" type="password" name="password" required minlength="8"></div>
                <div class="form-row"><label for="setpw2">Confirm password</label><input id="setpw2" type="password" name="password_confirmation" required></div>
                <div class="actions full"><button class="btn btn-primary" type="submit">Set password</button></div>
            </form>
        <?php else: ?>
            <form method="post" action="<?= e(url('/customer/password')) ?>" class="form-grid" style="gap:12px;">
                <?= csrf_field() ?>
                <div class="form-row full"><label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" required></div>
                <div class="form-row"><label for="new_password">New password</label><input id="new_password" type="password" name="password" required minlength="8"></div>
                <div class="form-row"><label for="new_password2">Confirm new password</label><input id="new_password2" type="password" name="password_confirmation" required></div>
                <div class="actions full"><button class="btn btn-primary" type="submit">Change password</button></div>
            </form>
        <?php endif; ?>
        <?php if ($googleEnabled && empty($customer['google_sub'])): ?>
            <div class="divider-or">Link Google</div>
            <a class="btn-google" href="<?= e(url('/auth/google/redirect')) ?>"><?= icon('google') ?> Link your Google account</a>
            <p class="help-text">You will stay on the same customer account — Google sign-in is only enabled when configured by the platform.</p>
        <?php endif; ?>
    </div>
</div>
