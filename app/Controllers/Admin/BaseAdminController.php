<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\NotificationService;

abstract class BaseAdminController extends Controller
{
    protected array $adminUser;

    public function __construct()
    {
        Auth::requireSuperAdmin();
        $this->adminUser = Auth::user() ?? [];
    }

    protected function renderAdmin(string $view, array $params = []): void
    {
        $params['adminUser'] = $this->adminUser;
        $params['adminUnreadNotifications'] = NotificationService::unreadCount('super_admin');
        $params['adminReviewQueueCount'] = $this->countRows('SELECT COUNT(*) FROM businesses WHERE status IN ("pending", "under_review", "changes_requested")');
        $this->render($view, $params, 'layouts/admin');
    }

    protected function countRows(string $sql, array $params = []): int
    {
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
