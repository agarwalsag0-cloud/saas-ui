<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Services\ActivityLogger;
use App\Services\FeatureService;
use PDO;
use Throwable;

class PlanController extends BaseAdminController
{
    public function index(): void
    {
        $stmt = Database::pdo()->query(
            'SELECT sp.*,
                    (SELECT COUNT(*) FROM business_subscriptions bs WHERE bs.plan_id = sp.id) AS subscription_count,
                    (SELECT COUNT(*) FROM subscription_plan_features spf WHERE spf.plan_id = sp.id AND spf.enabled = 1) AS feature_count
             FROM subscription_plans sp
             ORDER BY sp.sort_order ASC, COALESCE(sp.monthly_price, sp.price) ASC'
        );

        $this->renderAdmin('admin.plans.index', [
            'pageTitle' => 'Subscription Plans',
            'active' => 'plans',
            'plans' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'featureGroups' => FeatureService::groupedRegistry(true, true),
            'selectedFeatures' => [],
        ]);
        clear_old_input();
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $validator = (new Validator())
            ->required('name', 'Plan name')
            ->numeric('monthly_price', 'Monthly price')
            ->numeric('yearly_price', 'Yearly price')
            ->in('billing_cycle', 'Default billing cycle', ['monthly', 'yearly', 'custom']);

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/admin/plans');
        }

        $selectedFeatureIds = array_map('intval', $_POST['feature_ids'] ?? []);
        $limits = $_POST['feature_limits'] ?? [];
        $monthly = ($_POST['monthly_price'] ?? '') !== '' ? (float) $_POST['monthly_price'] : 0.0;
        $yearly = ($_POST['yearly_price'] ?? '') !== '' ? (float) $_POST['yearly_price'] : null;
        $billingCycle = (string) ($_POST['billing_cycle'] ?? 'monthly');
        $price = $billingCycle === 'yearly' ? ($yearly ?? $monthly) : $monthly;

        $pdo = Database::pdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO subscription_plans (name, description, billing_cycle, price, monthly_price, yearly_price, currency, features, is_active, sort_order, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                trim((string) $_POST['name']),
                trim((string) ($_POST['description'] ?? '')) ?: null,
                $billingCycle,
                $price,
                $monthly,
                $yearly,
                strtoupper(trim((string) ($_POST['currency'] ?? 'INR'))),
                json_encode([], JSON_UNESCAPED_SLASHES),
                isset($_POST['is_active']) ? 1 : 0,
                (int) ($_POST['sort_order'] ?? 0),
            ]);
            $planId = (int) $pdo->lastInsertId();
            FeatureService::syncPlanFeatures($planId, $selectedFeatureIds, $limits);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            app_log('Plan creation failed', ['message' => $exception->getMessage()]);
            Flash::error('Could not create plan. Please verify the details and feature limits.');
            $_SESSION['_old'] = $_POST;
            $this->redirect('/admin/plans');
        }

        ActivityLogger::log('subscription_plan_created', 'subscription_plan', $planId, null, ['features' => $selectedFeatureIds]);
        Flash::success('Plan created with selected features.');
        $this->redirect('/admin/plans');
    }

    public function edit(string $id): void
    {
        $plan = $this->plan((int) $id);
        $this->renderAdmin('admin.plans.edit', [
            'pageTitle' => 'Edit Plan',
            'active' => 'plans',
            'plan' => $plan,
            'featureGroups' => FeatureService::groupedRegistry(true, true),
            'selectedFeatures' => FeatureService::featuresForPlan((int) $plan['id']),
        ]);
        clear_old_input();
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $plan = $this->plan((int) $id);
        $validator = (new Validator())
            ->required('name', 'Plan name')
            ->numeric('monthly_price', 'Monthly price')
            ->numeric('yearly_price', 'Yearly price')
            ->in('billing_cycle', 'Default billing cycle', ['monthly', 'yearly', 'custom']);

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/admin/plans/' . $plan['id'] . '/edit');
        }

        $selectedFeatureIds = array_map('intval', $_POST['feature_ids'] ?? []);
        $limits = $_POST['feature_limits'] ?? [];
        $monthly = ($_POST['monthly_price'] ?? '') !== '' ? (float) $_POST['monthly_price'] : 0.0;
        $yearly = ($_POST['yearly_price'] ?? '') !== '' ? (float) $_POST['yearly_price'] : null;
        $billingCycle = (string) ($_POST['billing_cycle'] ?? 'monthly');
        $price = $billingCycle === 'yearly' ? ($yearly ?? $monthly) : $monthly;

        $pdo = Database::pdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'UPDATE subscription_plans
                 SET name = ?, description = ?, billing_cycle = ?, price = ?, monthly_price = ?, yearly_price = ?, currency = ?, features = ?, is_active = ?, sort_order = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([
                trim((string) $_POST['name']),
                trim((string) ($_POST['description'] ?? '')) ?: null,
                $billingCycle,
                $price,
                $monthly,
                $yearly,
                strtoupper(trim((string) ($_POST['currency'] ?? 'INR'))),
                json_encode([], JSON_UNESCAPED_SLASHES),
                isset($_POST['is_active']) ? 1 : 0,
                (int) ($_POST['sort_order'] ?? 0),
                (int) $plan['id'],
            ]);
            FeatureService::syncPlanFeatures((int) $plan['id'], $selectedFeatureIds, $limits);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            app_log('Plan update failed', ['message' => $exception->getMessage()]);
            Flash::error('Could not update plan. Please verify the details and feature limits.');
            $_SESSION['_old'] = $_POST;
            $this->redirect('/admin/plans/' . $plan['id'] . '/edit');
        }

        ActivityLogger::log('subscription_plan_updated', 'subscription_plan', (int) $plan['id'], null, ['features' => $selectedFeatureIds]);
        Flash::success('Plan updated with selected features.');
        $this->redirect('/admin/plans');
    }

    public function toggle(string $id): void
    {
        $this->verifyCsrf();
        $plan = $this->plan((int) $id);
        $newStatus = (int) !$plan['is_active'];
        $stmt = Database::pdo()->prepare('UPDATE subscription_plans SET is_active = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$newStatus, (int) $plan['id']]);
        ActivityLogger::log('subscription_plan_toggled', 'subscription_plan', (int) $plan['id'], null, ['is_active' => $newStatus]);
        Flash::success('Plan status updated.');
        $this->redirect('/admin/plans');
    }

    private function plan(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM subscription_plans WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$plan) {
            throw new HttpException(404, 'Plan not found.');
        }
        return $plan;
    }
}
