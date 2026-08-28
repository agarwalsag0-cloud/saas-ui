<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use PDO;

class DashboardController extends BaseAdminController
{
    public function index(): void
    {
        $pdo = Database::pdo();

        $metrics = [
            'total_businesses' => $this->countRows('SELECT COUNT(*) FROM businesses WHERE status <> "archived"'),
            'active_businesses' => $this->countRows('SELECT COUNT(*) FROM businesses WHERE status IN ("active", "approved")'),
            'pending_businesses' => $this->countRows('SELECT COUNT(*) FROM businesses WHERE status IN ("pending", "under_review", "changes_requested")'),
            'suspended_businesses' => $this->countRows('SELECT COUNT(*) FROM businesses WHERE status = "suspended"'),
            'expired_subscriptions' => $this->countRows('SELECT COUNT(DISTINCT business_id) FROM business_subscriptions WHERE expires_at < NOW() AND (grace_ends_at IS NULL OR grace_ends_at < NOW()) AND status NOT IN ("cancelled", "suspended")'),
            'monthly_recurring_revenue' => 0,
            'yearly_revenue' => 0,
            'pending_payments' => 0,
            'total_enquiries' => $this->countRows('SELECT COUNT(*) FROM enquiries'),
            'total_orders' => $this->countRows('SELECT COUNT(*) FROM orders'),
        ];

        $stmt = $pdo->query(
            'SELECT COALESCE(SUM(sp.price), 0)
             FROM business_subscriptions bs
             INNER JOIN subscription_plans sp ON sp.id = bs.plan_id
             WHERE bs.status = "active" AND bs.expires_at >= NOW() AND sp.billing_cycle = "monthly"'
        );
        $metrics['monthly_recurring_revenue'] = (float) $stmt->fetchColumn();

        $stmt = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = "paid" AND YEAR(payment_date) = YEAR(CURDATE())');
        $metrics['yearly_revenue'] = (float) $stmt->fetchColumn();

        $stmt = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = "pending"');
        $metrics['pending_payments'] = (float) $stmt->fetchColumn();

        $recentBusinesses = $pdo->query(
            'SELECT id, name, slug, category, status, created_at
             FROM businesses
             WHERE status <> "archived"
             ORDER BY created_at DESC
             LIMIT 8'
        )->fetchAll(PDO::FETCH_ASSOC);

        $recentEnquiries = $pdo->query(
            'SELECT e.id, e.name, e.status, e.created_at, b.name AS business_name
             FROM enquiries e
             INNER JOIN businesses b ON b.id = e.business_id
             ORDER BY e.created_at DESC
             LIMIT 8'
        )->fetchAll(PDO::FETCH_ASSOC);

        $recentOrders = $pdo->query(
            'SELECT o.id, o.order_number, o.customer_name, o.status, o.created_at, b.name AS business_name
             FROM orders o
             INNER JOIN businesses b ON b.id = o.business_id
             ORDER BY o.created_at DESC
             LIMIT 8'
        )->fetchAll(PDO::FETCH_ASSOC);

        $monthlyRevenue = $pdo->query(
            'SELECT DATE_FORMAT(payment_date, "%b %Y") AS label, COALESCE(SUM(amount), 0) AS total
             FROM payments
             WHERE status = "paid" AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY YEAR(payment_date), MONTH(payment_date), label
             ORDER BY YEAR(payment_date), MONTH(payment_date)'
        )->fetchAll(PDO::FETCH_ASSOC);

        $subscriptionStats = $pdo->query(
            'SELECT status, COUNT(*) AS total
             FROM business_subscriptions
             GROUP BY status
             ORDER BY total DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $growth = $pdo->query(
            'SELECT DATE_FORMAT(created_at, "%b %Y") AS label, COUNT(*) AS total
             FROM businesses
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY YEAR(created_at), MONTH(created_at), label
             ORDER BY YEAR(created_at), MONTH(created_at)'
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin.dashboard', [
            'pageTitle' => 'Super Admin Dashboard',
            'active' => 'dashboard',
            'metrics' => $metrics,
            'recentBusinesses' => $recentBusinesses,
            'recentEnquiries' => $recentEnquiries,
            'recentOrders' => $recentOrders,
            'monthlyRevenue' => $monthlyRevenue,
            'subscriptionStats' => $subscriptionStats,
            'growth' => $growth,
        ]);
    }
}
