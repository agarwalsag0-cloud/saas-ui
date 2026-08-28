# Multi-Business Subscription Platform

A production-minded PHP + MySQL SaaS foundation for running many independent business portals under one central platform. It is designed for local XAMPP development, phpMyAdmin database import, and continued development in VS Code.

### Login & access model

- `/login` — public chooser: **Customer** or **Business** (never shows Super Admin).
- `/customer/login`, `/customer/register` — self-service customer accounts (Google sign-in when `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` are configured; no approval required, separate `customer_accounts` store).
- `/business/login` — tenant staff only; sessions bind to the user's `business_id`, every business query is tenant-scoped server-side.
- `/admin/login` — Super Admin only; deliberately not linked from any public page; rate-limited; created once through `/setup` which then locks itself.
- Customers, business users and admins can never sign in to each other's portals even with correct passwords (role-checked at `Auth::attempt`), and portal guards re-verify role + tenant from the database on every request.

## What is included

- Super Admin portal
  - Platform dashboard with business, subscription, revenue, enquiry and order statistics.
  - Business approval/status management.
  - Business detail screen with payment history, subscription controls and activity logs.
  - Configurable subscription plan/package builder.
  - Central Feature / Module Registry at `/admin/features` with identifier, name, description, category, icon, active status and plan availability.
  - Dynamic plan-feature assignment with optional per-feature limits.
  - Manual payment recording with future gateway-ready fields.
  - Platform notifications.
- Business Owner portal
  - Tenant-scoped dashboard.
  - Business profile and safe branding customization.
  - Categories.
  - Products/services/packages/bookings/custom listings.
  - Offers.
  - Enquiry management with statuses and notes.
  - Order/request management with statuses and notes.
  - Subscription and payment history view with included current-plan features.
  - Business notifications.
  - Menus and backend actions are controlled by the active subscription plan features, not by hard-coded plan names.
- Public business websites
  - Every approved/active business whose active subscription includes `public_website` gets a dynamic website at `/p/{business-slug}`.
  - Website content is database-driven from business profile, categories, listings, offers and website settings, and each public module is also filtered by included plan features.
  - Website customization section with presets: Modern, Elegant, Minimal, Professional and Bold.
  - Business-controlled public visibility toggles for categories, listings, offers and website sections.
  - Public enquiry and order/request submission forms where enabled.
  - Super Admin can enable/disable a business website and preview any tenant website.
- Security foundation
  - Password hashing with `password_hash()` / `password_verify()`.
  - PDO prepared statements.
  - Server-side role checks.
  - Tenant isolation through `business_id` in every business-owned query.
  - CSRF protection on POST forms.
  - Secure upload validation for images.
  - Generic error pages; raw database errors are logged, not shown.
  - Activity logs for key operations.

## Technology

- PHP 8.1+ recommended
- MySQL or MariaDB through XAMPP
- Apache through XAMPP
- phpMyAdmin
- HTML, CSS, vanilla JavaScript
- No Composer dependencies and no external frontend CDN required

## Folder structure

```text
multi-business-subscription-platform/
├── app/
│   ├── Controllers/
│   │   ├── Admin/       # Super Admin controllers
│   │   ├── Business/    # Business Owner controllers
│   │   └── PublicPortalController.php
│   ├── Core/            # Router, DB, Auth, CSRF, View, Flash, Validator
│   ├── Services/        # Feature access, upload, subscription logic, notifications, activity, slugs
│   └── Views/           # Layouts and page templates
├── database/
│   ├── install.sql                    # Required database structure + default feature registry
│   ├── update_public_website.sql       # Patch only if an older install was already imported
│   ├── update_feature_based_plans.sql  # One-time patch for older installs to add feature registry/plan pivot
│   ├── fix_existing_database_after_updates.sql # Safe repair script if old DB causes 500 after code update
│   └── demo_seed.sql                  # Optional demo/test data with sample configurable plans
├── public/
│   ├── index.php        # Front controller
│   ├── assets/          # CSS/JS
│   └── uploads/         # Tenant uploads, PHP execution disabled by .htaccess
├── scripts/
│   ├── create_super_admin.php
│   ├── tenant_isolation_smoke_test.php
│   └── send_renewal_notifications.php
├── storage/logs/        # Application logs
├── .env.example
└── README.md
```

## XAMPP setup

### 1. Copy the project

Copy the `multi-business-subscription-platform` folder to your XAMPP `htdocs` directory, for example:

