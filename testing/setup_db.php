<?php

declare(strict_types=1);

/**
 * Builds the SQLite fixture database for the auth/security harness.
 *
 * The schema is derived from the real database/install.sql (mechanically
 * translated), so tests run against the production table definitions.
 * Seed data below is only what the security scenarios need.
 *
 * Usage: php testing/setup_db.php
 */

putenv('DB_DRIVER=sqlite');
$_ENV['DB_DRIVER'] = 'sqlite';

$root = dirname(__DIR__);
$dbPath = $root . '/storage/testing.sqlite';
if (is_file($dbPath)) {
    unlink($dbPath);
}
putenv('DB_SQLITE_PATH=' . $dbPath);
$_ENV['DB_SQLITE_PATH'] = $dbPath;

if (!defined('STDERR')) {
    define('STDERR', fopen('php://stderr', 'w'));
}
if (!defined('STDOUT')) {
    define('STDOUT', fopen('php://stdout', 'w'));
}

require_once $root . '/app/bootstrap.php';

use App\Core\Database;

$pdo = Database::pdo();
$schema = file_get_contents($root . '/database/install.sql');
// strip line comments so statements are not polluted by leading -- lines
$schema = preg_replace('/^\s*--.*$/m', '', $schema) ?? $schema;
if ($schema === false) {
    fwrite(STDERR, "install.sql not found\n");
    exit(1);
}

/** Split on semicolons that sit outside string literals. */
function split_statements(string $sql): array
{
    $out = [];
    $buf = '';
    $len = strlen($sql);
    $inS = false;
    $inD = false;
    $inBt = false;
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        if ($c === "'" && !$inD && !$inBt) {
            $inS = !$inS;
        } elseif ($c === '"' && !$inS && !$inBt) {
            $inD = !$inD;
        } elseif ($c === '`' && !$inS && !$inD) {
            $inBt = !$inBt;
        }
        if ($c === ';' && !$inS && !$inD && !$inBt) {
            $out[] = $buf;
            $buf = '';
            continue;
        }
        $buf .= $c;
    }
    if (trim($buf) !== '') {
        $out[] = $buf;
    }
    return $out;
}

function translate_ddl(string $stmt): string
{
    if (!preg_match('/^\s*(CREATE TABLE|DROP TABLE)/i', $stmt)) {
        return $stmt;
    }
    $lines = preg_split('/\R/', $stmt) ?: [];
    $keep = [];
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '' || str_starts_with($t, '--')) {
            continue;
        }
        if (preg_match('/^(KEY|INDEX|UNIQUE KEY|CONSTRAINT|FULLTEXT|PRIMARY KEY)\b/i', $t)) {
            continue; // indexes/FKs not needed for auth isolation tests
        }
        $keep[] = $line;
    }
    $out = implode("\n", $keep);
    $out = preg_replace('/COMMENT\s+\'(?:\\\\.|[^\'\\\\])*\'/i', '', $out) ?? $out;
    $out = preg_replace('/\bENGINE\s*=\s*\w+[^;]*$/im', '', $out) ?? $out;
    $out = preg_replace('/\bAUTO_INCREMENT\s*=\s*\d+/i', '', $out) ?? $out;
    // id column with inline PRIMARY KEY: `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
    $out = preg_replace('/`?(\w+)`?\s+(?:BIGINT|INT|MEDIUMINT|SMALLINT|TINYINT)(?:\s+UNSIGNED)?(?:\(\d+\))?\s+(NOT NULL\s+)?AUTO_INCREMENT\s+PRIMARY KEY/i', '$1 INTEGER PRIMARY KEY AUTOINCREMENT', $out) ?? $out;
    // id column declared separately from PRIMARY KEY line (PK line already dropped)
    $out = preg_replace('/`?(\w+)`?\s+(?:BIGINT|INT|MEDIUMINT|SMALLINT|TINYINT)(?:\s+UNSIGNED)?(?:\(\d+\))?\s+NOT NULL\s+AUTO_INCREMENT/i', '$1 INTEGER PRIMARY KEY AUTOINCREMENT', $out) ?? $out;
    $out = preg_replace('/\bON UPDATE CURRENT_TIMESTAMP\b/i', '', $out) ?? $out;
    $out = preg_replace('/\bENUM\s*\((?:[^()]|\([^()]*\))*\)/i', 'TEXT', $out) ?? $out;
    $out = preg_replace('/\b(TINYINT|SMALLINT|MEDIUMINT|BIGINT|INT|INTEGER)(\(\d+\))?(?:\s+UNSIGNED)?(?=\W)/i', 'INTEGER', $out) ?? $out;
    $out = preg_replace('/\b(DECIMAL|NUMERIC|FLOAT|DOUBLE|REAL)\s*(\(\s*[\d.,]+\s*\))?/i', 'REAL', $out) ?? $out;
    $out = preg_replace('/\b(VARCHAR|CHAR)\s*\(\d+\)/i', 'TEXT', $out) ?? $out;
    $out = preg_replace('/\b(DATETIME|TIMESTAMP|DATE|TIME|YEAR)\b/i', 'TEXT', $out) ?? $out;
    $out = preg_replace('/\b(JSON|LONGTEXT|MEDIUMTEXT|TINYTEXT|BLOB)\b/i', 'TEXT', $out) ?? $out;
    $out = preg_replace('/,\s*(?=\))/s', "\n)", $out);
    $out = preg_replace('/\)\s*;?\s*$/m', ')', $out);
    $out = rtrim(trim($out), ';');
    // final closing paren only once
    $out = preg_replace('/\)\s*\)+\s*$/', ')', $out) ?? $out;
    return $out;
}

