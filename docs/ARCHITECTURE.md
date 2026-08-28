# Architecture Notes

## Request lifecycle

1. Apache routes requests to `public/index.php` through `.htaccess`.
2. `app/bootstrap.php` loads environment variables, autoloading and session settings.
3. `App\Core\Router` matches a route.
4. Controller checks role and, for business controllers, tenant context.
5. Business controllers load subscription status and feature access through `BaseBusinessController` / `FeatureService`.
6. Controller uses PDO prepared statements.
7. Views render escaped output through the `e()` helper.

## Credential and role flow

- `database/mbsp_database.sql` is the single phpMyAdmin import for a test-ready install: it concatenates the schema/registry (`install.sql` content) with demo tenant/customer content. `database/install.sql` remains the schema-only source of truth (used by the `testing/` harness) and never forces a default Super Admin credential.
- `/setup` creates the first Super Admin when no Super Admin exists.
- Super Admin can create business owners from `/admin/businesses/create`.
- `/register-business` lets a new business owner set credentials and optionally select a preferred active plan during registration; the business and selected subscription remain pending until approval/activation.
- `database/demo_seed.sql` is the older standalone seed kept for reference; its adapted content lives inside `mbsp_database.sql` (with no `super_admin` row and no dangling references).

## Role separation

- Super Admin controllers extend `App\Controllers\Admin\BaseAdminController`.
- Business controllers extend `App\Controllers\Business\BaseBusinessController`.
- Business controllers load `Auth::businessId()` once and use it for tenant-scoped queries.
- Super Admin routes use platform-wide access and must not be reused as business-facing endpoints.

## Feature-based subscription architecture

Plans are configurable product packages, not hard-coded tiers. Feature definitions live once in the registry and plans reference them through a pivot table.

Access chain:

```text
Business → active/allowed subscription → subscription plan → subscription_plan_features → platform_features → module access
```

Core tables:

- `platform_features`: central feature/module registry.
  - `identifier` is the stable code key, e.g. `public_website`, `product_management`, `orders`.
  - `name`, `description`, `category`, `icon`, `is_active`, `available_for_plans`, `sort_order` are metadata for Super Admin plan building.
- `subscription_plan_features`: many-to-many plan-feature relation.
  - `enabled` controls inclusion.
  - `limits_json` stores optional quotas such as `{ "max_items": 50 }`.
- `subscription_plans`: plan metadata and prices.
  - `monthly_price` and `yearly_price` are data-driven; pricing is not hard-coded in PHP.
  - Legacy `billing_cycle`, `price`, and `features` remain for compatibility, but module access comes from the pivot table.

Important files:

- `app/Services/FeatureService.php`
  - reads the registry
  - groups features for admin UI
  - syncs plan-feature assignments
  - calculates a business's current features from business status + subscription + plan
  - exposes limit helpers
- `app/Controllers/Business/BaseBusinessController.php`
  - injects `$featureAccess` into business views
  - provides `requireFeature()` and `requireAnyFeature()`
  - renders the upgrade-required screen when a feature is unavailable
- `app/Views/business/feature_unavailable.php`
  - professional "Feature Not Included" / "Plan Limit Reached" message

Feature access and limits are deliberately separate. A plan can include `product_management` and also set `limits_json = {"max_items":25}`. Future modules such as WhatsApp automation, invoices/GST, coupons, reviews, bookings, analytics, AI tools, custom domains, storage quotas, user limits or email/SMS automation can be added by registering identifiers and enforcing those identifiers in controllers.

Downgrades lock access but do not delete tenant data. Website settings, branding, listings, offers, enquiries, orders and customers remain stored. If the tenant later upgrades to a plan containing the feature again, the saved configuration/content is reused.

## Public website architecture

Business websites are dynamic database-driven pages, not generated static sites.

- Public route: `/p/{slug}`
- Listing route: `/p/{slug}/listing/{listingSlug}`
- The requested slug first identifies the business tenant.
- The public website is available only when:
  - business status allows public access,
  - subscription status/expiry/grace allows access,
  - the active plan includes `public_website`,
  - Super Admin has not disabled the website,
  - business visibility settings allow the content.
- All categories, listings, offers, enquiries and orders are loaded or inserted with that tenant's `business_id`.
- `business_settings` controls website enable/disable, directory listing, theme preset, layout, colors, SEO metadata and section visibility.
- Public queries only load records marked visible, for example `visible_on_website = 1`.
- Public output is further filtered by feature access:
  - categories require `categories`,
  - product listings require `product_management`,
  - service/package/booking/custom listings require `service_management`,
  - offers require `offers`,
  - enquiry form requires `enquiries`,
  - request/order form requires `orders` plus relevant subtype features where applicable.

Public websites expose only public-intended business profile, public listings/categories/offers and public forms. They never expose internal notes, payment details, analytics, private customers or admin data.

## Tenant isolation pattern

Never trust IDs from the browser alone.

Bad:

```sql
SELECT * FROM listings WHERE id = ?
```

Good:

```sql
SELECT * FROM listings WHERE id = ? AND business_id = ?
```

The code follows the second pattern for categories, listings, offers, customers, enquiries, orders, payments, notifications and activity logs. Business users never choose `business_id` from a request parameter; it comes from the authenticated session.

## Backend feature enforcement pattern

Business modules should not only hide navigation links. Controllers must enforce access before reads/writes.

Example:

```php
$this->requireFeature('offers', 'Offers');
```

For modules with alternative features:

```php
$this->requireAnyFeature(['product_management', 'service_management'], 'Product/Service Management');
```

For limits:

```php
$limit = $this->featureLimit('product_management', 'max_items');
```

If access is missing, the controller renders `business.feature_unavailable` instead of serving the module.

## Future integration points

- Payment gateway: insert/update `payments`, then update `business_subscriptions` in a transaction.
- WhatsApp/email/SMS: create delivery services that consume `notifications` and register `whatsapp_button`, `email_automation`, or `sms_automation`-style features.
- Invoices/GST: create invoice tables linked to `business_id`, `payment_id` and `subscription_id`, then gate by an invoice feature identifier.
- Reviews/bookings/coupons: add tables with `business_id`, register the feature, assign it to plans and enforce it in controllers.
- Employee permissions: extend `users.role`, add permission tables and optionally combine staff permissions with plan features.
- API/mobile app: reuse services and enforce tenant context through tokens.