```text
C:\xampp\htdocs\multi-business-subscription-platform
```

### 2. Start services

Open XAMPP Control Panel and start:

- Apache
- MySQL

### 3. Create the database in phpMyAdmin

1. Open `http://localhost/phpmyadmin`.
2. Click **New**.
3. Create a database, for example:
   - Name: `multi_business_platform`
   - Collation: `utf8mb4_unicode_ci`
4. Select the database.
5. Go to **Import**.
6. Import `database/install.sql`.

That is the only required SQL file for a fresh setup. It creates the structure and central feature registry, but does not force Super Admin credentials or fixed subscription packages. `database/demo_seed.sql` is optional and only for sample/testing data. Skip it if you want to create every credential and plan yourself.

If you already imported an older copy of `install.sql`, use the patch files instead of recreating the database:

1. Import `database/update_public_website.sql` once if that update was not already applied.
2. Import `database/update_feature_based_plans.sql` once to add `platform_features`, `subscription_plan_features`, and monthly/yearly price columns.

Do not import the update files repeatedly. If you are unsure what has already been applied and you see a 500 after updating the PHP files, use `database/fix_existing_database_after_updates.sql`; it checks for missing columns/tables before adding them.

The SQL files do not force a database name. If you choose a different database name, update `.env` accordingly.

### 4. Configure environment

`.env.example` is only a safe template. `.env` is the real local configuration file that the app reads. This keeps database credentials out of shared/example files.

Copy `.env.example` to `.env` in the project root:

```text
APP_NAME="Multi-Business Subscription Platform"
APP_ENV=local
APP_DEBUG=false
APP_TIMEZONE=Asia/Kolkata
SESSION_NAME=MBSPSESSID

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=multi_business_platform
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4

UPLOAD_MAX_MB=3
```

For a default XAMPP install, MySQL username is usually `root` and password is usually empty.

### 5. Open the app

Visit:

```text
http://localhost/multi-business-subscription-platform/public
```

There is also a root `index.php` convenience redirect, so this usually works too:

```text
http://localhost/multi-business-subscription-platform
```

## Demo credentials

If you optionally imported `database/demo_seed.sql`, all demo passwords are:

```text
password
```

Accounts:

| Role | Email | Password |
|---|---|---|
| Super Admin | `admin@platform.local` | `password` |
| Business Owner | `owner@luckytour.test` | `password` |
| Business Owner | `owner@electrohub.test` | `password` |
| Pending tenant | `owner@calmcare.test` | `password` |
| Expired tenant | `owner@spiceleaf.test` | `password` |
| Suspended tenant | `owner@northstar.test` | `password` |

Change demo passwords before using the system beyond local testing.

## Creating the initial Super Admin

No Super Admin credential is hard-coded in the main installer. After importing `install.sql`, open this URL and create your own credentials:

```text
http://localhost/multi-business-subscription-platform/public/setup
```

If you prefer CLI, configure `.env`, then run from the project root:

```bash
php scripts/create_super_admin.php
```

The script prompts for name, email and password and stores the password securely using PHP password hashing.


## Credential flow

- Main install creates tables only; it does not create a forced admin username/password.
- First Super Admin is created by you from `/setup` or the CLI script.
- Super Admin can create businesses and owner credentials from `/admin/businesses/create`.
- Public business registration is available at `/register-business`; the owner sets their own password and can optionally choose a preferred subscription plan. The business remains `pending`, and the selected plan remains pending until Super Admin approves/activates it.
- Optional `demo_seed.sql` is only for testing and can be skipped.

## Major modules

### 1. Super Admin

Routes begin with `/admin`.

- `/admin` dashboard
- `/admin/businesses` business list and filters
- `/admin/businesses/create` add business and first owner
- `/admin/businesses/{id}` detail, status, subscription and payments
- `/admin/plans` configurable subscription plan/package builder
- `/admin/features` central Feature / Module Registry
- `/admin/notifications` platform notification stream

### 2. Business Owner

Routes begin with `/business`.

Business users can only manage records belonging to their own `business_id`. Business portal modules are also protected by active subscription + included plan feature checks.

