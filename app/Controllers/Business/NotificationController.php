<?php

declare(strict_types=1);

namespace App\Controllers\Business;

use App\Core\Database;
use App\Core\Flash;
use App\Services\NotificationService;
use PDO;

class NotificationController extends BaseBusinessController
{
    public function index(): void
    {
        $this->requireFeature('notifications', 'Notifications');
        $stmt = Database::pdo()->prepare('SELECT * FROM notifications WHERE audience = "business" AND business_id = ? ORDER BY created_at DESC LIMIT 100');
        $stmt->execute([$this->tenantId()]);
        $this->renderBusiness('business.notifications', [
            'pageTitle' => 'Notifications',
            'active' => 'notifications',
            'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }

    public function markRead(): void
    {
        $this->requireFeature('notifications', 'Notifications');
        $this->verifyCsrf();
        NotificationService::markAllRead('business', $this->tenantId());
        Flash::success('Notifications marked as read.');
        $this->redirect('/business/notifications');
    }
}
