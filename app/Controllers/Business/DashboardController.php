<?php

declare(strict_types=1);

namespace App\Controllers\Business;

use App\Core\Database;
use App\Services\NotificationService;
use PDO;

class DashboardController extends BaseBusinessController
{
    public function index(): void
    {
        $businessId = $this->tenantId();
        $pdo = Database::pdo();

        $count = function (string $sql, array $params = []) use ($pdo): int {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        };

        $metrics = [
            'categories' => $count('SELECT COUNT(*) FROM categories WHERE business_id = ? AND is_active = 1', [$businessId]),
            'listings' => $count('SELECT COUNT(*) FROM listings WHERE business_id = ? AND status <> "archived"', [$businessId]),
            'active_listings' => $count('SELECT COUNT(*) FROM listings WHERE business_id = ? AND status = "active"', [$businessId]),
            'enquiries' => $count('SELECT COUNT(*) FROM enquiries WHERE business_id = ?', [$businessId]),
            'new_enquiries' => $count('SELECT COUNT(*) FROM enquiries WHERE business_id = ? AND status = "new"', [$businessId]),
            'orders' => $count('SELECT COUNT(*) FROM orders WHERE business_id = ?', [$businessId]),
            'open_orders' => $count('SELECT COUNT(*) FROM orders WHERE business_id = ? AND status IN ("new", "confirmed", "in_progress")', [$businessId]),
            'offers' => $count('SELECT COUNT(*) FROM offers WHERE business_id = ? AND is_active = 1', [$businessId]),
            'revenue' => 0,
        ];

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE business_id = ? AND status IN ("confirmed", "completed")');
        $stmt->execute([$businessId]);
        $metrics['revenue'] = (float) $stmt->fetchColumn();

        $recentEnquiries = $pdo->prepare('SELECT * FROM enquiries WHERE business_id = ? ORDER BY created_at DESC LIMIT 8');
        $recentEnquiries->execute([$businessId]);

        $recentOrders = $pdo->prepare('SELECT * FROM orders WHERE business_id = ? ORDER BY created_at DESC LIMIT 8');
        $recentOrders->execute([$businessId]);

        $activity = $pdo->prepare('SELECT * FROM activity_logs WHERE business_id = ? ORDER BY created_at DESC LIMIT 8');
        $activity->execute([$businessId]);

        $notifications = NotificationService::latest('business', $businessId, 6);

        $settingsStmt = $pdo->prepare('SELECT website_enabled, show_in_directory FROM business_settings WHERE business_id = ? LIMIT 1');
        $settingsStmt->execute([$businessId]);
        $websiteSettings = $settingsStmt->fetch(PDO::FETCH_ASSOC) ?: ['website_enabled' => 1, 'show_in_directory' => 1];

        $this->renderBusiness('business.dashboard', [
            'pageTitle' => 'Business Dashboard',
            'active' => 'dashboard',
            'metrics' => $metrics,
            'recentEnquiries' => $recentEnquiries->fetchAll(PDO::FETCH_ASSOC),
            'recentOrders' => $recentOrders->fetchAll(PDO::FETCH_ASSOC),
            'activityLogs' => $activity->fetchAll(PDO::FETCH_ASSOC),
            'notifications' => $notifications,
            'websiteSettings' => $websiteSettings,
            'websitePath' => url('/p/' . $this->business['slug']),
        ]);
    }
}