- `/business` dashboard with feature-aware cards and public website CTA when included
- `/business/profile` public profile and branding when included
- `/business/website` website customization, theme presets, SEO basics and public visibility controls when `public_website` + `website_customization` are included
- `/business/categories` when `categories` is included
- `/business/listings` when `product_management` and/or `service_management` is included
- `/business/offers` when `offers` is included
- `/business/enquiries` when `enquiries` is included
- `/business/orders` when `orders` is included, with request subtypes respecting product/service/booking/service-request features
- `/business/subscription` always available for plan/payment status and upgrade guidance
- `/business/notifications` when `notifications` is included

### 3. Public business website

- `/` public website directory
- `/p/{slug}` dynamic business website
- `/p/{slug}/listing/{listingSlug}` public listing detail page
- Public forms submit to tenant-scoped enquiry/order tables.
- The website respects business status, subscription status, selected plan features, Super Admin website toggle and business visibility settings.

## Multi-tenant architecture

The platform uses a shared database with strict tenant keys.

Every business-owned table has a `business_id`, including:

- `users`
- `business_settings`
- `business_subscriptions`
- `payments`
- `categories`
- `listings`
- `offers`
- `customers`
- `enquiries`
- `orders`
- `notifications`
- `activity_logs`

Business portal controllers always obtain the tenant from the authenticated session, not from a user-controlled URL parameter:

```php
$businessId = Auth::businessId();
```

Then every query includes tenant scope, for example:

```sql
SELECT * FROM listings WHERE id = ? AND business_id = ? LIMIT 1
```

This prevents insecure direct object reference attacks where a business owner changes an ID in the URL to access another tenant's records.

Super Admin routes are separate and require `role = super_admin`.

## Feature-based subscription architecture

Plans are configurable product packages, not hard-coded tiers. The access chain is:

```text
Business → current active subscription → subscription plan → subscription_plan_features → platform_features → controller/view/public access
```

Core tables:

- `platform_features`: central registry with `identifier`, `name`, `description`, `category`, `icon`, `is_active`, `available_for_plans` and `sort_order`.
- `subscription_plan_features`: many-to-many plan-feature pivot with `enabled` and optional `limits_json`.
- `subscription_plans`: plan metadata and data-driven `monthly_price` / `yearly_price`.

Important implementation files:

- `app/Services/FeatureService.php` calculates registry data, plan features, business feature access and limits.
- `app/Controllers/Business/BaseBusinessController.php` injects `$featureAccess` into business views and exposes `requireFeature()` / `requireAnyFeature()` guards.
- `app/Views/business/feature_unavailable.php` shows a professional upgrade-required screen when a user directly opens a feature not included in the plan.

Super Admin can add future feature identifiers such as `reviews`, `bookings`, `gst_invoices`, `coupons`, `analytics`, `ai_tools`, `custom_domain`, `email_automation` or `sms_automation` without rewriting the subscription structure. Code-enforced modules should check the stable identifier through `FeatureService` / `BaseBusinessController`; limits should remain in `limits_json` so quotas are separate from feature access.

## Subscription-gated Public Business Website system

`Public Business Website` is a paid/configurable feature in `platform_features` with identifier `public_website`. A business does **not** receive a live website just because it registered. Every business may keep default website settings internally, but website management and public rendering are locked until the active subscription plan includes `public_website`.

Every business has one dynamic website URL based on its slug, and it becomes publicly available when business/subscription status is allowed and the current plan includes `public_website`:

```text
/p/{business-slug}
```

The website is not a static HTML file. It loads content directly from tenant-scoped database tables:

- `businesses` for branding/contact/about information
- `business_settings` for website theme, layout, SEO and section visibility
- `categories` where `is_active = 1`, `visible_on_website = 1` and `categories` is included
- `listings` where `status = active`, `visible_on_website = 1` and the listing type is allowed by `product_management` and/or `service_management`
- `offers` where `offers` is included, `is_active = 1`, `visible_on_website = 1` and date rules match
- public forms only when `enquiries` / `orders` and relevant request subtype features are included

Business owners manage this from:

```text
/business/website
```

If the active plan includes `public_website`, they can open the website screen. Theme presets/colors/layout require `website_customization`, logo/cover controls require `custom_branding`, SEO fields require `basic_seo`, and public listing/category/offer controls depend on the corresponding feature modules. Listings/categories/offers also have a **Visible on Website** checkbox only when website access is included.

Super Admin can enable/disable a business website from the business detail screen. Website access also respects business status and subscription rules.

## Subscription flow

