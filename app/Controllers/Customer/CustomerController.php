<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\CustomerAuth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Services\GoogleOAuthService;
use App\Services\NotificationService;
use App\Services\UploadService;
use PDO;
use Throwable;

/**
 * Customer portal. Every query is scoped to the authenticated
 * customer_accounts.id — customers can never read another customer's data.
 */
class CustomerController extends Controller
{
    public function dashboard(): void
    {
        CustomerAuth::requireLogin();
        $accountId = (int) CustomerAuth::id();
        $customer = CustomerAuth::customer() ?? [];

        $pdo = Database::pdo();

        $enquiryCount = $this->count('SELECT COUNT(*) FROM enquiries WHERE customer_account_id = ?', [$accountId]);
        $orderCount = $this->count('SELECT COUNT(*) FROM orders WHERE customer_account_id = ?', [$accountId]);
        $openEnquiries = $this->count("SELECT COUNT(*) FROM enquiries WHERE customer_account_id = ? AND status IN ('new', 'contacted', 'in_progress')", [$accountId]);
        $openOrders = $this->count("SELECT COUNT(*) FROM orders WHERE customer_account_id = ? AND status IN ('new', 'confirmed', 'in_progress')", [$accountId]);

        $recentEnquiries = $pdo->prepare(
            'SELECT e.*, b.name AS business_name, b.slug AS business_slug
             FROM enquiries e
             INNER JOIN businesses b ON b.id = e.business_id
             WHERE e.customer_account_id = ?
             ORDER BY e.created_at DESC LIMIT 5'
        );
        $recentEnquiries->execute([$accountId]);

        $recentOrders = $pdo->prepare(
            'SELECT o.*, b.name AS business_name, b.slug AS business_slug
             FROM orders o
             INNER JOIN businesses b ON b.id = o.business_id
             WHERE o.customer_account_id = ?
             ORDER BY o.created_at DESC LIMIT 5'
        );
        $recentOrders->execute([$accountId]);

        $recentActivity = $this->recentBusinessWebsites();

        $this->render('customer.dashboard', [
            'pageTitle' => 'Customer Dashboard',
            'active' => 'dashboard',
            'customer' => $customer,
            'metrics' => [
                'enquiries' => $enquiryCount,
                'orders' => $orderCount,
                'open_enquiries' => $openEnquiries,
                'open_orders' => $openOrders,
            ],
            'recentEnquiries' => $recentEnquiries->fetchAll(PDO::FETCH_ASSOC),
            'recentOrders' => $recentOrders->fetchAll(PDO::FETCH_ASSOC),
            'recentBusinesses' => $recentActivity,
            'customerUnreadNotifications' => NotificationService::unreadCountForCustomer($accountId),
        ], 'layouts/customer');
    }

    public function profile(): void
    {
        CustomerAuth::requireLogin();
        $this->render('customer.profile', [
            'pageTitle' => 'My Profile',
            'active' => 'profile',
            'customer' => CustomerAuth::customer() ?? [],
            'googleEnabled' => GoogleOAuthService::isConfigured(),
        ], 'layouts/customer');
        clear_old_input();
    }

    public function updateProfile(): void
    {
        CustomerAuth::requireLogin();
        $this->verifyCsrf();
        $accountId = (int) CustomerAuth::id();

        $validator = (new Validator())
            ->required('name', 'Name')
            ->max('name', 'Name', 190)
            ->email('email', 'Email')
            ->max('phone', 'Phone', 40);

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/customer/profile');
        }

