<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\CustomerAuth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Services\ActivityLogger;
use App\Services\FeatureService;
use App\Services\NotificationService;
use App\Services\SubscriptionService;
use App\Services\WebsiteAccessService;
use PDO;

class PublicPortalController extends Controller
{
    // ---- Public directory -------------------------------------------------

    public function landing(): void
    {
        $pdo = Database::pdo();
        $q = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $city = trim((string) ($_GET['city'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 12;

        $where = [WebsiteAccessService::directoryWhere()];
        $params = [];
        if ($q !== '') {
            $where[] = '(b.name LIKE ? OR b.tagline LIKE ? OR b.description LIKE ? OR b.city LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($category !== '') {
            $where[] = 'b.category = ?';
            $params[] = $category;
        }
        if ($city !== '') {
            $where[] = 'b.city = ?';
            $params[] = $city;
        }
        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM businesses b LEFT JOIN business_settings w ON w.business_id = b.id WHERE ' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            'SELECT b.id, b.name, b.slug, b.category, b.tagline, b.city, b.state, b.description, b.logo_path, b.cover_path
             FROM businesses b
             LEFT JOIN business_settings w ON w.business_id = b.id
             WHERE ' . $whereSql . '
             ORDER BY b.approved_at DESC, b.created_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $stmt->execute($params);

        $categoryStmt = $pdo->query(
            'SELECT DISTINCT b.category FROM businesses b
             LEFT JOIN business_settings w ON w.business_id = b.id
             WHERE ' . WebsiteAccessService::directoryWhere() . '
             ORDER BY b.category ASC'
        );
        $cityStmt = $pdo->query(
            'SELECT DISTINCT b.city FROM businesses b
             LEFT JOIN business_settings w ON w.business_id = b.id
             WHERE ' . WebsiteAccessService::directoryWhere() . ' AND b.city IS NOT NULL AND b.city <> ""
             ORDER BY b.city ASC'
        );

        $this->render('public.landing', [
            'pageTitle' => 'Discover Local Businesses',
            'businesses' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'categories' => $categoryStmt->fetchAll(PDO::FETCH_COLUMN) ?: [],
            'cities' => $cityStmt->fetchAll(PDO::FETCH_COLUMN) ?: [],
            'filters' => ['q' => $q, 'category' => $category, 'city' => $city],
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ], 'layouts/public');
    }

    // ---- Tenant websites ----------------------------------------------------

    public function portal(string $slug): void
    {
        $this->renderSite($slug, $this->previewRequested());
    }

    private function previewRequested(): bool
    {
        return (string) ($_GET['preview'] ?? '') === '1';
    }

    /**
     * Renders the tenant website. In preview mode the visitor must be an
     * authorized viewer (business owner of this tenant or Super Admin); the
     * page is always served with `noindex` and is never linked publicly.
     */
    public function renderSite(string $slug, bool $preview): void
    {
        $business = $this->businessBySlug($slug);
        $businessId = (int) $business['id'];

        $settings = WebsiteAccessService::settingsFor($businessId);
        $subscription = SubscriptionService::current($businessId);
        $featureAccess = FeatureService::featuresForBusiness($businessId, $business, $subscription);
        $access = WebsiteAccessService::evaluate($business, $subscription, $settings, $featureAccess);

        $previewAllowed = $preview
            && $this->isPreviewViewer($businessId)
            && ($access['preview_available'] || \App\Core\Auth::isSuperAdmin());

        if (!$previewAllowed) {
            if (!$access['public_access']) {
                $reason = !$access['enabled_by_admin'] ? 'website_disabled'
                    : (!$access['published'] ? 'unpublished'
                    : (!$access['plan_entitled'] ? 'feature_not_included'
                    : SubscriptionService::effectiveStatus($business, $subscription)));
                $this->render('public.unavailable', [
                    'pageTitle' => $business['name'] . ' unavailable',
                    'business' => $business,
                    'settings' => $access['settings'],
                    'subscription' => $subscription,
                    'effectiveStatus' => $reason,
                    'noindex' => true,
                ], 'layouts/website');
                return;
            }
        }

        $settings = $access['settings'];
        $canCategories = isset($featureAccess['categories']);
        $canListings = isset($featureAccess['product_management']) || isset($featureAccess['service_management']);
        $canOffers = isset($featureAccess['offers']);
        $canEnquiries = isset($featureAccess['enquiries']);
        $canOrders = isset($featureAccess['orders']);

        $categorySlug = $canCategories ? trim((string) ($_GET['category'] ?? '')) : '';
        $params = [$businessId];
        $where = 'l.business_id = ? AND l.status = "active" AND l.visible_on_website = 1';
        $typeWhere = '';
        $offerTypeWhere = '';
        if (isset($featureAccess['product_management']) && !isset($featureAccess['service_management'])) {
            $typeWhere = ' AND l.type = "product"';
            $offerTypeWhere = ' AND (o.listing_id IS NULL OR l.type = "product")';
            $where .= $typeWhere;
        } elseif (!isset($featureAccess['product_management']) && isset($featureAccess['service_management'])) {
            $typeWhere = ' AND l.type <> "product"';
            $offerTypeWhere = ' AND (o.listing_id IS NULL OR l.type <> "product")';
            $where .= $typeWhere;
        }

        if ($categorySlug !== '') {
            $where .= ' AND c.slug = ? AND c.visible_on_website = 1 AND c.is_active = 1';
            $params[] = $categorySlug;
        }

        $categoriesStmt = Database::pdo()->prepare(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM listings l WHERE l.business_id = c.business_id AND l.category_id = c.id AND l.status = "active" AND l.visible_on_website = 1' . $typeWhere . ') AS public_listing_count
             FROM categories c
             WHERE c.business_id = ? AND c.is_active = 1 AND c.visible_on_website = 1
             ORDER BY c.sort_order ASC, c.name ASC'
        );
        $categoriesStmt->execute([$businessId]);

        $listingsStmt = Database::pdo()->prepare(
            'SELECT l.*, c.name AS category_name, c.slug AS category_slug
             FROM listings l
             LEFT JOIN categories c ON c.id = l.category_id AND c.business_id = l.business_id
             WHERE ' . $where . '
             ORDER BY l.is_featured DESC, l.created_at DESC
             LIMIT 48'
        );
        $listingsStmt->execute($params);

        $featuredStmt = Database::pdo()->prepare(
            'SELECT l.*, c.name AS category_name
             FROM listings l
             LEFT JOIN categories c ON c.id = l.category_id AND c.business_id = l.business_id
             WHERE l.business_id = ? AND l.status = "active" AND l.visible_on_website = 1 AND l.is_featured = 1' . $typeWhere . '
             ORDER BY l.updated_at DESC
             LIMIT 6'
        );
        $featuredStmt->execute([$businessId]);

        $offersStmt = Database::pdo()->prepare(
            'SELECT o.*, l.title AS listing_title, l.slug AS listing_slug
             FROM offers o
             LEFT JOIN listings l ON l.id = o.listing_id AND l.business_id = o.business_id
             WHERE o.business_id = ? AND o.is_active = 1 AND o.visible_on_website = 1
               AND (o.listing_id IS NULL OR l.visible_on_website = 1)' . $offerTypeWhere . '
               AND (o.start_at IS NULL OR o.start_at <= CURDATE())
               AND (o.end_at IS NULL OR o.end_at >= CURDATE())
             ORDER BY o.created_at DESC
             LIMIT 8'
        );
        $offersStmt->execute([$businessId]);

        $customer = CustomerAuth::customer();

        $this->render('public.portal', [
            'pageTitle' => $settings['seo_title'] ?: $business['name'],
            'seoDescription' => $settings['seo_description'] ?: text_excerpt($business['description'] ?: ($business['tagline'] ?? ''), 160),
            'business' => $business,
            'settings' => array_merge($settings, [
                'allow_public_enquiries' => $canEnquiries ? (int) $settings['allow_public_enquiries'] : 0,
                'allow_public_orders' => $canOrders ? (int) $settings['allow_public_orders'] : 0,
            ]),
            'categories' => $canCategories ? $categoriesStmt->fetchAll() : [],
            'featuredListings' => ($canListings && isset($featureAccess['featured_listings'])) ? $featuredStmt->fetchAll() : [],
            'listings' => $canListings ? $listingsStmt->fetchAll() : [],
            'offers' => $canOffers ? $offersStmt->fetchAll() : [],
            'currentCategory' => $categorySlug,
            'featureAccess' => $featureAccess,
            'websiteAccess' => $access,
            'preview' => $previewAllowed,
            'noindex' => $preview || !$access['indexing_eligible'],
            'canonicalUrl' => url('/p/' . $business['slug']),
            'customerPrefill' => $customer ? [
                'name' => $customer['name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'],
            ] : null,
        ], 'layouts/website');
    }

    public function listing(string $slug, string $listingSlug): void
    {
        $business = $this->businessBySlug($slug);
        $businessId = (int) $business['id'];
        $settings = WebsiteAccessService::settingsFor($businessId);
        $subscription = SubscriptionService::current($businessId);
        $featureAccess = FeatureService::featuresForBusiness($businessId, $business, $subscription);
        $access = WebsiteAccessService::evaluate($business, $subscription, $settings, $featureAccess);

        $previewAllowed = $this->previewRequested()
            && $this->isPreviewViewer($businessId)
            && ($access['preview_available'] || \App\Core\Auth::isSuperAdmin());

        if ((!$access['public_access'] && !$previewAllowed) || (!isset($featureAccess['product_management']) && !isset($featureAccess['service_management']))) {
            throw new HttpException(404);
        }

        $settings = $access['settings'];
        if (empty($featureAccess['enquiries'])) {
            $settings['allow_public_enquiries'] = 0;
        }
        if (empty($featureAccess['orders'])) {
            $settings['allow_public_orders'] = 0;
        }
        $typeWhere = '';
        if (isset($featureAccess['product_management']) && !isset($featureAccess['service_management'])) {
            $typeWhere = ' AND type = "product"';
        } elseif (!isset($featureAccess['product_management']) && isset($featureAccess['service_management'])) {
            $typeWhere = ' AND type <> "product"';
        }

        $stmt = Database::pdo()->prepare(
            'SELECT l.*, c.name AS category_name
             FROM listings l
             LEFT JOIN categories c ON c.id = l.category_id AND c.business_id = l.business_id
             WHERE l.business_id = ? AND l.slug = ? AND l.status = "active" AND l.visible_on_website = 1
             LIMIT 1'
        );
        $stmt->execute([$businessId, $listingSlug]);
        $listing = $stmt->fetch();

        if (!$listing) {
            throw new HttpException(404);
        }
        if ($listing['type'] === 'product' && empty($featureAccess['product_management'])) {
            throw new HttpException(404);
        }
        if ($listing['type'] !== 'product' && empty($featureAccess['service_management'])) {
            throw new HttpException(404);
        }

        $relatedStmt = Database::pdo()->prepare(
            'SELECT id, title, slug, price, price_label, image_path, type, short_description
             FROM listings
             WHERE business_id = ? AND status = "active" AND visible_on_website = 1 AND id <> ?' . $typeWhere . '
             ORDER BY is_featured DESC, created_at DESC
             LIMIT 4'
        );
        $relatedStmt->execute([$businessId, (int) $listing['id']]);
        $customer = CustomerAuth::customer();

        $this->render('public.listing', [
            'pageTitle' => $listing['title'] . ' · ' . ($settings['seo_title'] ?: $business['name']),
            'seoDescription' => text_excerpt($listing['short_description'] ?: (string) $listing['description'], 160),
            'business' => $business,
            'settings' => $settings,
            'listing' => $listing,
            'relatedListings' => $relatedStmt->fetchAll(PDO::FETCH_ASSOC),
            'featureAccess' => $featureAccess,
            'websiteAccess' => $access,
            'preview' => $previewAllowed,
            'noindex' => $previewAllowed || !$access['indexing_eligible'],
            'canonicalUrl' => $previewAllowed ? null : url('/p/' . $business['slug'] . '/listing/' . $listing['slug']),
            'customerPrefill' => $customer ? [
                'name' => $customer['name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'],
            ] : null,
        ], 'layouts/website');
    }

    // ---- SEO endpoints --------------------------------------------------------

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $base = rtrim(url(''), '/');
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /business',
            'Disallow: /customer',
            'Disallow: /login',
            'Disallow: /setup',
            'Disallow: /p/*?preview',
            '',
            'Sitemap: ' . $base . '/sitemap.xml',
            '',
        ];
        echo implode("\n", $lines);
        exit;
    }

