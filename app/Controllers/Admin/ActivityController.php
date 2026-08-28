<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use PDO;

/**
 * Platform-wide activity/audit stream for the Super Admin portal.
 */
class ActivityController extends BaseAdminController
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $where = ['1 = 1'];
        $params = [];
        $businessId = (int) ($_GET['business_id'] ?? 0);
        $action = trim((string) ($_GET['action'] ?? ''));

        if ($businessId > 0) {
            $where[] = 'a.business_id = ?';
            $params[] = $businessId;
        }
        if ($action !== '') {
            $where[] = 'a.action LIKE ?';
            $params[] = '%' . $action . '%';
        }
        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM activity_logs a WHERE ' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT a.*, b.name AS business_name, u.name AS actor_name
             FROM activity_logs a
             LEFT JOIN businesses b ON b.id = a.business_id
             LEFT JOIN users u ON u.id = a.actor_user_id
             WHERE ' . $whereSql . '
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $stmt->execute($params);

        $this->renderAdmin('admin.activity.index', [
            'pageTitle' => 'Activity & Audit',
            'active' => 'activity',
            'logs' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'filters' => ['business_id' => $businessId ?: '', 'action' => $action],
        ]);
    }
}