        $pdo = Database::pdo();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if ($email !== '') {
            $check = $pdo->prepare('SELECT id FROM customer_accounts WHERE email = ? AND id <> ? LIMIT 1');
            $check->execute([$email, $accountId]);
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                Flash::error('That email is already used by another account.');
                $this->redirect('/customer/profile');
            }
        }

        $customer = CustomerAuth::customer() ?? [];
        try {
            $avatar = UploadService::image('avatar', 'uploads/customer-accounts', $customer['avatar_path'] ?? null);
        } catch (Throwable $exception) {
            Flash::error($exception->getMessage());
            $this->redirect('/customer/profile');
            return;
        }
        if (isset($_POST['avatar_remove'])) {
            $avatar = null;
        }

        $stmt = $pdo->prepare(
            'UPDATE customer_accounts SET name = ?, email = COALESCE(NULLIF(?, ""), email), phone = ?, avatar_path = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([
            trim((string) $_POST['name']),
            $email,
            trim((string) ($_POST['phone'] ?? '')) ?: null,
            $avatar,
            $accountId,
        ]);

        Flash::success('Profile updated.');
        CustomerAuth::clearCache();
        $this->redirect('/customer/profile');
    }

    public function changePassword(): void
    {
        CustomerAuth::requireLogin();
        $this->verifyCsrf();
        $accountId = (int) CustomerAuth::id();
        $customer = CustomerAuth::customer() ?? [];

        $validator = (new Validator())
            ->required('current_password', 'Current password')
            ->required('password', 'New password')
            ->min('password', 'New password', 8)
            ->matches('password_confirmation', 'password', 'Password confirmation');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/customer/profile');
        }

        if (empty($customer['password_hash'])) {
            Flash::warning('This account signs in with Google, so there is no password to change. Use "Set a password" to add one.');
            $this->redirect('/customer/profile');
        }

        if (!password_verify((string) $_POST['current_password'], (string) $customer['password_hash'])) {
            Flash::error('Current password is incorrect.');
            $this->redirect('/customer/profile');
        }

        $stmt = Database::pdo()->prepare('UPDATE customer_accounts SET password_hash = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([password_hash((string) $_POST['password'], PASSWORD_DEFAULT), $accountId]);

        Flash::success('Password changed.');
        $this->redirect('/customer/profile');
    }

    public function setInitialPassword(): void
    {
        CustomerAuth::requireLogin();
        $this->verifyCsrf();
        $customer = CustomerAuth::customer() ?? [];
        $accountId = (int) CustomerAuth::id();

        $validator = (new Validator())
            ->required('password', 'Password')
            ->min('password', 'Password', 8)
            ->matches('password_confirmation', 'password', 'Password confirmation');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/customer/profile');
        }

        if (!empty($customer['password_hash'])) {
            $this->changePassword();
            return;
        }

        $stmt = Database::pdo()->prepare('UPDATE customer_accounts SET password_hash = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([password_hash((string) $_POST['password'], PASSWORD_DEFAULT), $accountId]);
        Flash::success('Password set. You can now sign in with email + password as well.');
        $this->redirect('/customer/profile');
    }

    public function enquiries(): void
    {
        CustomerAuth::requireLogin();
        $accountId = (int) CustomerAuth::id();

        $stmt = Database::pdo()->prepare(
            'SELECT e.*, b.name AS business_name, b.slug AS business_slug, l.title AS listing_title
             FROM enquiries e
             INNER JOIN businesses b ON b.id = e.business_id
             LEFT JOIN listings l ON l.id = e.listing_id AND l.business_id = e.business_id
             WHERE e.customer_account_id = ?
             ORDER BY e.created_at DESC
             LIMIT 200'
        );
        $stmt->execute([$accountId]);

        $this->render('customer.enquiries', [
            'pageTitle' => 'My Enquiries',
            'active' => 'enquiries',
            'customer' => CustomerAuth::customer() ?? [],
            'enquiries' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ], 'layouts/customer');
    }

    public function orders(): void
    {
        CustomerAuth::requireLogin();
        $accountId = (int) CustomerAuth::id();

        $stmt = Database::pdo()->prepare(
            'SELECT o.*, b.name AS business_name, b.slug AS business_slug, l.title AS listing_title
             FROM orders o
             INNER JOIN businesses b ON b.id = o.business_id
             LEFT JOIN listings l ON l.id = o.listing_id AND l.business_id = o.business_id
             WHERE o.customer_account_id = ?
             ORDER BY o.created_at DESC
             LIMIT 200'
        );
        $stmt->execute([$accountId]);

        $this->render('customer.orders', [
            'pageTitle' => 'My Orders & Requests',
            'active' => 'orders',
            'customer' => CustomerAuth::customer() ?? [],
            'orders' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ], 'layouts/customer');
    }

    public function notifications(): void
    {
        CustomerAuth::requireLogin();
        $accountId = (int) CustomerAuth::id();
        $notifications = NotificationService::latestForCustomer($accountId, 100);

        if (isset($_POST['mark_read'])) {
            $this->verifyCsrf();
            NotificationService::markCustomerRead($accountId);
            Flash::success('Notifications marked as read.');
            $this->redirect('/customer/notifications');
        }

        $this->render('customer.notifications', [
            'pageTitle' => 'Notifications',
            'active' => 'notifications',
            'customer' => CustomerAuth::customer() ?? [],
            'notifications' => $notifications,
        ], 'layouts/customer');
    }

    public function enquiryDetail(string $id): void
    {
        CustomerAuth::requireLogin();
        $enquiry = $this->customerEnquiry((int) $id);

        $historyStmt = Database::pdo()->prepare(
            'SELECT * FROM enquiry_status_history WHERE enquiry_id = ? ORDER BY created_at ASC'
        );
        $historyStmt->execute([(int) $enquiry['id']]);

        $this->render('customer.enquiry_detail', [
            'pageTitle' => 'Enquiry #' . (int) $enquiry['id'],
            'active' => 'enquiries',
            'customer' => CustomerAuth::customer() ?? [],
            'enquiry' => $enquiry,
            'history' => $historyStmt->fetchAll(PDO::FETCH_ASSOC),
        ], 'layouts/customer');
    }

    public function orderDetail(string $id): void
    {
        CustomerAuth::requireLogin();
        $order = $this->customerOrder((int) $id);

        $historyStmt = Database::pdo()->prepare(
            'SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC'
        );
        $historyStmt->execute([(int) $order['id']]);

        $this->render('customer.order_detail', [
            'pageTitle' => 'Request ' . $order['order_number'],
            'active' => 'orders',
            'customer' => CustomerAuth::customer() ?? [],
            'order' => $order,
            'history' => $historyStmt->fetchAll(PDO::FETCH_ASSOC),
        ], 'layouts/customer');
    }

    private function customerEnquiry(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT e.*, b.name AS business_name, b.slug AS business_slug, l.title AS listing_title
             FROM enquiries e
             INNER JOIN businesses b ON b.id = e.business_id
             LEFT JOIN listings l ON l.id = e.listing_id AND l.business_id = e.business_id
             WHERE e.id = ? AND e.customer_account_id = ?
             LIMIT 1'
        );
        $stmt->execute([$id, (int) CustomerAuth::id()]);
        $enquiry = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$enquiry) {
            throw new HttpException(404, 'Enquiry not found for your account.');
        }
        return $enquiry;
    }

    private function customerOrder(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT o.*, b.name AS business_name, b.slug AS business_slug, l.title AS listing_title
             FROM orders o
             INNER JOIN businesses b ON b.id = o.business_id
             LEFT JOIN listings l ON l.id = o.listing_id AND l.business_id = o.business_id
             WHERE o.id = ? AND o.customer_account_id = ?
             LIMIT 1'
        );
        $stmt->execute([$id, (int) CustomerAuth::id()]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new HttpException(404, 'Request not found for your account.');
        }
        return $order;
    }

    private function recentBusinessWebsites(): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT b.name, b.slug, b.category, b.tagline, b.city, b.logo_path, b.cover_path
             FROM businesses b
             INNER JOIN business_settings w ON w.business_id = b.id
             WHERE b.status IN ("active", "approved")
               AND w.website_enabled = 1
               AND w.website_published = 1
               AND w.show_in_directory = 1
             ORDER BY b.created_at DESC
             LIMIT 6'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function count(string $sql, array $params): int
    {
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
