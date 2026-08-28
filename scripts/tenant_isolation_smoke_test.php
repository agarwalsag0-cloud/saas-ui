<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run this script from the command line.\n");
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$pdo = Database::pdo();

$businesses = $pdo->query('SELECT id, name FROM businesses WHERE status <> "archived" ORDER BY id ASC LIMIT 2')->fetchAll();
if (count($businesses) < 2) {
    exit("Need at least two businesses to run the tenant isolation smoke test.\n");
}

[$a, $b] = $businesses;
$listingA = $pdo->prepare('SELECT id, title FROM listings WHERE business_id = ? LIMIT 1');
$listingA->execute([(int) $a['id']]);
$listingA = $listingA->fetch();

$listingB = $pdo->prepare('SELECT id, title FROM listings WHERE business_id = ? LIMIT 1');
$listingB->execute([(int) $b['id']]);
$listingB = $listingB->fetch();

if (!$listingA || !$listingB) {
    exit("Need at least one listing in each of the first two businesses. Import demo_seed.sql or add listings.\n");
}

$checks = [];
$stmt = $pdo->prepare('SELECT COUNT(*) FROM listings WHERE id = ? AND business_id = ?');
$stmt->execute([(int) $listingB['id'], (int) $a['id']]);
$checks[] = ['Business A cannot load Business B listing through tenant-scoped query', (int) $stmt->fetchColumn() === 0];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM enquiries WHERE business_id = ? AND id IN (SELECT id FROM enquiries WHERE business_id = ?)');
$stmt->execute([(int) $a['id'], (int) $b['id']]);
$checks[] = ['Business A enquiry scope excludes Business B enquiries', (int) $stmt->fetchColumn() === 0];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE business_id = ? AND id IN (SELECT id FROM orders WHERE business_id = ?)');
$stmt->execute([(int) $a['id'], (int) $b['id']]);
$checks[] = ['Business A order scope excludes Business B orders', (int) $stmt->fetchColumn() === 0];

$failed = 0;
foreach ($checks as [$label, $ok]) {
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$ok) {
        $failed++;
    }
}

if ($failed > 0) {
    exit(1);
}

echo "Tenant isolation smoke test passed for {$a['name']} and {$b['name']}.\n";
