<?php

declare(strict_types=1);

/**
 * Auth & tenant-isolation security suite (test harness — never loaded by the app).
 *
 * Runs the REAL controllers/guards from this repo against the SQLite fixture
 * built by testing/setup_db.php. On XAMPP run:
 *
 *   php testing/setup_db.php
 *   php testing/run_tests.php
 *
 * Single scenario (used by the sandbox WASM driver): php testing/run_tests.php --id S01
 * Print expectations as JSON:                     php testing/run_tests.php --expect
 */

$root = dirname(__DIR__);

putenv('DB_DRIVER=sqlite');
$_ENV['DB_DRIVER'] = 'sqlite';
putenv('DB_SQLITE_PATH=' . $root . '/storage/testing.sqlite');
$_ENV['DB_SQLITE_PATH'] = $root . '/storage/testing.sqlite';

if (!defined('STDERR')) {
    define('STDERR', fopen('php://stderr', 'w'));
}
if (!defined('STDOUT')) {
    define('STDOUT', fopen('php://stdout', 'w'));
}

require_once $root . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\CustomerAuth;
use App\Core\Database;
use App\Core\HttpException;

error_reporting(E_ALL);
ini_set('display_errors', '1');

$manifestPath = $root . '/storage/testing_manifest.json';
if (!is_file($manifestPath)) {
    if (in_array(PHP_SAPI, ['cli', 'php', 'wasm'], true) && getenv('MBSP_HARNESS_AUTOSSETUP') !== '0') {
        // One-shot environments (e.g. the WASM sandbox driver) build fixtures on demand.
        require $root . '/testing/setup_db.php';
    }
    if (!is_file($manifestPath)) {
        fwrite(STDERR, "Run `php testing/setup_db.php` first.\n");
        exit(2);
    }
}
$M = json_decode((string) file_get_contents($manifestPath), true);
$pdo = Database::pdo();

