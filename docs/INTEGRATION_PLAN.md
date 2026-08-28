# Integration Plan — Multi-Business SaaS Platform (final reconstruction)

## Source map (audited)

| Source | What it is | Role in the final app |
|---|---|---|
| GitHub repo (`saas-ui`) | Currently only carries the audit prompt txt, the main-project ZIP and the Stitch folder | Becomes the real working codebase (app deployed at repo root) |
| Main project (`multi-business-subscription-platform`) | Complete PHP 8 + MySQL MVC app: Router/Auth/CSRF/Validator core, Super Admin portal, Business portal, feature-registry subscriptions, public websites `/p/{slug}`, uploads, activity log, notifications, demo seed + install SQL, docs, CLI scripts | Backend/business logic backbone — reused, extended, fixed |
| Stitch project (`stitch_multi_biz_saas_portal`) | 19 screens + `core_ledger` DESIGN.md (Electric Indigo #5850EC, Plus Jakarta Sans + Inter, dark-slate sidebar w/ 4px active indicator, gray-50 canvas, white 1px-border cards, soft shadows, pill badges) | UI/UX reference for all screens, incl. the **customer portal** which the main project lacks entirely |

## Gaps vs. spec (to close in this build)

1. **Customer accounts** — missing entirely (only a per-business `customers` CRM table exists). Add `customer_accounts` (password + Google OAuth architecture), customer portal (dashboard, profile, my enquiries, my orders, notifications), link enquiries/orders to account, prefill forms.
2. **Website state separation** — today "configured = live". Add `website_published` + publish/unpublish, `/business/website/preview` (draft, noindex, owner-only), `/admin/businesses/{id}/preview`; directory + `/p/{slug}` require published; `website_enabled` (admin kill-switch) and `show_in_directory` stay separate.
3. **Approval workflow** — add `under_review` and `changes_requested` states: business can "Submit for review", admin can approve / reject / request changes (with note → notification).
4. **SEO** — dynamic `/sitemap.xml`, `/robots.txt`, canonical + robots meta on preview/unavailable pages, og tags, indexability gated on approved+published.
5. **Directory UX** — search, category filter, pagination on public landing (currently fixed 12 cards, no filtering).
6. **Audit screen** — admin activity/audit log listing page.
7. **UI** — reskin all layouts + new customer screens to Stitch design language while keeping existing DOM hooks/class names; Material-style inline SVG icons; responsive fixes.
8. **Verification** — PHP lint via WASM PHP 8.3, real HTTP end-to-end run against SQLite compat harness (`DB_DRIVER=sqlite`, test-only), MySQL-schema static validator for every inline query, tenant-isolation + security probes; README documents real MySQL/XAMPP behavior.

## Key decisions

- App lives at repo **root** (`app/`, `public/`, `database/`, …) so the XAMPP copy = the repo folder.
- One database, one auth system: `users` (super_admin/business) + `customer_accounts` (customers) share session hardening patterns but never mix roles; role guards are server-side (`Auth::requireSuperAdmin`, `Auth::requireBusinessUser`, `CustomerAuth::requireLogin`).
- Feature access stays registry-driven (`platform_features` → `subscription_plan_features`); customers' portal features are platform-level and never billed to tenants.
- `website_published` defaults to 0 for new installs (spec: default config ≠ public); the existing-data update patch preserves current behavior for already-live sites by setting it from `website_enabled`.
- Google OAuth = real authorization-code flow, only enabled when `GOOGLE_CLIENT_ID/SECRET` are configured; no passwords stored, no fake logins.

## Build status (this round)

All gaps above are implemented. Verified statically in the sandbox (no MySQL available here):

- `token_get_all` syntax lint (PHP 8.3 WASM): **all 94 app PHP files pass**.
- Every `url('/…')` literal used by views/layouts resolves to a route registered in `public/index.php` (70 routes).
- Every `icon('…')` name resolves to a glyph defined in `app/helpers.php` (unknown names silently fall back, so this was audited explicitly).
- Publish/unpublish/submit-review forms are rendered outside the website-settings `<form>` (no nested forms).
- State matrix enforced by `WebsiteAccessService::evaluate` and re-checked in `PublicPortalController` on every request (GET portal/listing, enquiry/order POST, sitemap): configured ≠ published ≠ approved ≠ directory-visible ≠ indexable.
- Preview is gated per viewer (owner of that tenant or Super Admin) and always `noindex`, never canonical, never in sitemap/directory.
- `changes_requested`/`rejected` now clear `approved_at` so re-approval is required; `review_note` is stored and surfaced in the admin business page + notification.

Requires a run on XAMPP/MySQL (per docs/TESTING.md): full schema import + `database/update_2026_08_customer_portal_and_website_workflow.sql` on existing installs, then the publish/review and customer-portal checklists. Google login additionally needs `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` in `.env` (copy `.env.example`); the Google button is hidden until configured — no fake auth fallbacks exist.

## Auth architecture rebuild (login separation round)

- Three isolated doors: `/login` chooser (Customer|Business only), `/business/login` (business roles), `/admin/login` (super_admin only, unlinked from public UI, rate-limited, self-locking `/setup`). Customers use the separate `customer_accounts` store; a Google-created customer can never obtain tenant access (proven by S22).
- `Auth::attempt` now filters by role list per door; portal guards re-resolve role/tenant/status from the database every request (session payload is only an id), so session tampering fails closed (S15).
- Registration ≠ approval: `SubscriptionService::canManageContent()` lets pending tenants build profiles/listings/website while `canUsePortal()` still gates publishing; publish additionally hard-requires Super Admin approval (S13). Account status, approval, subscription, features, website publish, directory visibility and indexability remain seven independent server-side concepts.
- Sessions: HttpOnly+SameSite=Lax cookies, regenerate on login/logout, idle expiry via `SESSION_IDLE_MINUTES` (default 120), intended-URL resume after sign-in.
- New executable verification: `testing/` SQLite harness (real controllers/guards/views, fixtures derived from install.sql) — 26 scenarios covering the entire requirement-17 matrix, all passing; `php testing/run_tests.php` reproduces it on XAMPP.

## Current round (single-database import) — COMPLETE

- New `database/mbsp_database.sql`: the ONE file to import in phpMyAdmin — full schema + feature registry (kept byte-identical to `install.sql`) + adapted demo seed. Every INSERT column list was statically validated against the schema.
- Deliberate changes vs the old demo_seed.sql: no `super_admin` user row and no `recorded_by/actor` references to a pre-created admin id (per the no-hardcoded-admin constraint); added platform `customer_accounts` (2 logins) linked to existing enquiries/orders via `customer_account_id`; added a staff user (role separation test), a 6th tenant in `rejected` state with review note (login-block + rejection-visibility tests), a customer-facing notification (bell in the customer portal), and mixed `business_settings` publish states (published+indexable vs configured-unpublished) to drive the Publish workflow test.
- `install.sql` remains the schema source of truth for `testing/setup_db.php`; `demo_seed.sql` and the `update_*.sql` patch files stay for reference/upgrades of pre-existing databases. README, docs/TESTING.md, docs/ARCHITECTURE.md now point at the single file.
- Verified: automated harness still ALL PASS (26 scenarios) with the untouched install.sql; no app PHP changed this round.
