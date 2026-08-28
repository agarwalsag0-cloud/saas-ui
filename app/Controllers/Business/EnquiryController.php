<?php

declare(strict_types=1);

namespace App\Controllers\Business;

use App\Core\Database;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Services\ActivityLogger;
use PDO;

class EnquiryController extends BaseBusinessController
{
    public function index(): void
    {
        $this->requireFeature('enquiries', 'Enquiry Management');
        $businessId = $this->tenantId();
        $where = ['e.business_id = ?'];
        $params = [$businessId];
        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));

        if ($q !== '') {
            $where[] = '(e.name LIKE ? OR e.phone LIKE ? OR e.email LIKE ? OR e.message LIKE ? OR e.requested_item LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($status !== '') {
            $where[] = 'e.status = ?';
            $params[] = $status;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT e.*, l.title AS listing_title
             FROM enquiries e
             LEFT JOIN listings l ON l.id = e.listing_id AND l.business_id = e.business_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY e.created_at DESC
             LIMIT 100'
        );
        $stmt->execute($params);

        $this->renderBusiness('business.enquiries', [
            'pageTitle' => 'Customer Enquiries',
            'active' => 'enquiries',
            'enquiries' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function updateStatus(string $id): void
    {
        $this->requireFeature('enquiries', 'Enquiry Management');
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $enquiry = $this->enquiry((int) $id);

        $validator = (new Validator())
            ->in('status', 'Status', ['new', 'contacted', 'in_progress', 'converted', 'closed'])
            ->max('internal_notes', 'Internal notes', 3000);

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/business/enquiries');
        }

        $newStatus = (string) ($_POST['status'] ?? $enquiry['status']);
        $note = trim((string) ($_POST['internal_notes'] ?? ''));
        $businessId = $this->tenantId();
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('UPDATE enquiries SET status = ?, internal_notes = ?, updated_at = NOW() WHERE id = ? AND business_id = ?');
        $stmt->execute([$newStatus, $note ?: null, (int) $enquiry['id'], $businessId]);

        if ($newStatus !== $enquiry['status'] || $note !== '') {
            $history = $pdo->prepare('INSERT INTO enquiry_status_history (enquiry_id, business_id, old_status, new_status, note, changed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $history->execute([(int) $enquiry['id'], $businessId, $enquiry['status'], $newStatus, $note ?: null, (int) $this->businessUser['id']]);
        }
        $pdo->commit();

        ActivityLogger::log('enquiry_status_updated', 'enquiry', (int) $enquiry['id'], $businessId, ['to' => $newStatus]);
        Flash::success('Enquiry updated.');
        $this->redirect('/business/enquiries');
    }

    private function enquiry(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM enquiries WHERE id = ? AND business_id = ? LIMIT 1');
        $stmt->execute([$id, $this->tenantId()]);
        $enquiry = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$enquiry) {
            throw new HttpException(404, 'Enquiry not found for this business.');
        }
        return $enquiry;
    }
}