$created = 0;
$inserted = 0;
foreach (split_statements($schema) as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') {
        continue;
    }
    if (preg_match('/^\s*INSERT INTO/i', $stmt)) {
        $pdo->exec($stmt);
        $inserted++;
        continue;
    }
    $translated = translate_ddl($stmt);
    if (preg_match('/^\s*(CREATE TABLE|DROP TABLE)/i', $translated)) {
        try {
            $pdo->exec($translated);
        } catch (\Throwable $e) {
            echo "FAILED DDL:\n{$translated}\n";
            throw $e;
        }
        $created++;
    }
}

echo "schema tables: {$created}, seed statements: {$inserted}\n";

// ---------------------------------------------------------------------------
// Harness fixtures
// ---------------------------------------------------------------------------
$now = date('Y-m-d H:i:s');
$future = date('Y-m-d', strtotime('+40 days'));
$past = date('Y-m-d', strtotime('-10 days'));
$hash = password_hash('password', PASSWORD_DEFAULT);

function id_of(PDO $pdo, string $table, string $where, array $params = []): int
{
    $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE {$where} LIMIT 1");
    $stmt->execute($params);
    $id = $stmt->fetchColumn();
    if ($id === false) {
        fwrite(STDERR, "fixture lookup failed: {$table} {$where}\n");
        exit(1);
    }
    return (int) $id;
}

$ins = $pdo->prepare('INSERT INTO businesses (name, slug, category, description, city, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)');
$businesses = [
    ['Acme Pending Co', 'acme-pending', 'Services', 'Registration complete, awaiting review.', 'Agra', 'pending', $now, $now],
    ['Bloom approved', 'bloom-approved', 'Flower shop', 'Approved and published tenant.', 'Jaipur', 'approved', $now, $now],
    ['Coral suspended', 'coral-suspended', 'Retail', 'Approved+published but suspended by platform.', 'Delhi', 'suspended', $now, $now],
    ['Dune rejected', 'dune-rejected', 'Retail', 'Rejected application.', 'Pune', 'rejected', $now, $now],
    ['Echo inactive', 'echo-inactive', 'Media', 'Approved but subscription expired.', 'Noida', 'approved', $now, $now],
];
foreach ($businesses as $b) {
    $ins->execute($b);
}

$acme = id_of($pdo, 'businesses', 'slug = ?', ['acme-pending']);
$bloom = id_of($pdo, 'businesses', 'slug = ?', ['bloom-approved']);
$coral = id_of($pdo, 'businesses', 'slug = ?', ['coral-suspended']);
$dune = id_of($pdo, 'businesses', 'slug = ?', ['dune-rejected']);
$echo = id_of($pdo, 'businesses', 'slug = ?', ['echo-inactive']);