    public function sitemap(): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query(
            'SELECT b.slug, COALESCE(w.website_published_at, b.updated_at) AS lastmod
             FROM businesses b
             LEFT JOIN business_settings w ON w.business_id = b.id
             WHERE ' . WebsiteAccessService::sitemapWhere() . '
             ORDER BY b.id ASC'
        );
        $businesses = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $base = rtrim(url(''), '/');
        $siteCount = 0;
        foreach ($businesses as $row) {
            $loc = $base . '/p/' . rawurlencode((string) $row['slug']);
            $lastmod = $row['lastmod'] ? substr((string) $row['lastmod'], 0, 10) : date('Y-m-d');
            echo "  <url><loc>" . e($loc) . "</loc><lastmod>" . e($lastmod) . "</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
            $siteCount++;
            if ($siteCount >= 2000) {
                break; // keep sitemap responses bounded
            }
        }
        echo '</urlset>';
        exit;
    }

    // ---- Public enquiry / order intake ---------------------------------------

    public function submitEnquiry(string $slug): void
    {
        $this->verifyCsrf();
        $business = $this->businessBySlug($slug);
        $businessId = (int) $business['id'];
        $settings = WebsiteAccessService::settingsFor($businessId);
        $subscription = SubscriptionService::current($businessId);
        $featureAccess = FeatureService::featuresForBusiness($businessId, $business, $subscription);
        $access = WebsiteAccessService::evaluate($business, $subscription, $settings, $featureAccess);

        if (!$access['public_access'] || empty($featureAccess['enquiries']) || empty($access['settings']['allow_public_enquiries'])) {
            throw new HttpException(403, 'This website is not accepting enquiries right now.');
        }

        $validator = (new Validator())
            ->required('name', 'Name')
            ->max('name', 'Name', 190)
            ->required('phone', 'Phone')
            ->max('phone', 'Phone', 40)
            ->required('message', 'Message')
            ->max('message', 'Message', 4000)
            ->email('email', 'Email');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/p/' . $business['slug']);
        }

        $listing = $this->tenantListing($businessId, (int) ($_POST['listing_id'] ?? 0), $featureAccess);
        $requestedItem = $listing['title'] ?? (trim((string) ($_POST['requested_item'] ?? '')) ?: null);

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        $customerId = $this->findOrCreateCustomer($businessId);
        $accountId = CustomerAuth::id();

        $stmt = $pdo->prepare(
            'INSERT INTO enquiries
             (business_id, customer_id, customer_account_id, listing_id, name, phone, email, message, requested_item, status, source, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "new", "public_portal", NOW(), NOW())'
        );
        $stmt->execute([
            $businessId,
            $customerId,
            $accountId,
            $listing['id'] ?? null,
            trim((string) $_POST['name']),
            trim((string) $_POST['phone']),
            trim((string) ($_POST['email'] ?? '')) ?: null,
            trim((string) $_POST['message']),
            $requestedItem,
        ]);
        $enquiryId = (int) $pdo->lastInsertId();

        $history = $pdo->prepare('INSERT INTO enquiry_status_history (enquiry_id, business_id, old_status, new_status, note, changed_by, created_at) VALUES (?, ?, NULL, "new", "Created from public website", NULL, NOW())');
        $history->execute([$enquiryId, $businessId]);
        $pdo->commit();

        NotificationService::create('business', 'new_enquiry', 'New website enquiry received', 'A customer submitted an enquiry through your public website.', $businessId, null, 'enquiry', $enquiryId);
        NotificationService::create('super_admin', 'new_enquiry', 'New enquiry on ' . $business['name'], 'A public website enquiry was submitted.', $businessId, null, 'enquiry', $enquiryId);
        if ($accountId) {
            NotificationService::create('customer', 'enquiry_received', 'We received your enquiry', 'Your enquiry for ' . $business['name'] . ' was submitted and the business has been notified.', $businessId, null, 'enquiry', $enquiryId, $accountId);
        }
        ActivityLogger::log('public_website_enquiry_created', 'enquiry', $enquiryId, $businessId);

        Flash::success('Thank you. Your enquiry has been submitted.' . ($accountId ? ' You can track it under "My Enquiries".' : ''));
        $this->redirect('/p/' . $business['slug']);
    }

    public function submitOrder(string $slug): void
    {
        $this->verifyCsrf();
        $business = $this->businessBySlug($slug);
        $businessId = (int) $business['id'];
        $settings = WebsiteAccessService::settingsFor($businessId);
        $subscription = SubscriptionService::current($businessId);
        $featureAccess = FeatureService::featuresForBusiness($businessId, $business, $subscription);
        $access = WebsiteAccessService::evaluate($business, $subscription, $settings, $featureAccess);

        if (!$access['public_access'] || empty($featureAccess['orders']) || empty($access['settings']['allow_public_orders'])) {
            throw new HttpException(403, 'This website is not accepting requests right now.');
        }

        $validator = (new Validator())
            ->required('name', 'Name')
            ->required('phone', 'Phone')
            ->required('details', 'Request details')
            ->email('email', 'Email')
            ->in('request_type', 'Request type', ['product_order', 'service_request', 'booking_request', 'package_enquiry', 'custom_request']);

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/p/' . $business['slug']);
        }

        $requestType = (string) ($_POST['request_type'] ?? 'custom_request');
        $this->guardPublicRequestType($requestType, $featureAccess);

        $listing = $this->tenantListing($businessId, (int) ($_POST['listing_id'] ?? 0), $featureAccess);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $total = null;
        if ($listing && $listing['price'] !== null && $listing['price'] !== '') {
            $total = (float) $listing['price'] * $quantity;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        $customerId = $this->findOrCreateCustomer($businessId);
        $orderNumber = $this->generateOrderNumber($businessId);
        $accountId = CustomerAuth::id();

        $stmt = $pdo->prepare(
            'INSERT INTO orders
             (business_id, customer_id, customer_account_id, listing_id, order_number, request_type, customer_name, phone, email, details, quantity, total_amount, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "new", NOW(), NOW())'
        );
        $stmt->execute([
            $businessId,
            $customerId,
            $accountId,
            $listing['id'] ?? null,
            $orderNumber,
            $requestType,
            trim((string) $_POST['name']),
            trim((string) $_POST['phone']),
            trim((string) ($_POST['email'] ?? '')) ?: null,
            trim((string) $_POST['details']),
            $quantity,
            $total,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $history = $pdo->prepare('INSERT INTO order_status_history (order_id, business_id, old_status, new_status, note, changed_by, created_at) VALUES (?, ?, NULL, "new", "Created from public website", NULL, NOW())');
        $history->execute([$orderId, $businessId]);
        $pdo->commit();

        NotificationService::create('business', 'new_order', 'New website order/request received', 'A customer submitted an order or request through your public website.', $businessId, null, 'order', $orderId);
        NotificationService::create('super_admin', 'new_order', 'New request on ' . $business['name'], 'A public website order/request was submitted.', $businessId, null, 'order', $orderId);
        if ($accountId) {
            NotificationService::create('customer', 'request_received', 'We received your request', 'Your request ' . $orderNumber . ' for ' . $business['name'] . ' is with the business.', $businessId, null, 'order', $orderId, $accountId);
        }
        ActivityLogger::log('public_website_order_created', 'order', $orderId, $businessId);

        Flash::success('Your request has been submitted. Reference: ' . $orderNumber);
        $this->redirect('/p/' . $business['slug']);
    }

    // ---- Internals -------------------------------------------------------------

    private function isPreviewViewer(int $businessId): bool
    {
        if (\App\Core\Auth::isSuperAdmin()) {
            return true;
        }
        if (\App\Core\Auth::isBusinessUser()) {
            return \App\Core\Auth::businessId() === $businessId;
        }
        return false;
    }

    private function businessBySlug(string $slug): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM businesses WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$business || in_array($business['status'], ['rejected', 'archived'], true)) {
            throw new HttpException(404);
        }
        return $business;
    }

    private function guardPublicRequestType(string $requestType, array $featureAccess): void
    {
        if ($requestType === 'product_order' && empty($featureAccess['product_management'])) {
            throw new HttpException(403, 'Product orders are not included in this business plan.');
        }
        if (in_array($requestType, ['service_request', 'package_enquiry', 'custom_request'], true) && empty($featureAccess['service_management'])) {
            throw new HttpException(403, 'Service requests are not included in this business plan.');
        }
        if ($requestType === 'service_request' && empty($featureAccess['service_requests'])) {
            throw new HttpException(403, 'Service request workflows are not included in this business plan.');
        }
        if ($requestType === 'booking_request' && (empty($featureAccess['service_management']) || empty($featureAccess['booking_requests']))) {
            throw new HttpException(403, 'Booking requests are not included in this business plan.');
        }
    }

    private function tenantListing(int $businessId, int $listingId, array $featureAccess = []): ?array
    {
        if ($listingId <= 0) {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM listings WHERE id = ? AND business_id = ? AND status = "active" AND visible_on_website = 1 LIMIT 1');
        $stmt->execute([$listingId, $businessId]);
        $listing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$listing) {
            throw new HttpException(403, 'Invalid listing for this business website.');
        }
        if (($listing['type'] ?? '') === 'product' && empty($featureAccess['product_management'])) {
            throw new HttpException(403, 'Product requests are not included in this business plan.');
        }
        if (($listing['type'] ?? '') !== 'product' && empty($featureAccess['service_management'])) {
            throw new HttpException(403, 'Service requests are not included in this business plan.');
        }
        return $listing;
    }

    private function findOrCreateCustomer(int $businessId): int
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if ($phone !== '') {
            $stmt = Database::pdo()->prepare('SELECT id FROM customers WHERE business_id = ? AND phone = ? LIMIT 1');
            $stmt->execute([$businessId, $phone]);
            $existing = $stmt->fetchColumn();
            if ($existing) {
                return (int) $existing;
            }
        }

        if ($email !== '') {
            $stmt = Database::pdo()->prepare('SELECT id FROM customers WHERE business_id = ? AND email = ? LIMIT 1');
            $stmt->execute([$businessId, $email]);
            $existing = $stmt->fetchColumn();
            if ($existing) {
                return (int) $existing;
            }
        }

        $stmt = Database::pdo()->prepare('INSERT INTO customers (business_id, name, phone, email, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$businessId, $name, $phone, $email ?: null]);
        return (int) Database::pdo()->lastInsertId();
    }

    private function generateOrderNumber(int $businessId): string
    {
        do {
            $number = 'REQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $stmt = Database::pdo()->prepare('SELECT id FROM orders WHERE business_id = ? AND order_number = ? LIMIT 1');
            $stmt->execute([$businessId, $number]);
        } while ($stmt->fetch());

        return $number;
    }
}
