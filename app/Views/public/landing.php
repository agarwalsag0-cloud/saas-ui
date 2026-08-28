<section class="hero">
    <div class="hero-content">
        <div class="page-kicker">Multi-tenant business platform</div>
        <h1>Discover local businesses.<br>Enquiry, request, done.</h1>
        <p>Every listing below is an approved tenant with an active subscription and a published website. Browse catalogs and offers, then contact the business directly — or save everything to your customer account.</p>
        <div class="actions" style="margin-top:14px;">
            <a class="btn btn-primary" href="<?= e(url('/register-business')) ?>">Register your business</a>
            <a class="btn btn-ghost" href="<?= e(url('/login')) ?>">Sign in</a>
        </div>
    </div>
</section>

<section class="public-section" style="padding-top:0;">
    <form class="portal-filter" method="get" action="<?= e(url('/')) ?>">
        <div class="form-row" style="flex:2;">
            <label for="q">Search businesses</label>
            <input id="q" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, tagline, description or city…">
        </div>
        <div class="form-row">
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $categoryOption): ?>
                    <option value="<?= e($categoryOption) ?>" <?= $filters['category'] === $categoryOption ? 'selected' : '' ?>><?= e($categoryOption) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="city">City</label>
            <select id="city" name="city">
                <option value="">All cities</option>
                <?php foreach ($cities as $cityOption): ?>
                    <option value="<?= e($cityOption) ?>" <?= $filters['city'] === $cityOption ? 'selected' : '' ?>><?= e($cityOption) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit" style="height:42px;"><?= icon('search') ?> Search</button>
    </form>

    <?php if ($filters['q'] !== '' || $filters['category'] !== '' || $filters['city'] !== ''): ?>
        <div class="portal-filters" style="margin-top:14px;">
            <span class="table-muted"><?= (int) $total ?> result<?= $total == 1 ? '' : 's' ?> · </span>
            <a href="<?= e(url('/')) ?>">Clear filters ✕</a>
        </div>
    <?php endif; ?>

    <div class="card-header" style="margin-top:26px;">
        <div>
            <h2 class="card-title" style="font-size:24px;">Business website directory</h2>
            <p class="card-subtitle">Only approved businesses with an active plan, published website and directory visibility appear here.</p>
        </div>
    </div>
    <div class="listing-grid">
        <?php foreach ($businesses as $business): ?>
            <article class="listing-card">
                <div class="listing-image">
                    <?php if ($business['cover_path']): ?><img src="<?= e(upload_url($business['cover_path'])) ?>" alt="<?= e($business['name']) ?> cover image"><?php else: ?><?= e($business['category']) ?><?php endif; ?>
                </div>
                <div class="listing-body">
                    <?php if ($business['logo_path']): ?><img src="<?= e(upload_url($business['logo_path'])) ?>" alt="<?= e($business['name']) ?> logo" style="width:56px;height:56px;border-radius:14px;object-fit:cover;margin-top:-44px;border:3px solid #fff;box-shadow:0 8px 20px rgba(15,23,42,.12);background:#fff;"><?php endif; ?>
                    <h3 style="margin:0;"><?= e($business['name']) ?></h3>
                    <div class="table-muted"><?= e($business['category']) ?><?= $business['city'] ? ' · ' . e(trim($business['city'] . ', ' . ($business['state'] ?? ''), ', ')) : '' ?></div>
                    <p><?= e(text_excerpt((string) ($business['tagline'] ?: ($business['description'] ?? '')), 130)) ?></p>
                    <a class="btn btn-primary btn-sm" href="<?= e(url('/p/' . $business['slug'])) ?>">Visit website <?= icon('public') ?></a>
                </div>
            </article>
        <?php endforeach; if (!$businesses): ?>
            <div class="empty-state" style="grid-column:1/-1;">
                <strong>No public businesses match</strong>
                Approved tenants with a published website and the website feature in their plan will appear here.<br>
                <a class="btn btn-sm btn-outline" href="<?= e(url('/register-business')) ?>">Register a business instead</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Directory pagination">
            <?php
            $qs = static function (int $p) use ($filters) {
                $query = array_filter(['q' => $filters['q'], 'category' => $filters['category'], 'city' => $filters['city'], 'page' => $p > 1 ? $p : null], fn($v) => $v !== null && $v !== '');
                return url('/' . ($query ? '?' . http_build_query($query) : ''));
            };
            ?>
            <?php if ($page > 1): ?><a href="<?= e($qs($page - 1)) ?>">← Prev</a><?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p === $page): ?><span class="active"><?= $p ?></span>
                <?php elseif (abs($p - $page) <= 2 || $p === 1 || $p === $totalPages): ?><a href="<?= e($qs($p)) ?>"><?= $p ?></a>
                <?php elseif (abs($p - $page) === 3): ?><span>…</span><?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><a href="<?= e($qs($page + 1)) ?>">Next →</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
