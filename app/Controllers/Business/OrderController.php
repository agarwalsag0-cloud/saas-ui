<?php

declare(strict_types=1);

namespace App\Controllers\Business;

use App\Core\Database;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Services\ActivityLogger;
use PDO;

class OrderController extends BaseBusinessController
{
    public function index(): void
    {
        $this->requireFeature('orders', 'Orders');
        $businessId = $this->tenantId();
        $where = ['o.business_id = ?'];
        $params = [$businessId];
        $allowedTypes = $this->allowedRequestTypes();
        if ($allowedTypes) {
            $where[] = 'o.request_type IN (' . implode(',', array_fill(0, count($allowedTypes), '?')) . ')';
            array_push($params, ...$allowedTypes);
        } else {
            $where[] = '1 = 0';
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));

        if ($q !== '') {
            $where[] = '(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.phone LIKE ? OR o.email LIKE ? OR o.details LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($status !== '') {
            $where[] = 'o.status = ?';
            $params[] = $status;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT o.*, l.title AS listing_title
             FROM orders o
             LEFT JOIN listings l ON l.id = o.listing_id AND l.business_id = o.business_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY o.created_at DESC
             LIMIT 100'
        );
        $stmt->execute($params);

        $this->renderBusiness('business.orders', [
            'pageTitle' => 'Orders & Requests',
            'active' => 'orders',
            'orders' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function updateStatus(string $id): void
    {
        $this->requireFeature('orders', 'Orders');
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $order = $this->order((int) $id);
        $this->requireRequestTypeAccess((string) $order['request_type']);

        $validator = (new Validator())
            ->in('status', 'Status', ['new', 'confirmed', 'in_progress', 'completed', 'cancelled', 'closed'])
            ->max('internal_notes', 'Internal notes', 3000);

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/business/orders');
        }

        $newStatus = (string) ($_POST['status'] ?? $order['status']);
        $note = trim((string) ($_POST['internal_notes'] ?? ''));
        $businessId = $this->tenantId();
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('UPDATE orders SET status = ?, internal_notes = ?, updated_at = NOW() WHERE id = ? AND business_id = ?');
        $stmt->execute([$newStatus, $note ?: null, (int) $order['id'], $businessId]);

        if ($newStatus !== $order['status'] || $note !== '') {
            $history = $pdo->prepare('INSERT INTO order_status_history (order_id, business_id, old_status, new_status, note, changed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $history->execute([(int) $order['id'], $businessId, $order['status'], $newStatus, $note ?: null, (int) $this->businessUser['id']]);
        }
        $pdo->commit();

        ActivityLogger::log('order_status_updated', 'order', (int) $order['id'], $businessId, ['to' => $newStatus]);
        Flash::success('Order/request updated.');
        $this->redirect('/business/orders');
    }

    private function allowedRequestTypes(): array
    {
        $types = [];
        if ($this->hasFeature('product_management')) {
            $types[] = 'product_order';
        }
        if ($this->hasFeature('service_management')) {
            $types[] = 'package_enquiry';
            $types[] = 'custom_request';
            if ($this->hasFeature('service_requests')) {
                $types[] = 'service_request';
            }
            if ($this->hasFeature('booking_requests')) {
                $types[] = 'booking_request';
            }
        }
        return $types;
    }

    private function requireRequestTypeAccess(string $requestType): void
    {
        if (!in_array($requestType, $this->allowedRequestTypes(), true)) {
            $this->renderBusiness('business.feature_unavailable', [
                'pageTitle' => 'Feature Not Included',
                'active' => 'orders',
                'featureName' => ucwords(str_replace('_', ' ', $requestType)),
                'reason' => 'not_included',
                'plans' => $this->availablePlans(),
            ]);
            exit;
        }
    }

    private function order(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM orders WHERE id = ? AND business_id = ? LIMIT 1');
        $stmt->execute([$id, $this->tenantId()]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new HttpException(404, 'Order not found for this business.');
        }
        return $order;
    }
}
