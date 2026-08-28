<?php

declare(strict_types=1);

namespace App\Controllers\Business;

use App\Core\Database;
use App\Services\FeatureService;
use PDO;

class SubscriptionController extends BaseBusinessController
{
    public function show(): void
    {
        $payments = Database::pdo()->prepare(
            'SELECT p.*, sp.name AS plan_name
             FROM payments p
             LEFT JOIN subscription_plans sp ON sp.id = p.plan_id
             WHERE p.business_id = ?
             ORDER BY p.payment_date DESC, p.id DESC
             LIMIT 20'
        );
        $payments->execute([$this->tenantId()]);

        $plans = Database::pdo()->query('SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC, COALESCE(monthly_price, price) ASC')->fetchAll(PDO::FETCH_ASSOC);
        $featuresByPlan = [];
        foreach ($plans as $plan) {
            $featuresByPlan[(int) $plan['id']] = FeatureService::featuresForPlan((int) $plan['id']);
        }

        $this->renderBusiness('business.subscription', [
            'pageTitle' => 'Subscription',
            'active' => 'subscription',
            'payments' => $payments->fetchAll(PDO::FETCH_ASSOC),
            'plans' => $plans,
            'featuresByPlan' => $featuresByPlan,
            'currentFeatures' => $this->subscription ? FeatureService::featuresForPlan((int) $this->subscription['plan_id']) : [],
        ]);
    }
}
