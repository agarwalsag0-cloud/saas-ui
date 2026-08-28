# Auth & tenant-isolation security harness (test-only)

This folder is **never loaded in production**. It lets the app's real
controllers, guards and Stitch views run against an in-process SQLite
fixture database — useful for CI, sandboxes, or a quick pre-deploy check
without MySQL.

## What it covers

`testing/run_tests.php` executes the actual `app/` code (Router guards,
`Auth`, `CustomerAuth`, `BaseBusinessController`, `BaseAdminController`,
service layer, `View::render`) against fixtures derived from the real
`database/install.sql`:

| Group | Scenarios |
|---|---|
| Portal separation | S01–S07, S20–S22 (customer/business/anon cross-access, real login endpoints, Google-created customers never mint business users) |
| Tenant isolation | S04, S16, S17 (URL/ID manipulation, spoofed `business_id` in POST bodies) |
| Subscription & feature gating | S08, S09 (expired subscription writes, locked feature via direct URL) |
| Approval vs publishing | S10–S13 (directory/sitemap visibility, suspended tenant offline, pending publish blocked even though setup is allowed) |
| Credentials & sessions | S14, S15, S18, S19 (role-bound logins, session tampering, inactive users, logout) |
| Render smoke | S23–S26 (every portal renders end-to-end; owner preview forces `noindex`) |

## Running on XAMPP / any PHP 8.1+ CLI

```bash
php testing/setup_db.php      # builds storage/testing.sqlite (throwaway)
php testing/run_tests.php     # runs all 26 scenarios, exits non-zero on failure
```

Requires `pdo_sqlite` (enabled by default in XAMPP). The harness never
touches your MySQL database.

## How it hooks into the app

`App\Core\Database` checks `DB_DRIVER=sqlite` (default: `mysql`) and then
loads `testing/sqlite/compat.php` — a thin PDO subclass that translates the
few MySQL-isms the app uses (`NOW()`, `CURDATE()`, `DATE_ADD/DATE_SUB
INTERVAL`, `YEAR()/MONTH()/DATE_FORMAT()`, `INSERT … ON DUPLICATE KEY` for
upsert tables) to SQLite equivalents. Set the env vars only for the harness;
your `.env` keeps MySQL.

## Caveats

* MySQL-specific features (full-text, `JSON_TABLE`, `ON UPDATE` clauses) are
  not exercised here — import `database/install.sql` on real MySQL before
  production use (see docs/TESTING.md).
* The sandbox driver that ran these in CI-style is outside the repo; the
  XAMPP CLI path above is the supported one.
