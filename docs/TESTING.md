# Testing Checklist

Use this checklist after importing `database/mbsp_database.sql` (one file: schema + registry + demo content; tenant states, publish states, staff and customer logins included — see its header comment).

## Initial setup

- Open `/setup` after importing `install.sql`.
- Create your own Super Admin email/password.
- Confirm the app logs you into `/admin`.
- Logout and log in again with the same credentials.
- Confirm `/setup` no longer allows creating another first admin after one Super Admin exists.

If you imported `demo_seed.sql`, you can also test with:

- Super Admin: `admin@platform.local` / `password`
- Business Owner: `owner@luckytour.test` / `password`
- Business Owner: `owner@electrohub.test` / `password`

## Public registration

- Open `/register-business`.
- Register a new business and owner credentials.
- If active plans exist, choose one plan that includes Public Business Website and another test with a plan that does not.
- Confirm the owner can log in but sees restricted/pending access until approval.
- Login as Super Admin, approve/activate the business and activate the selected subscription or assign another plan.

## Auth — entry points & separation

The platform exposes three independent sign-in doors; they are never interchangeable:

| Entry | URL | Accepted identities | Post-login landing |
|---|---|---|---|
| Public chooser | `/login` | — (links only) | — |
| Customer | `/customer/login` (+ `/auth/google/*`) | `customer_accounts` only | `/customer` (or the page that required login) |
| Business | `/business/login` | `business_owner`, `business_staff` | `/business` |
| Super Admin | `/admin/login` | `super_admin` only | `/admin` |

- The `/login` chooser offers only **Customer** and **Business**. No public page, nav, footer or error screen links the admin entry.
- Same credentials must NOT work across doors: try the admin account on `/business/login` and a business owner on `/admin/login` — both must fail with the generic *"Invalid email or password."*
- First run: `/setup` is reachable only while no Super Admin exists; after creation it redirects to `/admin/login` forever.
- Failed admin sign-ins lock the portal for 10 minutes after 5 attempts (per session); business login throttles identically.
- Idle sessions expire after `SESSION_IDLE_MINUTES` (default 120) and the user is told to sign in again.
- Session IDs regenerate on login and logout; logging out of `/admin/login` sends admins back to the admin entry, everyone else to `/login`.
- Attempt invalid password and confirm generic validation message.
- Confirm passwords are stored as hashes in `users.password_hash` / `customer_accounts.password_hash`, never plain text.

### Automated security suite (no MySQL needed)

```
php testing/setup_db.php
php testing/run_tests.php
```

All 26 scenarios must print `ALL PASS (26)`. The suite exercises the real guards: portal cross-access (customer→business, business→admin, anon→everything), tenant isolation by ID/URL/POST manipulation, feature-lock direct URLs, expired-subscription write blocking, pending/rejected/suspended public visibility, pending publish attempts, session tampering, logout invalidation, the real admin login flow, and full render smoke passes for every portal. See `testing/README.md`.

## Super Admin

- View `/admin` dashboard.
- Open `/admin/features`.
  - Add a test feature with a unique identifier such as `reviews`.
  - Mark it active and available for plans.
  - Edit the name/category/icon and save.
- Open `/admin/plans`.
  - Create a plan with monthly/yearly prices.
  - Select included features from the registry.
  - Add an optional limit such as `25` or `{"max_items":25}` for product/service management.
  - Edit the plan and confirm selected features and limits persist.
  - Deactivate/reactivate a plan.
- Add a new business with owner credentials.
- Approve and activate the business.
- Suspend and reactivate a business.
- Assign/update a subscription from the business detail screen.
- Confirm the business detail screen shows:
  - assigned plan features,
  - currently active calculated access,
  - inactive access when business/subscription status blocks usage.
- Enable/disable a business website from business detail and confirm public page behavior.
- Record a paid payment and confirm subscription dates update.
- Confirm activity logs and notifications appear.

## Business Owner

Test using at least two plans: one with most modules included and one with selected modules removed.

- Confirm the Business Portal menu only shows modules included in the active plan.
- Update business profile when `business_profile` is included.
- Remove `business_profile` from the plan and directly open `/business/profile`; expected professional Feature Not Included screen.
- Open Website Settings only when both `public_website` and `website_customization` are included.
- Confirm SEO controls only appear when `basic_seo` is included.
- Confirm logo/cover uploads only appear when `custom_branding` is included.
- Use View My Website and Copy Website Link when `public_website` is included.
- Create/edit/deactivate categories and toggle Visible on Website when `categories` + `public_website` are included.
- Create/edit/archive listings:
  - product type requires `product_management`,
  - service/package/booking/custom types require `service_management`,
  - featured checkbox requires `featured_listings`,
  - stock tracking requires `inventory`,
  - Visible on Website requires `public_website`.
- If a product/service limit exists, create records until the limit and confirm the limit-reached upgrade screen appears.
- Create/update/delete offers when `offers` is included.
- View/update enquiry status and notes when `enquiries` is included.
- View/update order/request status and notes when `orders` is included.
- Confirm order request subtypes respect product/service/booking/service-request features.
- Confirm subscription screen shows current status, payment history and included current-plan features.
- Confirm notifications screen only opens when `notifications` is included.