1. Super Admin registers/reviews features in `/admin/features`.
2. Super Admin creates subscription plans in `/admin/plans` and selects included features + optional limits from the registry.
3. Super Admin creates or approves a business.
4. Super Admin assigns a plan and subscription period from the business detail screen.
5. Business portal and public website access are calculated from:
   - business status (`active`, `approved`, `pending`, `suspended`, etc.)
   - subscription status
   - subscription expiry date
   - grace period end date
   - selected plan features in `subscription_plan_features`
6. Manual payments are recorded in `payments`.
7. A paid payment with a plan and period can renew/update the current subscription.
8. Gateway fields (`gateway_provider`, `gateway_payment_id`, `metadata`) are already available for future Razorpay/Stripe/etc. integration.

Effective business access states:

- `active`: normal portal access
- `expiring`: normal access, warning state
- `grace_period`: temporary access after expiry
- `expired`: write actions restricted and public portal unavailable
- `suspended`: access restricted by admin
- `pending`: business awaiting approval

## Notifications

Notifications are stored internally in `notifications` with:

- audience: `super_admin` or `business`
- optional `business_id`
- type and related entity fields

The structure is ready for future email, WhatsApp, SMS or push delivery adapters.

## File upload security

- Images are uploaded under `public/uploads/businesses/{business_id}/...`.
- Only JPG, PNG, WEBP and GIF MIME types are accepted.
- File names are randomized.
- Upload size is controlled by `UPLOAD_MAX_MB`.
- `public/uploads/.htaccess` disables PHP/executable serving from uploads.

## Testing tenant isolation

After importing optional demo data:

1. Login as `owner@luckytour.test`.
2. Open `/business/listings` and confirm only Lucky Tour & Travel listings appear.
3. Manually try opening another tenant listing edit URL such as:

   ```text
   http://localhost/multi-business-subscription-platform/public/business/listings/5/edit
   ```

   Listing `5` belongs to ElectroHub in the demo seed. Lucky's owner should receive a 404 page.

4. Logout and login as `owner@electrohub.test`.
5. Confirm Lucky enquiries/orders/listings are not visible.
6. Optional CLI smoke test:

   ```bash
   php scripts/tenant_isolation_smoke_test.php
   ```

## Development notes

### Adding a new business-specific module

Example: adding a reviews or booking module.

1. Register a stable feature in `/admin/features`, for example `reviews` or `bookings`.
2. Add the feature to one or more plans in `/admin/plans`.
3. Create a table with `business_id` and optional parent IDs.
4. Add foreign keys to `businesses` and any parent tables.
5. Build a Business controller that uses `Auth::businessId()` through `BaseBusinessController`.
6. Call `$this->requireFeature('reviews', 'Reviews')` or the relevant identifier before reads/writes.
7. Every SELECT/UPDATE/DELETE must include `business_id = ?`.
8. If the feature has quotas, store limits in `subscription_plan_features.limits_json` and read them through `featureLimit()`.
9. Add Super Admin read-only visibility only if platform-wide oversight is required.
10. Add notifications/activity logs where relevant.

This keeps the core architecture tenant-neutral and lets electronics shops, travel agencies, salons, restaurants, consultants or future business types coexist safely while plans remain configurable.

## Troubleshooting

### Database connection failed

- Confirm MySQL is running in XAMPP.
- Confirm database name in `.env` matches phpMyAdmin.
- Confirm root password. Default XAMPP often uses an empty password.

### 404 for pretty URLs

- Make sure Apache `mod_rewrite` is enabled.
- Use the `/public` URL if the root redirect is not working.
- Confirm `public/.htaccess` exists.

### Uploads fail

- Check PHP `upload_max_filesize` and `post_max_size` in `php.ini`.
- Ensure `public/uploads` is writable.
- Only image files are accepted.

### Blank page

- Check `storage/logs/app.log`.
- Temporarily set `APP_DEBUG=true` in `.env` only on local development.

## Known limitations in this foundation

- No live payment gateway yet; payments are manually recorded but gateway columns are ready.
- No automated invoices/GST module yet.
- No email/WhatsApp/SMS sending yet; internal notifications are implemented.
- No dedicated customer CRM screen yet; customer records are created from enquiries/orders and visible through those modules.
- No employee permission UI yet, although roles are ready for business staff expansion.
- No custom domain/subdomain support yet; public business websites use slugs.
- No drag-and-drop website builder yet; current website customization uses safe presets and controlled settings.
- Image gallery field exists, but the UI currently manages one main listing image.

These are intentionally left as future modules so the initial version stays maintainable and secure.
