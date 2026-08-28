<?php

declare(strict_types=1);

namespace App\Controllers\Business;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\HttpException;
use App\Services\FeatureService;
use App\Services\NotificationService;
use App\Services\SubscriptionService;

abstract class BaseBusinessController extends Controller
{
    protected array $businessUser;
    protected array $business;
    protected ?array $subscription;
    protected string $effectiveSubscriptionStatus;
    protected bool $portalAllowed;
    protected array $featureAccess = [];

    public function __construct()
    {
        Auth::requireBusinessUser();
        $this->businessUser = Auth::user() ?? [];
        $businessId = Auth::businessId();
        if (!$businessId) {
            throw new HttpException(403);
        }

        $stmt = Database::pdo()->prepare('SELECT * FROM businesses WHERE id = ? LIMIT 1');
        $stmt->execute([$businessId]);
        $business = $stmt->fetch();
        if (!$business || in_array($business['status'], ['rejected', 'archived'], true)) {
            throw new HttpException(403);
        }

        $this->business = $business;
        $this->subscription = SubscriptionService::current($businessId);
        $this->effectiveSubscriptionStatus = SubscriptionService::effectiveStatus($this->business, $this->subscription);
        // Content management (not public publishing) — pending businesses may prepare content.
        $this->portalAllowed = SubscriptionService::canManageContent($this->business, $this->subscription);
        $this->featureAccess = FeatureService::featuresForBusiness($businessId, $this->business, $this->subscription);
    }

    protected function renderBusiness(string $view, array $params = []): void
    {
        $params['businessUser'] = $this->businessUser;
        $params['business'] = $this->business;
        $params['subscription'] = $this->subscription;
        $params['effectiveSubscriptionStatus'] = $this->effectiveSubscriptionStatus;
        $params['portalAllowed'] = $this->portalAllowed;
        $params['featureAccess'] = $this->featureAccess;
        $params['businessUnreadNotifications'] = $this->hasFeature('notifications') ? NotificationService::unreadCount('business', (int) $this->business['id']) : 0;
        $params['websiteAccess'] = \App\Services\WebsiteAccessService::evaluate($this->business, $this->subscription, null, $this->featureAccess);
        $this->render($view, $params, 'layouts/business');
    }

    protected function guardWriteAccess(): void
    {
        if (!$this->portalAllowed) {
            Flash::warning('Your subscription or business status currently restricts this action. Please renew or contact the platform admin.');
            $this->redirect('/business/subscription');
        }
    }

    protected function hasFeature(string $identifier): bool
    {
        return isset($this->featureAccess[$identifier]);
    }

    protected function hasAnyFeature(array $identifiers): bool
    {
        foreach ($identifiers as $identifier) {
            if ($this->hasFeature($identifier)) {
                return true;
            }
        }
        return false;
    }

    protected function requireFeature(string $identifier, ?string $featureName = null): void
    {
        if (!$this->portalAllowed) {
            Flash::warning('Your subscription or business status currently restricts this action. Please renew or contact the platform admin.');
            $this->redirect('/business/subscription');
        }

        if (!$this->hasFeature($identifier)) {
            $this->renderBusiness('business.feature_unavailable', [
                'pageTitle' => 'Feature Not Included',
                'active' => '',
                'featureName' => $featureName ?: FeatureService::featureLabel($identifier),
                'reason' => 'not_included',
                'plans' => $this->availablePlans(),
            ]);
            exit;
        }
    }

    protected function requireAnyFeature(array $identifiers, string $featureName): void
    {
        if (!$this->portalAllowed) {
            Flash::warning('Your subscription or business status currently restricts this action. Please renew or contact the platform admin.');
            $this->redirect('/business/subscription');
        }

        if (!$this->hasAnyFeature($identifiers)) {
            $this->renderBusiness('business.feature_unavailable', [
                'pageTitle' => 'Feature Not Included',
                'active' => '',
                'featureName' => $featureName,
                'reason' => 'not_included',
                'plans' => $this->availablePlans(),
            ]);
            exit;
        }
    }

    protected function featureLimit(string $identifier, string $key, $default = null)
    {
        return $this->featureAccess[$identifier]['limits'][$key] ?? $default;
    }

    protected function showLimitReached(string $featureName, string $message): void
    {
        $this->renderBusiness('business.feature_unavailable', [
            'pageTitle' => 'Plan Limit Reached',
            'active' => '',
            'featureName' => $featureName,
            'reason' => 'limit_reached',
            'limitMessage' => $message,
            'plans' => $this->availablePlans(),
        ]);
        exit;
    }

    protected function tenantId(): int
    {
        return (int) $this->business['id'];
    }

    protected function availablePlans(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC, COALESCE(monthly_price, price) ASC');
        return $stmt->fetchAll() ?: [];
    }
}