## Public Portal

- Visit `/p/lucky-tour-travel` if demo seed was imported, or visit the slug of an approved business whose active plan includes `public_website`.
- Confirm the business website uses selected theme/colors/logo/cover when corresponding features are included.
- Toggle a listing/category/offer Hidden on Website and confirm it disappears publicly.
- Remove `categories` from the plan and confirm categories do not appear publicly.
- Remove `offers` from the plan and confirm offers do not appear publicly.
- Remove `featured_listings` from the plan and confirm featured section/badges are hidden.
- Remove `product_management` from the plan and confirm product listings/details/forms are not publicly accessible.
- Remove `service_management` from the plan and confirm service/package/booking/custom listings/details/forms are not publicly accessible.
- Remove `enquiries` and confirm public enquiry forms disappear and direct POST is rejected.
- Remove `orders` and confirm public request forms disappear and direct POST is rejected.
- For plans with `orders` but without `booking_requests`, confirm booking request options are not shown and direct booking POST is rejected.
- Submit an enquiry/order on an included feature and confirm the business owner sees the submitted records.
- Expire or suspend the subscription and confirm the public website shows unavailable/404 behavior instead of public content.

## Tenant Isolation

- Login as one business owner.
- Confirm only that business's records are visible.
- Try opening another tenant's listing edit URL manually, for example `/business/listings/5/edit` when that listing belongs to another business.
- Expected result: 404/403, no data leakage.
- Try changing IDs in category, offer, enquiry and order URLs/forms.
- Expected result: action rejected and no other tenant data is shown or changed.
- Login as another business and repeat.
- Optional: run `php scripts/tenant_isolation_smoke_test.php` after importing demo data.

## Subscription Restrictions

- Use an expired/suspended demo tenant, or manually expire/suspend a tenant from Super Admin.
- Confirm restricted status is shown.
- Try creating/editing listings; expected redirect to subscription restriction.
- Visit that business public portal; expected unavailable message.

## Local technical checks

Run these in your local XAMPP/PHP environment because this packaged workspace may not include PHP/MySQL CLIs:

```bash
php -l public/index.php
php -l app/Services/FeatureService.php
php -l app/Controllers/Business/BaseBusinessController.php
php -l app/Controllers/PublicPortalController.php
php -l app/Controllers/Admin/PlanController.php
php -l app/Controllers/Admin/FeatureController.php
```

Also import `database/mbsp_database.sql` into a new phpMyAdmin database to verify SQL compatibility (it is the same schema the harness translates, plus demo content).

## Responsive UI

- Test dashboard, tables, forms and public portal on:
  - desktop
  - tablet width
  - mobile width

## Website publish, review & SEO (new workflow)

- Fresh install: a configured website is **not** public until the owner presses **Publish website** in `/business/website`. Verify `/p/{slug}` shows the "not published yet" page for anonymous visitors.
- As the owner, open **Preview** from `/business/website` (or the dashboard card). Verify the amber preview banner, `noindex, nofollow` in the page source, and that the page is not linked from the directory.
- Admin preview: `/admin/businesses/{id}/preview` works even for unpublished sites; anonymous visitors cannot (per-viewer gate).
- Review flow: business presses **Submit for review** while status is `pending` → status becomes `under_review` and appears on `/admin/businesses?status=under_review`. Admin approves (site becomes eligible) or **Requests changes** with a note → owner receives an in-app notification carrying the note, and `approved_at` is cleared so re-approval is required.
- Publish requires: plan includes `Public Business Website` + business approved + admin toggle on. Test each gate by turning it off — publish button is disabled with an explanatory notice, and `/p/{slug}` + directory drop the site while config is preserved.
- `website_enabled` (admin kill-switch) hides the site immediately even when published.
- SEO: with an active subscription that indexes the site, verify `/sitemap.xml` lists only approved + published + directory-visible businesses with an active subscription; `/robots.txt` disallows `/admin`, `/business`, `/customer`, `/p` preview; published pages carry canonical + og tags; `allow_indexing` off in Website Settings removes the site from the sitemap.
- Directory: test `/` with free-text search, category and city filters, and pagination; unpublished/pending tenants must never appear.

## Customer portal (new)

- Register at `/customer/register` (email + password). Verify immediate login, no approval step, and a "My Account" link replacing "Portal Login" in the public nav.
- Login screen shows the Google button only when `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` are configured; test the full round-trip if configured. Password login still works for Google-created accounts after **Set password** in the profile.
- From a business website, submit an enquiry/order while logged in as a customer: the form is prefilled, the note says it will be saved to your account, and the record appears in **My Enquiries / My Orders** with its status timeline.
- Records submitted anonymously (logged out) are NOT attached to any account and the sign-in hint is shown instead; only logged-in submissions appear in My Enquiries / My Orders.
- Isolation: create Customer A and Customer B; each must see only their own enquiries/orders/notifications. Opening another user's `/customer/enquiries/{id}` URL must return 404/403 — never data.
- Profile: avatar upload + remove, name/phone/email update, password change with current-password check; Google unlink only when a password exists.
- Business side: the enquiry/order still lands in the business portal exactly as before (customer link is additive only).