// Users (first super admin row may already exist via install seed — it should not)
$uIns = $pdo->prepare('INSERT INTO users (business_id, name, email, password_hash, role, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)');
$uIns->execute([null, 'Root Admin', 'admin@test.local', $hash, 'super_admin', 'active', $now, $now]);
$uIns->execute([$acme, 'Acme Owner', 'owner.acme@test.local', $hash, 'business_owner', 'active', $now, $now]);
$uIns->execute([$bloom, 'Bloom Owner', 'owner.bloom@test.local', $hash, 'business_owner', 'active', $now, $now]);
$uIns->execute([$bloom, 'Bloom Staff', 'staff.bloom@test.local', $hash, 'business_staff', 'active', $now, $now]);
$uIns->execute([$coral, 'Coral Owner', 'owner.coral@test.local', $hash, 'business_owner', 'active', $now, $now]);
$uIns->execute([$dune, 'Dune Owner', 'owner.dune@test.local', $hash, 'business_owner', 'active', $now, $now]);
$uIns->execute([$echo, 'Echo Owner', 'owner.echo@test.local', $hash, 'business_owner', 'active', $now, $now]);
$uIns->execute([$acme, 'Disabled Acme User', 'off.acme@test.local', $hash, 'business_owner', 'inactive', $now, $now]);

