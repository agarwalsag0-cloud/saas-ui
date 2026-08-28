<?php

declare(strict_types=1);

namespace App\Controllers\Business;

use App\Core\Database;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Services\ActivityLogger;
use App\Services\SlugService;
use App\Services\UploadService;
use PDO;
use Throwable;

class ListingController extends BaseBusinessController
{
    public function index(): void
    {
        $this->requireAnyFeature(['product_management', 'service_management'], 'Product/Service Management');
        $businessId = $this->tenantId();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $where = ['l.business_id = ?', 'l.status <> "archived"'];
        $params = [$businessId];
        $allowedTypes = array_keys($this->allowedListingTypes());
        if ($allowedTypes) {
            $where[] = 'l.type IN (' . implode(',', array_fill(0, count($allowedTypes), '?')) . ')';
            array_push($params, ...$allowedTypes);
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        $type = trim((string) ($_GET['type'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $categoryId = (int) ($_GET['category_id'] ?? 0);

        if ($q !== '') {
            $where[] = '(l.title LIKE ? OR l.description LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if ($type !== '' && in_array($type, $allowedTypes, true)) {
            $where[] = 'l.type = ?';
            $params[] = $type;
        }
        if ($status !== '') {
            $where[] = 'l.status = ?';
            $params[] = $status;
        }
        if ($categoryId > 0) {
            $where[] = 'l.category_id = ?';
            $params[] = $categoryId;
        }

        $whereSql = implode(' AND ', $where);
        $countStmt = Database::pdo()->prepare('SELECT COUNT(*) FROM listings l WHERE ' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = Database::pdo()->prepare(
            'SELECT l.*, c.name AS category_name
             FROM listings l
             LEFT JOIN categories c ON c.id = l.category_id AND c.business_id = l.business_id
             WHERE ' . $whereSql . '
             ORDER BY l.is_featured DESC, l.created_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $stmt->execute($params);

        $this->renderBusiness('business.listings.index', [
            'pageTitle' => 'Products & Services',
            'active' => 'listings',
            'listings' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'categories' => $this->categories(),
            'allowedListingTypes' => $this->allowedListingTypes(),
            'filters' => compact('q', 'type', 'status', 'categoryId'),
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
        ]);
    }

    public function create(): void
    {
        $this->requireAnyFeature(['product_management', 'service_management'], 'Product/Service Management');
        $this->renderBusiness('business.listings.form', [
            'pageTitle' => 'Add Product/Service',
            'active' => 'listings',
            'listing' => null,
            'categories' => $this->categories(),
            'allowedListingTypes' => $this->allowedListingTypes(),
            'mode' => 'create',
            'specificationsText' => '',
        ]);
        clear_old_input();
    }

    public function store(): void
    {
        $this->requireAnyFeature(['product_management', 'service_management'], 'Product/Service Management');
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $businessId = $this->tenantId();

        $validator = (new Validator())
            ->required('title', 'Title')
            ->in('type', 'Listing type', ['product', 'service', 'package', 'booking', 'custom'])
            ->in('status', 'Status', ['active', 'inactive'])
            ->numeric('price', 'Price');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/business/listings/create');
        }

        $type = (string) ($_POST['type'] ?? 'product');
        $this->requireListingTypeFeature($type);
        $this->enforceListingLimit($type);

        $categoryId = $this->validCategoryId((int) ($_POST['category_id'] ?? 0));
        try {
            $imagePath = UploadService::image('image', 'uploads/businesses/' . $businessId . '/listings');
        } catch (Throwable $exception) {
            Flash::error($exception->getMessage());
            $_SESSION['_old'] = $_POST;
            $this->redirect('/business/listings/create');
        }

        $stockQuantity = ($this->hasFeature('inventory') && isset($_POST['manage_stock'])) ? (int) ($_POST['stock_quantity'] ?? 0) : null;
        $manageStock = ($this->hasFeature('inventory') && isset($_POST['manage_stock'])) ? 1 : 0;
        $isFeatured = ($this->hasFeature('featured_listings') && isset($_POST['is_featured'])) ? 1 : 0;
        $visibleOnWebsite = $this->hasFeature('public_website') ? (isset($_POST['visible_on_website']) ? 1 : 0) : 1;

        $slug = SlugService::unique('listings', (trim((string) ($_POST['slug'] ?? '')) !== '' ? (string) $_POST['slug'] : (string) $_POST['title']), $businessId);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO listings
             (business_id, category_id, type, title, slug, short_description, description, price, price_label, compare_at_price, stock_quantity, manage_stock, specifications, image_path, gallery, is_featured, visible_on_website, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            $businessId,
            $categoryId,
            $type,
            trim((string) $_POST['title']),
            $slug,
            trim((string) ($_POST['short_description'] ?? '')) ?: null,
            trim((string) ($_POST['description'] ?? '')) ?: null,
            ($_POST['price'] ?? '') !== '' ? (float) $_POST['price'] : null,
            trim((string) ($_POST['price_label'] ?? '')) ?: null,
            ($_POST['compare_at_price'] ?? '') !== '' ? (float) $_POST['compare_at_price'] : null,
            $stockQuantity,
            $manageStock,
            $this->specificationsJson((string) ($_POST['specifications'] ?? '')),
            $imagePath,
            $isFeatured,
            $visibleOnWebsite,
            (string) ($_POST['status'] ?? 'active'),
        ]);
        $listingId = (int) Database::pdo()->lastInsertId();

        ActivityLogger::log('listing_created', 'listing', $listingId, $businessId);
        Flash::success('Listing created.');
        $this->redirect('/business/listings');
    }

    public function edit(string $id): void
    {
        $this->requireAnyFeature(['product_management', 'service_management'], 'Product/Service Management');
        $listing = $this->listing((int) $id);
        $this->requireListingTypeFeature((string) $listing['type']);
        $this->renderBusiness('business.listings.form', [
            'pageTitle' => 'Edit Listing',
            'active' => 'listings',
            'listing' => $listing,
            'categories' => $this->categories(),
            'allowedListingTypes' => $this->allowedListingTypes(),
            'mode' => 'edit',
            'specificationsText' => $this->specificationsText($listing['specifications'] ?? ''),
        ]);
        clear_old_input();
    }

    public function update(string $id): void
    {
        $this->requireAnyFeature(['product_management', 'service_management'], 'Product/Service Management');
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $listing = $this->listing((int) $id);
        $this->requireListingTypeFeature((string) $listing['type']);
        $businessId = $this->tenantId();

        $validator = (new Validator())
            ->required('title', 'Title')
            ->in('type', 'Listing type', ['product', 'service', 'package', 'booking', 'custom'])
            ->in('status', 'Status', ['active', 'inactive'])
            ->numeric('price', 'Price')
            ->numeric('compare_at_price', 'Compare at price');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/business/listings/' . $listing['id'] . '/edit');
        }

        $type = (string) ($_POST['type'] ?? 'product');
        $this->requireListingTypeFeature($type);

        $categoryId = $this->validCategoryId((int) ($_POST['category_id'] ?? 0));
        try {
            $imagePath = UploadService::image('image', 'uploads/businesses/' . $businessId . '/listings', $listing['image_path'] ?? null);
        } catch (Throwable $exception) {
            Flash::error($exception->getMessage());
            $this->redirect('/business/listings/' . $listing['id'] . '/edit');
        }

        $stockQuantity = $this->hasFeature('inventory')
            ? (isset($_POST['manage_stock']) ? (int) ($_POST['stock_quantity'] ?? 0) : null)
            : ($listing['stock_quantity'] ?? null);
        $manageStock = $this->hasFeature('inventory')
            ? (isset($_POST['manage_stock']) ? 1 : 0)
            : (int) ($listing['manage_stock'] ?? 0);
        $isFeatured = $this->hasFeature('featured_listings')
            ? (isset($_POST['is_featured']) ? 1 : 0)
            : (int) ($listing['is_featured'] ?? 0);
        $visibleOnWebsite = $this->hasFeature('public_website')
            ? (isset($_POST['visible_on_website']) ? 1 : 0)
            : (int) ($listing['visible_on_website'] ?? 1);

        $slug = SlugService::unique('listings', (trim((string) ($_POST['slug'] ?? '')) !== '' ? (string) $_POST['slug'] : (string) $_POST['title']), $businessId, (int) $listing['id']);
        $stmt = Database::pdo()->prepare(
            'UPDATE listings
             SET category_id = ?, type = ?, title = ?, slug = ?, short_description = ?, description = ?, price = ?, price_label = ?, compare_at_price = ?, stock_quantity = ?, manage_stock = ?, specifications = ?, image_path = ?, is_featured = ?, visible_on_website = ?, status = ?, updated_at = NOW()
             WHERE id = ? AND business_id = ?'
        );
        $stmt->execute([
            $categoryId,
            $type,
            trim((string) $_POST['title']),
            $slug,
            trim((string) ($_POST['short_description'] ?? '')) ?: null,
            trim((string) ($_POST['description'] ?? '')) ?: null,
            ($_POST['price'] ?? '') !== '' ? (float) $_POST['price'] : null,
            trim((string) ($_POST['price_label'] ?? '')) ?: null,
            ($_POST['compare_at_price'] ?? '') !== '' ? (float) $_POST['compare_at_price'] : null,
            $stockQuantity,
            $manageStock,
            $this->specificationsJson((string) ($_POST['specifications'] ?? '')),
            $imagePath,
            $isFeatured,
            $visibleOnWebsite,
            (string) ($_POST['status'] ?? 'active'),
            (int) $listing['id'],
            $businessId,
        ]);

        ActivityLogger::log('listing_updated', 'listing', (int) $listing['id'], $businessId);
        Flash::success('Listing updated.');
        $this->redirect('/business/listings');
    }

    public function archive(string $id): void
    {
        $this->requireAnyFeature(['product_management', 'service_management'], 'Product/Service Management');
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $listing = $this->listing((int) $id);
        $this->requireListingTypeFeature((string) $listing['type']);
        $stmt = Database::pdo()->prepare('UPDATE listings SET status = "archived", updated_at = NOW() WHERE id = ? AND business_id = ?');
        $stmt->execute([(int) $listing['id'], $this->tenantId()]);
        ActivityLogger::log('listing_archived', 'listing', (int) $listing['id'], $this->tenantId());
        Flash::success('Listing archived.');
        $this->redirect('/business/listings');
    }

    private function allowedListingTypes(): array
    {
        $types = [];
        if ($this->hasFeature('product_management')) {
            $types['product'] = 'Product';
        }
        if ($this->hasFeature('service_management')) {
            $types['service'] = 'Service';
            $types['package'] = 'Package';
            $types['booking'] = 'Booking';
            $types['custom'] = 'Custom';
        }
        return $types;
    }

    private function listingFeatureForType(string $type): string
    {
        return $type === 'product' ? 'product_management' : 'service_management';
    }

    private function requireListingTypeFeature(string $type): void
    {
        if (!array_key_exists($type, $this->allowedListingTypes())) {
            $this->requireFeature($this->listingFeatureForType($type), $type === 'product' ? 'Product Management' : 'Service Management');
        }
    }

    private function enforceListingLimit(string $type): void
    {
        $feature = $this->listingFeatureForType($type);
        $limit = $this->featureLimit($feature, 'max_items');
        if ($limit === null || $limit === '' || (int) $limit <= 0) {
            return;
        }

        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM listings WHERE business_id = ? AND status <> "archived" AND type = ?');
        $stmt->execute([$this->tenantId(), $type]);
        if ((int) $stmt->fetchColumn() >= (int) $limit) {
            $this->showLimitReached(
                $type === 'product' ? 'Product Management' : 'Service Management',
                'Your current plan allows up to ' . (int) $limit . ' ' . str_replace('_', ' ', $type) . ' listing(s). Please upgrade to add more.'
            );
        }
    }

    private function listing(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM listings WHERE id = ? AND business_id = ? LIMIT 1');
        $stmt->execute([$id, $this->tenantId()]);
        $listing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$listing) {
            throw new HttpException(404, 'Listing not found for this business.');
        }
        return $listing;
    }

    private function categories(): array
    {
        if (!$this->hasFeature('categories')) {
            return [];
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM categories WHERE business_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC');
        $stmt->execute([$this->tenantId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validCategoryId(int $categoryId): ?int
    {
        if ($categoryId <= 0) {
            return null;
        }
        if (!$this->hasFeature('categories')) {
            throw new HttpException(403, 'Categories are not included in your current plan.');
        }
        $stmt = Database::pdo()->prepare('SELECT id FROM categories WHERE id = ? AND business_id = ? LIMIT 1');
        $stmt->execute([$categoryId, $this->tenantId()]);
        if (!$stmt->fetchColumn()) {
            throw new HttpException(403, 'Invalid category for this business.');
        }
        return $categoryId;
    }

    private function specificationsJson(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        if ($text[0] === '{' || $text[0] === '[') {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_SLASHES);
            }
        }

        $specs = [];
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (strpos($line, ':') !== false) {
                [$key, $value] = array_map('trim', explode(':', $line, 2));
                if ($key !== '') {
                    $specs[$key] = $value;
                }
            } else {
                $specs[] = $line;
            }
        }

        return json_encode($specs, JSON_UNESCAPED_SLASHES);
    }

    private function specificationsText(?string $json): string
    {
        $decoded = json_decode($json ?: '[]', true);
        if (!is_array($decoded)) {
            return '';
        }

        $lines = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $lines[] = $key . ': ' . (is_scalar($value) ? (string) $value : json_encode($value));
            } else {
                $lines[] = is_scalar($value) ? (string) $value : json_encode($value);
            }
        }
        return implode(PHP_EOL, $lines);
    }
}
