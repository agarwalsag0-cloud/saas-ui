<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Flash;
use App\Services\NotificationService;
use PDO;

class NotificationController extends BaseAdminController
{
    public function index(): void
    {
        $stmt = Database::pdo()->query('SELECT n.*, b.name AS business_name FROM notifications n LEFT JOIN businesses b ON b.id = n.business_id WHERE n.audience = "super_admin" ORDER BY n.created_at DESC LIMIT 100');
        $this->renderAdmin('admin.notifications.index', [
            'pageTitle' => 'Notifications',
            'active' => 'notifications',
            'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }

    public function markRead(): void
    {
        $this->verifyCsrf();
        NotificationService::markAllRead('super_admin');
        Flash::success('Notifications marked as read.');
        $this->redirect('/admin/notifications');
    }
}
