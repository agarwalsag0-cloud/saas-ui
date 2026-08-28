<?php

declare(strict_types=1);

namespace App\Controllers\Business;

use App\Core\Database;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Services\ActivityLogger;
use PDO;

class OfferController extends BaseBusinessController
{
    public function index(): void
    {
        $this->requireFeature('offers', 'Offers');
        $businessId = $this->tenantId();
        $typeWhere = $this->listingTypeWhere('l');
        $offers = Database::pdo()->prepare(
            'SELECT o.*, l.title AS listing_title
             FROM offers o
             LEFT JOIN listings l ON l.id = o.listing_id AND l.business_id = o.business_id
             WHERE o.business_id = ?' . ($typeWhere ? ' AND (o.listing_id IS NULL OR ' . ltrim($typeWhere, ' AND') . ')' : '') . '
             ORDER BY o.created_at DESC'
        );
        $offers->execute([$businessId]);

        $listings = Database::pdo()->prepare('SELECT id, title FROM listings l WHERE business_id = ? AND status <> "archived"' . $typeWhere . ' ORDER BY title ASC');
        $listings->execute([$businessId]);

        $this->renderBusiness('business.offers', [
            'pageTitle' => 'Offers',
            'active' => 'offers',
            'offers' => $offers->fetchAll(PDO::FETCH_ASSOC),
            'listings' => $listings->fetchAll(PDO::FETCH_ASSOC),
        ]);
        clear_old_input();
    }

    public function store(): void
    {
        $this->requireFeature('offers', 'Offers');
        $this->guardWriteAccess();
        $this->verifyCsrf();

        $validator = (new Validator())
            ->required('title', 'Offer title')
            ->in('discount_type', 'Discount type', ['percentage', 'fixed', 'custom'])
            ->numeric('discount_value', 'Discount value');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/business/offers');
        }

        $businessId = $this->tenantId();
        $listingId = $this->validListingId((int) ($_POST['listing_id'] ?? 0));

        $stmt = Database::pdo()->prepare(
            'INSERT INTO offers
             (business_id, listing_id, title, description, discount_type, discount_value, start_at, end_at, is_active, visible_on_website, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            $businessId,
            $listingId,
            trim((string) $_POST['title']),
            trim((string) ($_POST['description'] ?? '')) ?: null,
            (string) $_POST['discount_type'],
            ($_POST['discount_value'] ?? '') !== '' ? (float) $_POST['discount_value'] : null,
            ($_POST['start_at'] ?? '') ?: null,
            ($_POST['end_at'] ?? '') ?: null,
            isset($_POST['is_active']) ? 1 : 0,
            $this->hasFeature('public_website') ? (isset($_POST['visible_on_website']) ? 1 : 0) : 1,
        ]);
        $offerId = (int) Database::pdo()->lastInsertId();

        ActivityLogger::log('offer_created', 'offer', $offerId, $businessId);
        Flash::success('Offer created.');
        $this->redirect('/business/offers');
    }

    public function update(string $id): void
    {
        $this->requireFeature('offers', 'Offers');
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $offer = $this->offer((int) $id);
        $businessId = $this->tenantId();

        $validator = (new Validator())
            ->required('title', 'Offer title')
            ->in('discount_type', 'Discount type', ['percentage', 'fixed', 'custom'])
            ->numeric('discount_value', 'Discount value');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/business/offers');
        }

        $listingId = $this->validListingId((int) ($_POST['listing_id'] ?? 0));
        $stmt = Database::pdo()->prepare(
            'UPDATE offers
             SET listing_id = ?, title = ?, description = ?, discount_type = ?, discount_value = ?, start_at = ?, end_at = ?, is_active = ?, visible_on_website = ?, updated_at = NOW()
             WHERE id = ? AND business_id = ?'
        );
        $stmt->execute([
            $listingId,
            trim((string) $_POST['title']),
            trim((string) ($_POST['description'] ?? '')) ?: null,
            (string) $_POST['discount_type'],
            ($_POST['discount_value'] ?? '') !== '' ? (float) $_POST['discount_value'] : null,
            ($_POST['start_at'] ?? '') ?: null,
            ($_POST['end_at'] ?? '') ?: null,
            isset($_POST['is_active']) ? 1 : 0,
            $this->hasFeature('public_website') ? (isset($_POST['visible_on_website']) ? 1 : 0) : (int) ($offer['visible_on_website'] ?? 1),
            (int) $offer['id'],
            $businessId,
        ]);

        ActivityLogger::log('offer_updated', 'offer', (int) $offer['id'], $businessId);
        Flash::success('Offer updated.');
        $this->redirect('/business/offers');
    }

    public function delete(string $id): void
    {
        $this->requireFeature('offers', 'Offers');
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $offer = $this->offer((int) $id);
        $stmt = Database::pdo()->prepare('DELETE FROM offers WHERE id = ? AND business_id = ?');
        $stmt->execute([(int) $offer['id'], $this->tenantId()]);
        ActivityLogger::log('offer_deleted', 'offer', (int) $offer['id'], $this->tenantId());
        Flash::success('Offer deleted.');
        $this->redirect('/business/offers');
    }

    private function offer(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM offers WHERE id = ? AND business_id = ? LIMIT 1');
        $stmt->execute([$id, $this->tenantId()]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$offer) {
            throw new HttpException(404, 'Offer not found for this business.');
        }
        return $offer;
    }

    private function validListingId(int $listingId): ?int
    {
        if ($listingId <= 0) {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT id, type FROM listings WHERE id = ? AND business_id = ? LIMIT 1');
        $stmt->execute([$listingId, $this->tenantId()]);
        $listing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$listing) {
            throw new HttpException(403, 'Invalid listing for this business.');
        }
        if (($listing['type'] ?? '') === 'product' && !$this->hasFeature('product_management')) {
            throw new HttpException(403, 'Product management is not included in your current plan.');
        }
        if (($listing['type'] ?? '') !== 'product' && !$this->hasFeature('service_management')) {
            throw new HttpException(403, 'Service management is not included in your current plan.');
        }
        return $listingId;
    }

    private function listingTypeWhere(string $alias = 'l'): string
    {
        if ($this->hasFeature('product_management') && !$this->hasFeature('service_management')) {
            return ' AND ' . $alias . '.type = "product"';
        }
        if (!$this->hasFeature('product_management') && $this->hasFeature('service_management')) {
            return ' AND ' . $alias . '.type <> "product"';
        }
        if (!$this->hasFeature('product_management') && !$this->hasFeature('service_management')) {
            return ' AND 1 = 0';
        }
        return '';
    }
}
