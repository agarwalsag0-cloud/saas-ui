<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Services\ActivityLogger;
use App\Services\FeatureService;
use App\Services\NotificationService;
use App\Services\SlugService;
use App\Services\SubscriptionService;
use PDO;
use Throwable;

class BusinessController extends BaseAdminController
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = ['b.status <> "archived"'];
        $params = [];
        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));

        if ($q !== '') {
            $where[] = '(b.name LIKE ? OR b.email LIKE ? OR b.phone LIKE ? OR b.city LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($status !== '') {
            $where[] = 'b.status = ?';
            $params[] = $status;
        }

        $whereSql = implode(' AND ', $where);
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM businesses b WHERE ' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = 'SELECT b.*, u.name AS owner_name, u.email AS owner_email,
                       bs.status AS subscription_status, bs.expires_at, sp.name AS plan_name,
                       w.website_enabled, w.show_in_directory, w.website_published,
                       EXISTS (
                           SELECT 1
                           FROM subscription_plan_features spf
                           INNER JOIN platform_features pf ON pf.id = spf.feature_id
                           WHERE spf.plan_id = bs.plan_id
                             AND spf.enabled = 1
                             AND pf.identifier = "public_website"
                             AND pf.is_active = 1
                             AND pf.available_for_plans = 1
                       ) AS includes_public_website
                FROM businesses b
                LEFT JOIN users u ON u.business_id = b.id AND u.role = "business_owner"
                LEFT JOIN business_settings w ON w.business_id = b.id
                LEFT JOIN business_subscriptions bs ON bs.id = (
                    SELECT bs2.id FROM business_subscriptions bs2
                    WHERE bs2.business_id = b.id
                    ORDER BY bs2.expires_at DESC, bs2.id DESC
                    LIMIT 1
                )
                LEFT JOIN subscription_plans sp ON sp.id = bs.plan_id
                WHERE ' . $whereSql . '
                ORDER BY b.created_at DESC
                LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $this->renderAdmin('admin.businesses.index', [
            'pageTitle' => 'Businesses',
            'active' => 'businesses',
            'businesses' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'q' => $q,
            'status' => $status,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
        ]);
    }

    public function create(): void
    {
        $this->renderAdmin('admin.businesses.form', [
            'pageTitle' => 'Add Business',
            'active' => 'businesses',
            'business' => null,
            'plans' => $this->plans(),
            'mode' => 'create',
        ]);
        clear_old_input();
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $validator = (new Validator())
            ->required('name', 'Business name')
            ->required('category', 'Business category')
            ->max('tagline', 'Tagline', 255)
            ->required('owner_name', 'Owner name')
            ->required('owner_email', 'Owner email')
            ->email('owner_email', 'Owner email')
            ->required('owner_password', 'Owner password')
            ->email('email', 'Business email')
            ->in('status', 'Business status', ['pending', 'under_review', 'changes_requested', 'approved', 'active', 'inactive', 'suspended']);

        if (strlen((string) ($_POST['owner_password'] ?? '')) < 8) {
            $errors = $validator->errors();
            $errors[] = 'Owner password must be at least 8 characters.';
        } else {
            $errors = $validator->errors();
        }

        if (!empty($errors)) {
            $this->flashValidationErrors($errors);
            $this->redirect('/admin/businesses/create');
        }

        $pdo = Database::pdo();
        $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $emailCheck->execute([strtolower(trim((string) $_POST['owner_email']))]);
        if ($emailCheck->fetch()) {
            Flash::error('Owner email is already used by another user.');
            $_SESSION['_old'] = $_POST;
            $this->redirect('/admin/businesses/create');
        }

        $status = (string) ($_POST['status'] ?? 'pending');
        $slugSource = trim((string) ($_POST['slug'] ?? '')) !== '' ? (string) $_POST['slug'] : (string) $_POST['name'];
        $slug = SlugService::unique('businesses', $slugSource);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO businesses
                 (name, slug, category, tagline, description, phone, email, address, city, state, country, website, status, approved_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                trim((string) $_POST['name']),
                $slug,
                trim((string) $_POST['category']),
                trim((string) ($_POST['tagline'] ?? '')) ?: null,
                trim((string) ($_POST['description'] ?? '')) ?: null,
                trim((string) ($_POST['phone'] ?? '')) ?: null,
                strtolower(trim((string) ($_POST['email'] ?? ''))) ?: null,
                trim((string) ($_POST['address'] ?? '')) ?: null,
                trim((string) ($_POST['city'] ?? '')) ?: null,
                trim((string) ($_POST['state'] ?? '')) ?: null,
                trim((string) ($_POST['country'] ?? 'India')) ?: null,
                trim((string) ($_POST['website'] ?? '')) ?: null,
                $status,
                in_array($status, ['approved', 'active'], true) ? date('Y-m-d H:i:s') : null,
            ]);
            $businessId = (int) $pdo->lastInsertId();

            $settings = $pdo->prepare('INSERT INTO business_settings (business_id, theme_color, accent_color, portal_settings, created_at, updated_at) VALUES (?, "#2563eb", "#f97316", NULL, NOW(), NOW())');
            $settings->execute([$businessId]);

            $user = $pdo->prepare(
                'INSERT INTO users (business_id, name, email, phone, password_hash, role, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, "business_owner", "active", NOW(), NOW())'
            );
            $user->execute([
                $businessId,
                trim((string) $_POST['owner_name']),
                strtolower(trim((string) $_POST['owner_email'])),
                trim((string) ($_POST['owner_phone'] ?? '')) ?: null,
                password_hash((string) $_POST['owner_password'], PASSWORD_DEFAULT),
            ]);

            $planId = (int) ($_POST['plan_id'] ?? 0);
            if ($planId > 0 && !empty($_POST['starts_at']) && !empty($_POST['expires_at'])) {
                $plan = $this->plan($planId);
                $sub = $pdo->prepare(
                    'INSERT INTO business_subscriptions
                     (business_id, plan_id, status, starts_at, expires_at, grace_ends_at, renewal_status, auto_renew, price_at_signup, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, "manual", 0, ?, NOW(), NOW())'
                );
                $sub->execute([
                    $businessId,
                    $planId,
                    (string) ($_POST['subscription_status'] ?? 'active'),
                    $_POST['starts_at'],
                    $_POST['expires_at'],
                    ($_POST['grace_ends_at'] ?? '') ?: null,
                    $plan ? $plan['price'] : 0,
                ]);
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            app_log('Business creation failed', ['message' => $exception->getMessage()]);
            Flash::error('Could not create business. Please verify details and try again.');
            $_SESSION['_old'] = $_POST;
            $this->redirect('/admin/businesses/create');
        }

        NotificationService::create('super_admin', 'business_created', 'Business added', trim((string) $_POST['name']) . ' was created.', $businessId, null, 'business', $businessId);
        if (in_array($status, ['approved', 'active'], true)) {
            NotificationService::create('business', 'business_approved', 'Business approved', 'Your business portal is now approved.', $businessId, null, 'business', $businessId);
        }
        ActivityLogger::log('business_created', 'business', $businessId, $businessId, ['status' => $status]);
        Flash::success('Business created successfully.');
        $this->redirect('/admin/businesses/' . $businessId);
    }

    public function show(string $id): void
    {
        $business = $this->business((int) $id);
        $businessId = (int) $business['id'];
        $pdo = Database::pdo();

        $ownerStmt = $pdo->prepare('SELECT * FROM users WHERE business_id = ? AND role = "business_owner" ORDER BY id ASC LIMIT 1');
        $ownerStmt->execute([$businessId]);

        $paymentsStmt = $pdo->prepare(
            'SELECT p.*, sp.name AS plan_name
             FROM payments p
             LEFT JOIN subscription_plans sp ON sp.id = p.plan_id
             WHERE p.business_id = ?
             ORDER BY p.payment_date DESC, p.id DESC
             LIMIT 20'
        );
        $paymentsStmt->execute([$businessId]);

        $activityStmt = $pdo->prepare('SELECT * FROM activity_logs WHERE business_id = ? ORDER BY created_at DESC LIMIT 20');
        $activityStmt->execute([$businessId]);

        $enquiriesStmt = $pdo->prepare('SELECT id, name, phone, requested_item, status, created_at FROM enquiries WHERE business_id = ? ORDER BY created_at DESC LIMIT 8');
        $enquiriesStmt->execute([$businessId]);

        $ordersStmt = $pdo->prepare('SELECT id, order_number, customer_name, status, total_amount, created_at FROM orders WHERE business_id = ? ORDER BY created_at DESC LIMIT 8');
        $ordersStmt->execute([$businessId]);

        $listingsStmt = $pdo->prepare('SELECT id, title, type, status, price, created_at FROM listings WHERE business_id = ? AND status <> "archived" ORDER BY created_at DESC LIMIT 8');
        $listingsStmt->execute([$businessId]);

        $stats = [
            'listings' => $this->countRows('SELECT COUNT(*) FROM listings WHERE business_id = ? AND status <> "archived"', [$businessId]),
            'enquiries' => $this->countRows('SELECT COUNT(*) FROM enquiries WHERE business_id = ?', [$businessId]),
            'orders' => $this->countRows('SELECT COUNT(*) FROM orders WHERE business_id = ?', [$businessId]),
            'customers' => $this->countRows('SELECT COUNT(*) FROM customers WHERE business_id = ?', [$businessId]),
        ];

        $subscription = SubscriptionService::current($businessId);
        $planFeatures = $subscription ? FeatureService::featuresForPlan((int) $subscription['plan_id']) : [];
        $currentFeatureAccess = $subscription ? FeatureService::featuresForBusiness($businessId, $business, $subscription) : [];

        $settingsStmt = $pdo->prepare('SELECT * FROM business_settings WHERE business_id = ? LIMIT 1');
        $settingsStmt->execute([$businessId]);
        $websiteSettings = $settingsStmt->fetch(PDO::FETCH_ASSOC) ?: ['website_enabled' => 1, 'show_in_directory' => 1];

        $this->renderAdmin('admin.businesses.show', [
            'pageTitle' => $business['name'],
            'active' => 'businesses',
            'business' => $business,
            'owner' => $ownerStmt->fetch() ?: null,
            'payments' => $paymentsStmt->fetchAll(PDO::FETCH_ASSOC),
            'activityLogs' => $activityStmt->fetchAll(PDO::FETCH_ASSOC),
            'recentBusinessEnquiries' => $enquiriesStmt->fetchAll(PDO::FETCH_ASSOC),
            'recentBusinessOrders' => $ordersStmt->fetchAll(PDO::FETCH_ASSOC),
            'recentBusinessListings' => $listingsStmt->fetchAll(PDO::FETCH_ASSOC),
            'stats' => $stats,
            'plans' => $this->plans(true),
            'subscription' => $subscription,
            'planFeatures' => $planFeatures,
            'currentFeatureAccess' => $currentFeatureAccess,
            'websiteSettings' => $websiteSettings,
            'effectiveStatus' => SubscriptionService::effectiveStatus($business, $subscription),
        ]);
    }

    public function edit(string $id): void
    {
        $business = $this->business((int) $id);
        $this->renderAdmin('admin.businesses.form', [
            'pageTitle' => 'Edit ' . $business['name'],
            'active' => 'businesses',
            'business' => $business,
            'plans' => $this->plans(),
            'mode' => 'edit',
        ]);
        clear_old_input();
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $business = $this->business((int) $id);

        $validator = (new Validator())
            ->required('name', 'Business name')
            ->required('category', 'Business category')
            ->max('tagline', 'Tagline', 255)
            ->email('email', 'Business email')
            ->in('status', 'Business status', ['pending', 'approved', 'active', 'inactive', 'suspended', 'rejected', 'archived']);

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/admin/businesses/' . $business['id'] . '/edit');
        }

        $status = (string) ($_POST['status'] ?? $business['status']);
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $slug = SlugService::unique('businesses', $slugInput !== '' ? $slugInput : (string) $_POST['name'], null, (int) $business['id']);

        $stmt = Database::pdo()->prepare(
            'UPDATE businesses
             SET name = ?, slug = ?, category = ?, tagline = ?, description = ?, phone = ?, email = ?, address = ?, city = ?, state = ?, country = ?, website = ?, status = ?, approved_at = COALESCE(approved_at, ?), updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            trim((string) $_POST['name']),
            $slug,
            trim((string) $_POST['category']),
            trim((string) ($_POST['tagline'] ?? '')) ?: null,
            trim((string) ($_POST['description'] ?? '')) ?: null,
            trim((string) ($_POST['phone'] ?? '')) ?: null,
            strtolower(trim((string) ($_POST['email'] ?? ''))) ?: null,
            trim((string) ($_POST['address'] ?? '')) ?: null,
            trim((string) ($_POST['city'] ?? '')) ?: null,
            trim((string) ($_POST['state'] ?? '')) ?: null,
            trim((string) ($_POST['country'] ?? 'India')) ?: null,
            trim((string) ($_POST['website'] ?? '')) ?: null,
            $status,
            in_array($status, ['approved', 'active'], true) ? date('Y-m-d H:i:s') : null,
            (int) $business['id'],
        ]);

        ActivityLogger::log('business_updated', 'business', (int) $business['id'], (int) $business['id'], ['status' => $status]);
        Flash::success('Business updated successfully.');
        $this->redirect('/admin/businesses/' . $business['id']);
    }

    public function changeStatus(string $id): void
    {
        $this->verifyCsrf();
        $business = $this->business((int) $id);
        $status = (string) ($_POST['status'] ?? '');
        $allowed = ['pending', 'under_review', 'changes_requested', 'approved', 'active', 'inactive', 'suspended', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            throw new HttpException(422, 'Invalid status.');
        }

        $note = trim((string) ($_POST['note'] ?? ''));
        if ($note === '' && $status === 'changes_requested') {
            $note = 'Please complete your business profile and website content, then submit for review again.';
        }

        $reviewNote = in_array($status, ['changes_requested', 'rejected'], true) ? ($note ?: null) : null;
        if ($status === 'approved') {
            $reviewNote = $note ?: null; // approval message is informational
        }

        $stmt = Database::pdo()->prepare(
            'UPDATE businesses SET status = ?, review_note = ?, submitted_for_review_at = CASE WHEN ? = "approved" THEN COALESCE(submitted_for_review_at, NOW()) ELSE submitted_for_review_at END, approved_at = CASE WHEN ? IN ("approved", "active") THEN COALESCE(approved_at, NOW()) WHEN ? IN ("changes_requested", "rejected") THEN NULL ELSE approved_at END, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$status, $reviewNote, $status, $status, $status, (int) $business['id']]);

        $messages = [
            'approved' => 'Your business has been approved. Your public profile is now eligible; publish your website from Website Settings when ready.',
            'active' => 'Your business is active.',
            'inactive' => 'Your business has been marked inactive.',
            'suspended' => 'Your business has been suspended. Please contact platform support.',
            'rejected' => 'Your business application was rejected.' . ($note !== '' ? ' Platform note: ' . $note : ''),
            'changes_requested' => 'The platform asked for changes before approval.' . ($note !== '' ? ' Notes: ' . $note : ''),
            'under_review' => 'Your business is under review by the platform team.',
            'pending' => 'Your business was moved back to pending.',
        ];
        $typeMap = [
            'approved' => 'business_approved',
            'active' => 'business_activated',
            'inactive' => 'business_deactivated',
            'suspended' => 'business_suspended',
            'rejected' => 'business_rejected',
            'changes_requested' => 'business_changes_requested',
            'under_review' => 'business_under_review',
            'pending' => 'business_pending',
        ];
        if (isset($messages[$status])) {
            NotificationService::create('business', $typeMap[$status], 'Business status updated', $messages[$status], (int) $business['id'], null, 'business', (int) $business['id']);
        }

        ActivityLogger::log('business_status_changed', 'business', (int) $business['id'], (int) $business['id'], ['from' => $business['status'], 'to' => $status, 'note' => $note !== '' ? $note : null]);
        Flash::success('Business status changed.');
        $this->redirect('/admin/businesses/' . $business['id']);
    }

    public function saveSubscription(string $id): void
    {
        $this->verifyCsrf();
        $business = $this->business((int) $id);
        $planId = (int) ($_POST['plan_id'] ?? 0);
        $plan = $this->plan($planId);
        if (!$plan) {
            Flash::error('Choose a valid subscription plan.');
            $this->redirect('/admin/businesses/' . $business['id']);
        }

        $validator = (new Validator())
            ->required('starts_at', 'Start date')
            ->required('expires_at', 'Expiry date')
            ->in('subscription_status', 'Subscription status', ['pending', 'active', 'expired', 'suspended', 'cancelled']);

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/admin/businesses/' . $business['id']);
        }

        $current = SubscriptionService::current((int) $business['id']);
        if ($current) {
            $stmt = Database::pdo()->prepare(
                'UPDATE business_subscriptions
                 SET plan_id = ?, status = ?, starts_at = ?, expires_at = ?, grace_ends_at = ?, renewal_status = ?, price_at_signup = ?, updated_at = NOW()
                 WHERE id = ? AND business_id = ?'
            );
            $stmt->execute([
                $planId,
                (string) $_POST['subscription_status'],
                $_POST['starts_at'],
                $_POST['expires_at'],
                ($_POST['grace_ends_at'] ?? '') ?: null,
                (string) ($_POST['renewal_status'] ?? 'manual'),
                $plan['price'],
                (int) $current['id'],
                (int) $business['id'],
            ]);
            $subscriptionId = (int) $current['id'];
        } else {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO business_subscriptions
                 (business_id, plan_id, status, starts_at, expires_at, grace_ends_at, renewal_status, auto_renew, price_at_signup, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())'
            );
            $stmt->execute([
                (int) $business['id'],
                $planId,
                (string) $_POST['subscription_status'],
                $_POST['starts_at'],
                $_POST['expires_at'],
                ($_POST['grace_ends_at'] ?? '') ?: null,
                (string) ($_POST['renewal_status'] ?? 'manual'),
                $plan['price'],
            ]);
            $subscriptionId = (int) Database::pdo()->lastInsertId();
        }

        NotificationService::create('business', 'subscription_updated', 'Subscription updated', 'Your subscription details were updated by the platform admin.', (int) $business['id'], null, 'subscription', $subscriptionId);
        ActivityLogger::log('subscription_updated', 'subscription', $subscriptionId, (int) $business['id']);
        Flash::success('Subscription saved.');
        $this->redirect('/admin/businesses/' . $business['id']);
    }

    public function recordPayment(string $id): void
    {
        $this->verifyCsrf();
        $business = $this->business((int) $id);

        $validator = (new Validator())
            ->required('amount', 'Payment amount')
            ->numeric('amount', 'Payment amount')
            ->required('payment_date', 'Payment date')
            ->required('method', 'Payment method')
            ->in('payment_status', 'Payment status', ['pending', 'paid', 'failed', 'refunded', 'cancelled']);

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/admin/businesses/' . $business['id']);
        }

        $pdo = Database::pdo();
        $planId = (int) ($_POST['payment_plan_id'] ?? 0);
        $plan = $planId > 0 ? $this->plan($planId) : null;
        $paymentStatus = (string) ($_POST['payment_status'] ?? 'paid');
        $subscriptionId = null;

        try {
            $pdo->beginTransaction();

            if ($paymentStatus === 'paid' && $plan && !empty($_POST['period_start']) && !empty($_POST['period_end'])) {
                $current = SubscriptionService::current((int) $business['id']);
                if ($current) {
                    $subStmt = $pdo->prepare(
                        'UPDATE business_subscriptions
                         SET plan_id = ?, status = "active", starts_at = ?, expires_at = ?, grace_ends_at = ?, renewal_status = "renewed", price_at_signup = ?, updated_at = NOW()
                         WHERE id = ? AND business_id = ?'
                    );
                    $subStmt->execute([
                        $planId,
                        $_POST['period_start'],
                        $_POST['period_end'],
                        ($_POST['payment_grace_ends_at'] ?? '') ?: null,
                        $plan['price'],
                        (int) $current['id'],
                        (int) $business['id'],
                    ]);
                    $subscriptionId = (int) $current['id'];
                } else {
                    $subStmt = $pdo->prepare(
                        'INSERT INTO business_subscriptions
                         (business_id, plan_id, status, starts_at, expires_at, grace_ends_at, renewal_status, auto_renew, price_at_signup, created_at, updated_at)
                         VALUES (?, ?, "active", ?, ?, ?, "manual", 0, ?, NOW(), NOW())'
                    );
                    $subStmt->execute([
                        (int) $business['id'],
                        $planId,
                        $_POST['period_start'],
                        $_POST['period_end'],
                        ($_POST['payment_grace_ends_at'] ?? '') ?: null,
                        $plan['price'],
                    ]);
                    $subscriptionId = (int) $pdo->lastInsertId();
                }
            } else {
                $current = SubscriptionService::current((int) $business['id']);
                $subscriptionId = $current ? (int) $current['id'] : null;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO payments
                 (business_id, subscription_id, plan_id, amount, currency, payment_date, method, reference, gateway_provider, gateway_payment_id, period_start, period_end, status, notes, recorded_by, metadata, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?, NULL, NOW(), NOW())'
            );
            $stmt->execute([
                (int) $business['id'],
                $subscriptionId,
                $planId > 0 ? $planId : null,
                (float) $_POST['amount'],
                (string) ($_POST['currency'] ?? 'INR'),
                $_POST['payment_date'],
                trim((string) $_POST['method']),
                trim((string) ($_POST['reference'] ?? '')) ?: null,
                ($_POST['period_start'] ?? '') ?: null,
                ($_POST['period_end'] ?? '') ?: null,
                $paymentStatus,
                trim((string) ($_POST['notes'] ?? '')) ?: null,
                (int) $this->adminUser['id'],
            ]);
            $paymentId = (int) $pdo->lastInsertId();

            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            app_log('Payment recording failed', ['message' => $exception->getMessage()]);
            Flash::error('Could not record payment. Please try again.');
            $this->redirect('/admin/businesses/' . $business['id']);
        }

        NotificationService::create('business', 'payment_recorded', 'Payment recorded', 'A payment was recorded on your subscription account.', (int) $business['id'], null, 'payment', $paymentId);
        ActivityLogger::log('payment_recorded', 'payment', $paymentId, (int) $business['id'], ['status' => $paymentStatus]);
        Flash::success('Payment recorded successfully.');
        $this->redirect('/admin/businesses/' . $business['id']);
    }

    public function toggleWebsite(string $id): void
    {
        $this->verifyCsrf();
        $business = $this->business((int) $id);
        $enabled = (int) (($_POST['website_enabled'] ?? '0') === '1');

        $stmt = Database::pdo()->prepare(
            'INSERT INTO business_settings (business_id, website_enabled, theme_color, accent_color, created_at, updated_at)
             VALUES (?, ?, "#2563eb", "#f97316", NOW(), NOW())
             ON DUPLICATE KEY UPDATE website_enabled = VALUES(website_enabled), updated_at = NOW()'
        );
        $stmt->execute([(int) $business['id'], $enabled]);

        NotificationService::create(
            'business',
            $enabled ? 'website_enabled' : 'website_disabled',
            $enabled ? 'Public website enabled' : 'Public website disabled',
            $enabled ? 'Your public business website is enabled.' : 'Your public business website was disabled by the platform admin.',
            (int) $business['id'],
            null,
            'business',
            (int) $business['id']
        );
        ActivityLogger::log('business_website_' . ($enabled ? 'enabled' : 'disabled'), 'business', (int) $business['id'], (int) $business['id']);
        Flash::success('Website access updated.');
        $this->redirect('/admin/businesses/' . $business['id']);
    }

    public function previewWebsite(string $id): void
    {
        $business = $this->business((int) $id);
        (new \App\Controllers\PublicPortalController())->renderSite((string) $business['slug'], true);
    }

    public function archive(string $id): void
    {
        $this->verifyCsrf();
        $business = $this->business((int) $id);
        $stmt = Database::pdo()->prepare('UPDATE businesses SET status = "archived", updated_at = NOW() WHERE id = ?');
        $stmt->execute([(int) $business['id']]);
        ActivityLogger::log('business_archived', 'business', (int) $business['id'], (int) $business['id']);
        Flash::success('Business archived.');
        $this->redirect('/admin/businesses');
    }

    private function business(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM businesses WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business) {
            throw new HttpException(404, 'Business not found.');
        }
        return $business;
    }

    private function plans(bool $includeInactive = false): array
    {
        $sql = 'SELECT * FROM subscription_plans';
        if (!$includeInactive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, COALESCE(monthly_price, price) ASC';
        return Database::pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function plan(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM subscription_plans WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