// CLI session emulation: no session_start(), but $_SESSION works as plain array.
$reset = static function () {
    $_SESSION = ['_csrf_token' => 'harness-csrf'];
    $_POST = ['_csrf' => 'harness-csrf'];
    $_GET = [];
};
$loginUser = static function (string $email) {
    $stmt = Database::pdo()->prepare('SELECT id, role, business_id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower($email)]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        throw new RuntimeException("fixture user missing: {$email}");
    }
    $_SESSION['user_id'] = (int) $u['id'];
    $_SESSION['role'] = (string) $u['role'];
    $_SESSION['business_id'] = $u['business_id'] !== null ? (int) $u['business_id'] : null;
};
$loginCustomer = static function (string $email) {
    $stmt = Database::pdo()->prepare('SELECT id FROM customer_accounts WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower($email)]);
    $id = $stmt->fetchColumn();
    if (!$id) {
        throw new RuntimeException("fixture customer missing: {$email}");
    }
    $_SESSION['customer_account_id'] = (int) $id;
};

$verify = null; // scenarios may register assertions that must run even after exit()
register_shutdown_function(static function () use (&$verify) {
    if (is_callable($verify)) {
        try {
            $verify();
        } catch (Throwable $e) {
            echo 'VERIFY-ERROR:' . $e->getMessage() . "\n";
        }
    }
    foreach ($_SESSION['_flash'] ?? [] as $f) {
        echo 'FLASH[' . $f['type'] . ']: ' . $f['message'] . "\n";
    }
    echo "END\n";
});

$run = static function (callable $fn): void {
    try {
        $fn();
        echo "COMPLETED\n";
    } catch (HttpException $e) {
        echo 'HTTP:' . $e->statusCode() . "\n";
    } catch (Throwable $e) {
        echo 'ERROR:' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . "\n";
    }
};

/** Directory visibility via the production query builder. */
$directoryIds = static function () use ($pdo): array {
    $svc = new ReflectionClass(\App\Services\WebsiteAccessService::class);
    if ($svc->hasMethod('directoryWhere')) {
        $m = $svc->getMethod('directoryWhere');
        $m->setAccessible(true);
        $where = $m->invoke(null);
        $stmt = $pdo->prepare('SELECT b.id FROM businesses b LEFT JOIN business_settings w ON w.business_id = b.id WHERE ' . $where);
        $stmt->execute();
        return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id'));
    }
    throw new RuntimeException('WebsiteAccessService::directoryWhere() not found');
};

$scenarios = [];

// --- Requirement 17 matrix ---------------------------------------------------

$scenarios['S01_customer_to_business_portal'] = [static function () use ($reset, $loginCustomer, $run) {
    $reset();
    $loginCustomer('cass@test.local');
    $run(static fn () => new \App\Controllers\Business\DashboardController());
}, ['redirect:/business/login']];

$scenarios['S02_customer_to_admin_portal'] = [static function () use ($reset, $loginCustomer, $run) {
    $reset();
    $loginCustomer('cass@test.local');
    $run(static fn () => new \App\Controllers\Admin\DashboardController());
}, ['redirect:/admin/login']];

$scenarios['S03_businessA_to_admin_portal'] = [static function () use ($reset, $loginUser, $run) {
    $reset();
    $loginUser('owner.acme@test.local');
    $run(static fn () => new \App\Controllers\Admin\DashboardController());
}, ['HTTP:403']];

$scenarios['S04_businessA_reads_B_listing'] = [static function () use ($reset, $loginUser, $run, $M) {
    $reset();
    $loginUser('owner.acme@test.local');
    $run(static fn () => (new \App\Controllers\Business\ListingController())->edit((string) $M['bloomListing']));
}, ['HTTP:404']];

$scenarios['S05_anon_to_business_portal'] = [static function () use ($reset, $run) {
    $reset();
    $run(static fn () => new \App\Controllers\Business\DashboardController());
}, ['redirect:/business/login']];

$scenarios['S06_anon_to_customer_portal'] = [static function () use ($reset, $run) {
    $reset();
    $run(static fn () => (new \App\Controllers\Customer\CustomerController())->dashboard());
}, ['redirect:/customer/login']];

$scenarios['S07_anon_to_admin_portal'] = [static function () use ($reset, $run) {
    $reset();
    $run(static fn () => new \App\Controllers\Admin\DashboardController());
}, ['redirect:/admin/login']];

$scenarios['S08_expired_subscription_write_blocked'] = [static function () use ($reset, $loginUser, $run, $M, &$verify) {
    $reset();
    $loginUser('owner.echo@test.local'); // approved tenant but expired subscription
    $enquiryId = $M['bloomEnquiry'];
    $verify = static function () use ($enquiryId) {
        $stmt = Database::pdo()->prepare('SELECT status FROM enquiries WHERE id = ?');
        $stmt->execute([$enquiryId]);
        echo 'VERIFY_ENQUIRY_UNTOUCHED=' . ((string) $stmt->fetchColumn() === 'new' ? '1' : '0') . "\n";
    };
    $_POST['status'] = 'converted';
    $_SERVER['REQUEST_URI'] = '/business/enquiries/' . $enquiryId . '/status';
    $run(static fn () => (new \App\Controllers\Business\EnquiryController())->updateStatus((string) $enquiryId));
}, ['redirect:/business/subscription', 'VERIFY_ENQUIRY_UNTOUCHED=1']];

$scenarios['S09_no_website_feature_direct_url'] = [static function () use ($reset, $loginUser, $run) {
    $reset();
    $loginUser('owner.foxtrot@test.local'); // Starter plan: no public_website feature
    $run(static fn () => (new \App\Controllers\Business\WebsiteController())->edit());
}, ['not included']];

$scenarios['S10_directory_excludes_pending'] = [static function () use ($reset, $M, &$verify, $directoryIds) {
    $reset();
    $verify = static function () use ($M, $directoryIds) {
        $ids = $directoryIds();
        echo 'VERIFY_PENDING_HIDDEN=' . (!in_array($M['acme'], $ids, true) ? '1' : '0') . "\n";
        echo 'VERIFY_APPROVED_PUBLISHED_VISIBLE=' . (in_array($M['bloom'], $ids, true) ? '1' : '0') . "\n";
    };
    $ids = $directoryIds();
    echo 'DIR_IDS=' . implode(',', $ids) . "\n";
}, ['VERIFY_PENDING_HIDDEN=1', 'VERIFY_APPROVED_PUBLISHED_VISIBLE=1']];

$scenarios['S11_rejected_invisible_and_login_blocked'] = [static function () use ($reset, $M, $directoryIds) {
    $reset();
    $ids = $directoryIds();
    echo 'VERIFY_REJECTED_HIDDEN=' . (!in_array($M['dune'], $ids, true) ? '1' : '0') . "\n";
    $err = null;
    $ok = Auth::attempt('owner.dune@test.local', 'password', $err, ['business_owner', 'business_staff']);
    echo 'ATTEMPT_REJECTED=' . ($ok ? 'ALLOWED' : 'BLOCKED') . "\n";
}, ['VERIFY_REJECTED_HIDDEN=1', 'ATTEMPT_REJECTED=BLOCKED']];

$scenarios['S12_suspended_website_offline'] = [static function () use ($reset, $M, $pdo) {
    $reset();
    $stmt = $pdo->prepare('SELECT * FROM businesses WHERE id = ?');
    $stmt->execute([$M['coral']]);
    $biz = $stmt->fetch(PDO::FETCH_ASSOC);
    $sub = \App\Services\SubscriptionService::current((int) $biz['id']);
    $fa = \App\Services\FeatureService::featuresForBusiness((int) $biz['id'], $biz, $sub);
    $access = \App\Services\WebsiteAccessService::evaluate($biz, $sub, null, $fa);
    echo 'PUBLIC=' . ($access['public_access'] ? '1' : '0') . "\n";
    echo 'DIRECTORY=' . ($access['directory_visible'] ? '1' : '0') . "\n";
}, ['PUBLIC=0', 'DIRECTORY=0']];

$scenarios['S13_pending_publish_blocked'] = [static function () use ($reset, $loginUser, $run, $M, $pdo, &$verify) {
    $reset();
    $loginUser('owner.acme@test.local'); // pending business, configured website, Growth plan
    $verify = static function () use ($M, $pdo) {
        $v = $pdo->query('SELECT website_published FROM business_settings WHERE business_id = ' . (int) $M['acme'])->fetchColumn();
        echo 'PUBLISHED=' . ((int) $v === 1 ? '1' : '0') . "\n";
    };
    $run(static fn () => (new \App\Controllers\Business\WebsiteController())->publish());
}, ['redirect:/business/website', 'PUBLISHED=0', 'FLASH[danger]: Your business is awaiting Super Admin approval']];

$scenarios['S14_credentials_not_interchangeable'] = [static function () use ($reset) {
    $reset();
    $e1 = null;
    $e2 = null;
    $a = Auth::attempt('owner.acme@test.local', 'password', $e1, ['super_admin']);
    $b = Auth::attempt('admin@test.local', 'password', $e2, ['business_owner', 'business_staff']);
    $c = Auth::attempt('owner.acme@test.local', 'password', $e3, ['business_owner', 'business_staff']);
    echo 'ADMIN_CREDS_ON_ADMIN_ROLE=' . ($a ? 'LEAK' : 'OK') . "\n";
    echo 'BIZ_CREDS_ON_ADMIN_FORM=' . ($b ? 'LEAK' : 'OK') . "\n";
    echo 'BIZ_CREDS_ON_BIZ_FORM=' . ($c ? 'OK' : 'LEAK') . "\n";
    echo 'ERROR_IS_GENERIC=' . ((strpos((string) $e2, 'Invalid email or password') !== false) ? '1' : '0') . "\n";
}, ['ADMIN_CREDS_ON_ADMIN_ROLE=OK', 'BIZ_CREDS_ON_ADMIN_FORM=OK', 'BIZ_CREDS_ON_BIZ_FORM=OK', 'ERROR_IS_GENERIC=1']];

$scenarios['S15_session_tampering'] = [static function () use ($reset, $M, $run) {
    $reset();
    // Attacker rewrites only the session payload: claims business role on an admin id.
    $_SESSION['user_id'] = $M['admin'];
    $_SESSION['role'] = 'business_owner';
    $_SESSION['business_id'] = $M['acme'];
    $run(static fn () => new \App\Controllers\Business\DashboardController());
}, ['HTTP:403']];

$scenarios['S16_customer_cross_read'] = [static function () use ($reset, $loginCustomer, $run, $M) {
    $reset();
    $loginCustomer('dee@test.local');
    $run(static fn () => (new \App\Controllers\Customer\CustomerController())->enquiryDetail((string) $M['acmeEnquiry']));
}, ['HTTP:404']];

$scenarios['S17_public_enquiry_business_id_spoof'] = [static function () use ($reset, $M, $pdo, $run, &$verify) {
    $reset();
    $_POST += [
        'name' => 'Probe Person',
        'phone' => '+919111111111',
        'email' => 'probe@example.com',
        'message' => 'Is the rose available?',
        'business_id' => (string) $M['acme'], // spoofed: slug is bloom
    ];
    $verify = static function () use ($pdo, $M) {
        $owner = (int) $pdo->query('SELECT business_id FROM enquiries ORDER BY id DESC LIMIT 1')->fetchColumn();
        echo 'VERIFY_LAST_ENQUIRY_IS_BLOOM=' . ($owner === (int) $M['bloom'] ? '1' : '0') . "\n";
    };
    $run(static fn () => (new \App\Controllers\PublicPortalController())->submitEnquiry('bloom-approved'));
}, ['redirect:/p/bloom-approved', 'VERIFY_LAST_ENQUIRY_IS_BLOOM=1']];

$scenarios['S18_inactive_user_cannot_login'] = [static function () use ($reset) {
    $reset();
    $err = null;
    $ok = Auth::attempt('off.acme@test.local', 'password', $err, ['business_owner', 'business_staff']);
    echo 'ATTEMPT_INACTIVE=' . ($ok ? 'ALLOWED' : 'BLOCKED') . "\n";
}, ['ATTEMPT_INACTIVE=BLOCKED']];

$scenarios['S19_logout_invalidates_session'] = [static function () use ($reset) {
    $reset();
    $err = null;
    Auth::attempt('owner.acme@test.local', 'password', $err, ['business_owner', 'business_staff']);
    echo 'PRE=' . (Auth::check() ? 'IN' : 'OUT') . "\n";
    Auth::logout();
    echo 'POST=' . (Auth::check() ? 'IN' : 'OUT') . "\n";
    echo 'CUSTOMER_POST=' . (CustomerAuth::check() ? 'IN' : 'OUT') . "\n";
}, ['PRE=IN', 'POST=OUT', 'CUSTOMER_POST=OUT']];

$scenarios['S20_admin_endpoint_real_flow'] = [static function () use ($reset, $run, &$verify) {
    $reset();
    $_POST += ['email' => 'admin@test.local', 'password' => 'password'];
    $verify = static function () {
        echo 'ADMIN_SESSION=' . ((\App\Core\Auth::role() === 'super_admin') ? 'SET' : 'MISSING') . "\n";
        echo 'INTO_ADMIN=' . (\App\Core\Auth::isSuperAdmin() ? '1' : '0') . "\n";
    };
    $run(static fn () => (new \App\Controllers\AuthController())->adminLogin());
}, ['redirect:/admin', 'ADMIN_SESSION=SET']];

$scenarios['S21_business_form_with_admin_creds'] = [static function () use ($reset, $run) {
    $reset();
    $_POST += ['email' => 'admin@test.local', 'password' => 'password'];
    $run(static fn () => (new \App\Controllers\AuthController())->businessLogin());
}, ['redirect:/business/login', 'FLASH[danger]: Invalid email or password.']];

$scenarios['S22_google_customer_never_becomes_business_user'] = [static function () use ($reset, $M, $pdo, $run, &$verify) {
    $reset();
    // Simulate the row a completed Google sign-in creates (password-less customer)
    $pdo->prepare('INSERT INTO customer_accounts (name, email, google_sub, status, created_at, updated_at) VALUES (?,?,?,?,datetime(\'now\'),datetime(\'now\'))')
        ->execute(['Google Grace', 'grace.google@test.local', 'g-sub-123', 'active']);
    $stmtC = $pdo->prepare('SELECT id FROM customer_accounts WHERE email = ?');
    $stmtC->execute(['grace.google@test.local']);
    $cid = (int) $stmtC->fetchColumn();
    $_SESSION['customer_account_id'] = $cid;
    $verify = static function () use ($pdo) {
        $stmtU = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmtU->execute(['grace.google@test.local']);
        echo 'NO_USERS_ROW=' . ((int) $stmtU->fetchColumn() === 0 ? '1' : '0') . "\n";
    };
    $run(static fn () => new \App\Controllers\Business\DashboardController());
}, ['redirect:/business/login', 'NO_USERS_ROW=1']];


// --- Full-stack render smoke (real controllers + real Stitch views via the
//     production View/render/layout pipeline; silent capture, no browser) -----

$renderAll = static function (callable $fn) use ($run): void {
    ob_start();
    $fn();
    $html = (string) ob_get_clean();
    echo 'RENDER_BYTES=' . strlen($html) . ' ' . (strlen($html) > 1500 ? 'OK' : 'LOW') . "\n";
};

$scenarios['S23_render_business_pages'] = [static function () use ($reset, $loginUser, $run, $renderAll) {
    $reset();
    $loginUser('owner.acme@test.local');
    $run(static function () use ($renderAll) {
        $renderAll(static function () { (new \App\Controllers\Business\DashboardController())->index(); });
        $renderAll(static function () { (new \App\Controllers\Business\ProfileController())->edit(); });
        $renderAll(static function () { (new \App\Controllers\Business\CategoryController())->index(); });
        $renderAll(static function () { (new \App\Controllers\Business\ListingController())->index(); });
        $renderAll(static function () { (new \App\Controllers\Business\EnquiryController())->index(); });
        $renderAll(static function () { (new \App\Controllers\Business\OrderController())->index(); });
        $renderAll(static function () { (new \App\Controllers\Business\OfferController())->index(); });
        $renderAll(static function () { (new \App\Controllers\Business\NotificationController())->index(); });
        $renderAll(static function () { (new \App\Controllers\Business\SubscriptionController())->show(); });
        $renderAll(static function () { (new \App\Controllers\Business\WebsiteController())->edit(); });
    });
}, ['COMPLETED']];

$scenarios['S24_render_admin_pages'] = [static function () use ($reset, $loginUser, $run, $renderAll, $M) {
    $reset();
    $loginUser('admin@test.local');
    $run(static function () use ($renderAll, $M) {
        $renderAll(static function () { (new \App\Controllers\Admin\DashboardController())->index(); });
        $renderAll(static function () { (new \App\Controllers\Admin\BusinessController())->index(); });
        $renderAll(static function () use ($M) { (new \App\Controllers\Admin\BusinessController())->show((string) $M['acme']); });
        $renderAll(static function () { (new \App\Controllers\Admin\ActivityController())->index(); });
        $renderAll(static function () { (new \App\Controllers\Admin\PlanController())->index(); });
        $renderAll(static function () { (new \App\Controllers\Admin\FeatureController())->index(); });
        $renderAll(static function () { (new \App\Controllers\Admin\NotificationController())->index(); });
    });
}, ['COMPLETED']];

$scenarios['S25_render_public_customer_auth'] = [static function () use ($reset, $loginCustomer, $run, $renderAll, $M) {
    $reset();
    $run(static function () use ($renderAll) {
        $renderAll(static function () { (new \App\Controllers\PublicPortalController())->landing(); });
        $renderAll(static function () { (new \App\Controllers\PublicPortalController())->portal('bloom-approved'); });
        $renderAll(static function () { (new \App\Controllers\AuthController())->showChoice(); });
        $renderAll(static function () { (new \App\Controllers\AuthController())->showBusinessLogin(); });
        $renderAll(static function () { (new \App\Controllers\AuthController())->showAdminLogin(); });
        $renderAll(static function () { (new \App\Controllers\Customer\CustomerAuthController())->showLogin(); });
        $renderAll(static function () { (new \App\Controllers\Customer\CustomerAuthController())->showRegister(); });
    });
    $loginCustomer('cass@test.local');
    $run(static function () use ($renderAll, $M) {
        $renderAll(static function () { (new \App\Controllers\Customer\CustomerController())->dashboard(); });
        $renderAll(static function () { (new \App\Controllers\Customer\CustomerController())->enquiries(); });
        $renderAll(static function () { (new \App\Controllers\Customer\CustomerController())->orders(); });
        $renderAll(static function () { (new \App\Controllers\Customer\CustomerController())->notifications(); });
        $renderAll(static function () { (new \App\Controllers\Customer\CustomerController())->profile(); });
        $renderAll(static function () { (new \App\Controllers\Customer\CustomerController())->enquiryDetail((string) $M['acmeEnquiry']); });
    });
}, ['COMPLETED']];

$scenarios['S26_owner_preview_forced_noindex'] = [static function () use ($reset, $loginUser, $run, $M, &$verify) {
    $reset();
    $loginUser('owner.acme@test.local');
    $_GET['preview'] = '1';
    $verify = static function () {
        global $capturedPreview;
        $html = $capturedPreview ?? '';
        echo 'HAS_NOINDEX=' . (str_contains($html, 'noindex') ? '1' : '0') . "\n";
        echo 'HAS_BANNER=' . (stripos($html, 'preview') !== false ? '1' : '0') . "\n";
    };
    $run(static function () {
        ob_start();
        (new \App\Controllers\PublicPortalController())->renderSite('acme-pending', true);
        $GLOBALS['capturedPreview'] = (string) ob_get_clean();
        echo 'PREVIEW_BYTES=' . strlen((string) $GLOBALS['capturedPreview']) . "\n";
    });
}, ['HAS_NOINDEX=1', 'HAS_BANNER=1']];

$expectations = [];
foreach ($scenarios as $id => [$fn, $expects]) {
    $expectations[$id] = $expects;
}

// ---- CLI modes ---------------------------------------------------------------

$envMode = getenv('MBSP_HARNESS_MODE');
$envId = getenv('MBSP_HARNESS_SCENARIO');
$arg1 = $envMode === 'expect' ? '--expect' : (($envId && $envId !== '') ? '--id' : ($argv[1] ?? ''));
$arg2 = ($envId && $envId !== '') ? $envId : ($argv[2] ?? '');

if ($arg1 === '--expect') {
    echo json_encode($expectations, JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

if ($arg1 === '--id') {
    $id = (string) $arg2;
    if (!isset($scenarios[$id])) {
        fwrite(STDERR, "unknown scenario {$id}\n");
        exit(2);
    }
    $scenarios[$id][0]();
    exit(0);
}

// Self-driven loop (needs process spawning so exit()-terminated scenarios isolate)
if (!function_exists('proc_open') || in_array(PHP_SAPI, ['php', 'wasm'], true)) {
    fwrite(STDERR, "This PHP build cannot spawn child processes; run scenarios individually via --id (see testing/README.md).\n");
    exit(2);
}
$fail = 0;
foreach ($scenarios as $id => [$fn, $expects]) {
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --id ' . escapeshellarg($id);
    $out = shell_exec($cmd . ' 2>&1');
    $ok = true;
    foreach ($expects as $token) {
        $token = str_replace('redirect:', '[redirect] ', $token);
        if (strpos((string) $out, $token) === false) {
            $ok = false;
        }
    }
    if (strpos((string) $out, 'ERROR:') !== false) {
        $ok = false;
    }
    echo ($ok ? 'PASS ' : 'FAIL ') . $id . "\n";
    if (!$ok) {
        $fail++;
        echo "--- output ---\n{$out}--------------\n";
    }
}
echo $fail === 0 ? "ALL SCENARIOS PASS (" . count($scenarios) . ")\n" : "FAILURES: {$fail}\n";
exit($fail === 0 ? 0 : 1);