// Plans: full-featured vs. no-website. Feature registry rows come from install.sql.
$grows = ['public_website', 'basic_seo', 'product_management', 'service_management', 'enquiries', 'orders', 'notifications', 'categories', 'featured_listings', 'website_customization', 'custom_branding', 'service_requests', 'booking_requests', 'customer_management'];
$pIns = $pdo->prepare('INSERT INTO subscription_plans (name, description, billing_cycle, price, monthly_price, currency, is_active, sort_order, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
$pIns->execute(['Growth', 'Everything included', 'monthly', 2999, 2999, 'INR', 1, 1, $now, $now]);
$pIns->execute(['Starter', 'No public website', 'monthly', 999, 999, 'INR', 1, 2, $now, $now]);
$growth = id_of($pdo, 'subscription_plans', 'name = ?', ['Growth']);
$starter = id_of($pdo, 'subscription_plans', 'name = ?', ['Starter']);

$featIds = [];
foreach ($pdo->query('SELECT id, identifier FROM platform_features')->fetchAll() as $f) {
    $featIds[$f['identifier']] = (int) $f['id'];
}
$spIns = $pdo->prepare('INSERT INTO subscription_plan_features (plan_id, feature_id, enabled, limits_json, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?)');
$limits = ['product_management' => '{"max_items":100}', 'service_management' => '{"max_items":50}'];
$starterIds = ['basic_seo', 'product_management', 'enquiries', 'notifications', 'categories', 'customer_management'];
$allRows = $pdo->query('SELECT id, identifier FROM platform_features WHERE is_active = 1 AND available_for_plans = 1')->fetchAll(PDO::FETCH_ASSOC);
foreach ($allRows as $f) {
    if (in_array($f['identifier'], $starterIds, true)) {
        $spIns->execute([$growth, (int) $f['id'], $limits[$f['identifier']] ?? null, $now, $now]);
    } else {
        $spIns->execute([$growth, (int) $f['id'], $limits[$f['identifier']] ?? null, $now, $now]);
    }
    if (in_array($f['identifier'], $starterIds, true)) {
        $spIns->execute([$starter, (int) $f['id'], $limits[$f['identifier']] ?? null, $now, $now]);
    }
}


$bsIns = $pdo->prepare('INSERT INTO business_subscriptions (business_id, plan_id, status, starts_at, expires_at, renewal_status, price_at_signup, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?)');
$bsIns->execute([$acme, $growth, 'active', $now, $future, 'manual', 2999, $now, $now]);
$bsIns->execute([$bloom, $growth, 'active', $now, $future, 'manual', 2999, $now, $now]);
$bsIns->execute([$coral, $growth, 'active', $now, $future, 'manual', 2999, $now, $now]);
$bsIns->execute([$dune, $starter, 'active', $now, $future, 'manual', 999, $now, $now]);
$bsIns->execute([$echo, $growth, 'expired', $now, $past, 'cancelled', 2999, $now, $now]);
$starterAcme = null; // acme also on Growth above; a no-website tenant:
$pdo->prepare('INSERT INTO businesses (name, slug, category, city, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?)')
    ->execute(['Foxtrot limited', 'foxtrot-limited', 'Retail', 'Surat', 'approved', $now, $now]);
$foxtrot = id_of($pdo, 'businesses', 'slug = ?', ['foxtrot-limited']);
$uIns->execute([$foxtrot, 'Foxtrot Owner', 'owner.foxtrot@test.local', $hash, 'business_owner', 'active', $now, $now]);
$bsIns->execute([$foxtrot, $starter, 'active', $now, $future, 'manual', 999, $now, $now]);

// Website settings rows
$wsIns = $pdo->prepare('INSERT INTO business_settings (business_id, website_enabled, website_published, website_published_at, allow_indexing, show_in_directory, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)');
$wsIns->execute([$acme, 1, 0, null, 1, 1, $now, $now]);          // configured but not published (pending)
$wsIns->execute([$bloom, 1, 1, $now, 1, 1, $now, $now]);         // published + approved
$wsIns->execute([$coral, 1, 1, $now, 1, 1, $now, $now]);         // published but business suspended
$wsIns->execute([$foxtrot, 1, 1, $now, 1, 1, $now, $now]);       // "published" though plan has no website

// Listings for cross-tenant probes
$lIns = $pdo->prepare('INSERT INTO listings (business_id, title, slug, description, type, status, visible_on_website, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?)');
$lIns->execute([$acme, 'Acme Widget', 'acme-widget', 'A', 'product', 'active', 1, $now, $now]);
$lIns->execute([$bloom, 'Bloom Rose', 'bloom-rose', 'B', 'product', 'active', 1, $now, $now]);

// An enquiry owned by Bloom for cross-tenant status update probes
$pdo->prepare('INSERT INTO enquiries (business_id, name, phone, email, message, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)')
    ->execute([$bloom, 'Visitor One', '+919000000000', 'visitor@example.com', 'Do you deliver on Sunday?', 'new', $now, $now]);
$enqBloom = id_of($pdo, 'enquiries', 'business_id = ?', [$bloom]);

// Customers
$cIns = $pdo->prepare('INSERT INTO customer_accounts (name, email, password_hash, status, created_at, updated_at) VALUES (?,?,?,?,?,?)');
$cIns->execute(['Cass Customer', 'cass@test.local', $hash, 'active', $now, $now]);
$cIns->execute(['Dee Customer', 'dee@test.local', $hash, 'active', $now, $now]);
$cass = id_of($pdo, 'customer_accounts', 'email = ?', ['cass@test.local']);

// Cass has an enquiry on Acme, linked to her account
$pdo->prepare('INSERT INTO enquiries (business_id, customer_account_id, name, phone, email, message, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?)')
    ->execute([$acme, $cass, 'Cass Customer', '', 'cass@test.local', 'Custom widget quote please', 'new', $now, $now]);
$cassEnquiry = id_of($pdo, 'enquiries', 'customer_account_id = ?', [$cass]);
$deeEnquiry = $cassEnquiry + 1000; // deliberately nonexistent

file_put_contents($root . '/storage/testing_manifest.json', json_encode([
    'admin' => id_of($pdo, 'users', 'email = ?', ['admin@test.local']),
    'acmeOwner' => id_of($pdo, 'users', 'email = ?', ['owner.acme@test.local']),
    'bloomOwner' => id_of($pdo, 'users', 'email = ?', ['owner.bloom@test.local']),
    'foxtrotOwner' => id_of($pdo, 'users', 'email = ?', ['owner.foxtrot@test.local']),
    'acme' => $acme,
    'bloom' => $bloom,
    'coral' => $coral,
    'dune' => $dune,
    'echo' => $echo,
    'foxtrot' => $foxtrot,
    'bloomListing' => id_of($pdo, 'listings', 'slug = ?', ['bloom-rose']),
    'acmeEnquiry' => $cassEnquiry,
    'bloomEnquiry' => $enqBloom,
    'cass' => $cass,
], JSON_PRETTY_PRINT));

echo "fixtures ready\n";
