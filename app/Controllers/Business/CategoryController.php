<?php

declare(strict_types=1);

namespace App\Controllers\Business;

use App\Core\Database;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Services\ActivityLogger;
use App\Services\SlugService;
use PDO;

class CategoryController extends BaseBusinessController
{
    public function index(): void
    {
        $this->requireFeature('categories', 'Categories');
        $stmt = Database::pdo()->prepare(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM listings l WHERE l.category_id = c.id AND l.business_id = c.business_id AND l.status <> "archived") AS listing_count
             FROM categories c
             WHERE c.business_id = ?
             ORDER BY c.sort_order ASC, c.name ASC'
        );
        $stmt->execute([$this->tenantId()]);

        $this->renderBusiness('business.categories', [
            'pageTitle' => 'Categories',
            'active' => 'categories',
            'categories' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);
        clear_old_input();
    }

    public function store(): void
    {
        $this->requireFeature('categories', 'Categories');
        $this->guardWriteAccess();
        $this->verifyCsrf();

        $validator = (new Validator())->required('name', 'Category name');
        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/business/categories');
        }

        $businessId = $this->tenantId();
        $slug = SlugService::unique('categories', (trim((string) ($_POST['slug'] ?? '')) !== '' ? (string) $_POST['slug'] : (string) $_POST['name']), $businessId);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO categories (business_id, parent_id, name, slug, description, is_active, visible_on_website, sort_order, created_at, updated_at)
             VALUES (?, NULL, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            $businessId,
            trim((string) $_POST['name']),
            $slug,
            trim((string) ($_POST['description'] ?? '')) ?: null,
            isset($_POST['is_active']) ? 1 : 0,
            $this->hasFeature('public_website') ? (isset($_POST['visible_on_website']) ? 1 : 0) : 1,
            (int) ($_POST['sort_order'] ?? 0),
        ]);
        $categoryId = (int) Database::pdo()->lastInsertId();

        ActivityLogger::log('category_created', 'category', $categoryId, $businessId);
        Flash::success('Category created.');
        $this->redirect('/business/categories');
    }

    public function update(string $id): void
    {
        $this->requireFeature('categories', 'Categories');
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $category = $this->category((int) $id);

        $validator = (new Validator())->required('name', 'Category name');
        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/business/categories');
        }

        $businessId = $this->tenantId();
        $slug = SlugService::unique('categories', (trim((string) ($_POST['slug'] ?? '')) !== '' ? (string) $_POST['slug'] : (string) $_POST['name']), $businessId, (int) $category['id']);
        $stmt = Database::pdo()->prepare(
            'UPDATE categories
             SET name = ?, slug = ?, description = ?, is_active = ?, visible_on_website = ?, sort_order = ?, updated_at = NOW()
             WHERE id = ? AND business_id = ?'
        );
        $stmt->execute([
            trim((string) $_POST['name']),
            $slug,
            trim((string) ($_POST['description'] ?? '')) ?: null,
            isset($_POST['is_active']) ? 1 : 0,
            $this->hasFeature('public_website') ? (isset($_POST['visible_on_website']) ? 1 : 0) : (int) ($category['visible_on_website'] ?? 1),
            (int) ($_POST['sort_order'] ?? 0),
            (int) $category['id'], 
            $businessId,
        ]);

        ActivityLogger::log('category_updated', 'category', (int) $category['id'], $businessId);
        Flash::success('Category updated.');
        $this->redirect('/business/categories');
    }

    public function delete(string $id): void
    {
        $this->requireFeature('categories', 'Categories');
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $category = $this->category((int) $id);
        $businessId = $this->tenantId();

        $countStmt = Database::pdo()->prepare('SELECT COUNT(*) FROM listings WHERE business_id = ? AND category_id = ? AND status <> "archived"');
        $countStmt->execute([$businessId, (int) $category['id']]);
        if ((int) $countStmt->fetchColumn() > 0) {
            $stmt = Database::pdo()->prepare('UPDATE categories SET is_active = 0, updated_at = NOW() WHERE id = ? AND business_id = ?');
            $stmt->execute([(int) $category['id'], $businessId]);
            Flash::warning('Category has listings, so it was deactivated instead of deleted.');
        } else {
            $stmt = Database::pdo()->prepare('DELETE FROM categories WHERE id = ? AND business_id = ?');
            $stmt->execute([(int) $category['id'], $businessId]);
            Flash::success('Category deleted.');
        }

        ActivityLogger::log('category_deleted_or_deactivated', 'category', (int) $category['id'], $businessId);
        $this->redirect('/business/categories');
    }

    private function category(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM categories WHERE id = ? AND business_id = ? LIMIT 1');
        $stmt->execute([$id, $this->tenantId()]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$category) {
            throw new HttpException(404, 'Category not found for this business.');
        }
        return $category;
    }
}
