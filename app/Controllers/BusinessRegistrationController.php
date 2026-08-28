<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use App\Services\SlugService;
use DateInterval;
use DateTimeImmutable;
use PDO;
use Throwable;

class BusinessRegistrationController extends Controller
{
    public function show(): void
    {
        $this->render('public.register_business', [
            'pageTitle' => 'Register Business',
            'plans' => $this->publicPlans(),
        ], 'layouts/public');
        clear_old_input();
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $validator = (new Validator())
            ->required('business_name', 'Business name')
            ->required('category', 'Business category')
            ->required('owner_name', 'Owner name')
            ->required('owner_email', 'Owner email')
            ->email('owner_email', 'Owner email')
            ->required('owner_password', 'Password')
            ->max('tagline', 'Tagline', 255)
            ->email('business_email', 'Business email')
            ->max('description', 'Business description', 2000);

        $errors = $validator->errors();
        $password = (string) ($_POST['owner_password'] ?? '');
        $confirm = (string) ($_POST['owner_password_confirmation'] ?? '');
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Password confirmation does not match.';
        }

        $selectedPlanId = (int) ($_POST['plan_id'] ?? 0);
        $selectedPlan = null;
        if ($selectedPlanId > 0) {
            $selectedPlan = $this->publicPlan($selectedPlanId);
            if (!$selectedPlan) {
                $errors[] = 'Selected subscription plan is not available. Please choose another plan or continue without selecting one.';
            }
        }

        if ($errors) {
            $this->flashValidationErrors($errors);
            $this->redirect('/register-business');
        }

        $pdo = Database::pdo();
        $ownerEmail = strtolower(trim((string) $_POST['owner_email']));
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$ownerEmail]);
        if ($check->fetch(PDO::FETCH_ASSOC)) {
            Flash::error('Owner email is already registered. Please log in or use another email.');
            $_SESSION['_old'] = $_POST;
            $this->redirect('/register-business');
        }

        $slug = SlugService::unique('businesses', (string) $_POST['business_name']);

        try {
            $pdo->beginTransaction();

            $businessStmt = $pdo->prepare(
                'INSERT INTO businesses
                 (name, slug, category, tagline, description, phone, email, address, city, state, country, website, status, approved_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", NULL, NOW(), NOW())'
            );
            $businessStmt->execute([
                trim((string) $_POST['business_name']),
                $slug,
                trim((string) $_POST['category']),
                trim((string) ($_POST['tagline'] ?? '')) ?: null,
                trim((string) ($_POST['description'] ?? '')) ?: null,
                trim((string) ($_POST['phone'] ?? '')) ?: null,
                strtolower(trim((string) ($_POST['business_email'] ?? ''))) ?: null,
                trim((string) ($_POST['address'] ?? '')) ?: null,
                trim((string) ($_POST['city'] ?? '')) ?: null,
                trim((string) ($_POST['state'] ?? '')) ?: null,
                trim((string) ($_POST['country'] ?? 'India')) ?: null,
                trim((string) ($_POST['website'] ?? '')) ?: null,
            ]);
            $businessId = (int) $pdo->lastInsertId();

            $settingsStmt = $pdo->prepare('INSERT INTO business_settings (business_id, theme_color, accent_color, portal_settings, created_at, updated_at) VALUES (?, "#2563eb", "#f97316", NULL, NOW(), NOW())');
            $settingsStmt->execute([$businessId]);

            $ownerStmt = $pdo->prepare(
                'INSERT INTO users (business_id, name, email, phone, password_hash, role, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, "business_owner", "active", NOW(), NOW())'
            );
            $ownerStmt->execute([
                $businessId,
                trim((string) $_POST['owner_name']),
                $ownerEmail,
                trim((string) ($_POST['owner_phone'] ?? '')) ?: null,
                password_hash($password, PASSWORD_DEFAULT),
            ]);
            $ownerId = (int) $pdo->lastInsertId();

            if ($selectedPlan) {
                $startsAt = new DateTimeImmutable('today');
                $duration = ($selectedPlan['billing_cycle'] ?? 'monthly') === 'yearly' ? new DateInterval('P1Y') : new DateInterval('P1M');
                $expiresAt = $startsAt->add($duration);
                $priceAtSignup = ($selectedPlan['billing_cycle'] ?? 'monthly') === 'yearly'
                    ? (float) ($selectedPlan['yearly_price'] ?? $selectedPlan['price'])
                    : (float) ($selectedPlan['monthly_price'] ?? $selectedPlan['price']);

                $subscriptionStmt = $pdo->prepare(
                    'INSERT INTO business_subscriptions (business_id, plan_id, status, starts_at, expires_at, grace_ends_at, renewal_status, auto_renew, price_at_signup, created_at, updated_at)
                     VALUES (?, ?, "pending", ?, ?, NULL, "pending", 0, ?, NOW(), NOW())'
                );
                $subscriptionStmt->execute([
                    $businessId,
                    (int) $selectedPlan['id'],
                    $startsAt->format('Y-m-d'),
                    $expiresAt->format('Y-m-d'),
                    $priceAtSignup,
                ]);
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            app_log('Public business registration failed', ['message' => $exception->getMessage()]);
            Flash::error('Could not submit registration. Please try again.');
            $_SESSION['_old'] = $_POST;
            $this->redirect('/register-business');
        }

        NotificationService::create('super_admin', 'business_registration', 'New business registration', trim((string) $_POST['business_name']) . ' registered and is waiting for approval.', $businessId, null, 'business', $businessId);
        ActivityLogger::log('business_registered_publicly', 'business', $businessId, $businessId, ['owner_user_id' => $ownerId, 'selected_plan_id' => $selectedPlan['id'] ?? null]);

        Flash::success('Business registered successfully. Your login is created, but portal and website access remain locked until Super Admin approval and an active subscription with included features.');
        $this->redirect('/business/login');
    }

    private function publicPlans(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT sp.*,
                    (SELECT COUNT(*) FROM subscription_plan_features spf WHERE spf.plan_id = sp.id AND spf.enabled = 1) AS feature_count,
                    EXISTS (
                        SELECT 1
                        FROM subscription_plan_features spf
                        INNER JOIN platform_features pf ON pf.id = spf.feature_id
                        WHERE spf.plan_id = sp.id
                          AND spf.enabled = 1
                          AND pf.identifier = "public_website"
                          AND pf.is_active = 1
                          AND pf.available_for_plans = 1
                    ) AS includes_public_website
             FROM subscription_plans sp
             WHERE sp.is_active = 1
             ORDER BY sp.sort_order ASC, COALESCE(sp.monthly_price, sp.price) ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function publicPlan(int $planId): ?array
    {
        if ($planId <= 0) {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$planId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
