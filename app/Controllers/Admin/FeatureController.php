<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Validator;
use App\Services\ActivityLogger;
use App\Services\FeatureService;
use PDO;
use Throwable;

class FeatureController extends BaseAdminController
{
    public function index(): void
    {
        $this->renderAdmin('admin.features.index', [
            'pageTitle' => 'Platform Features',
            'active' => 'features',
            'featureGroups' => FeatureService::groupedRegistry(false, false),
        ]);
        clear_old_input();
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $validator = (new Validator())
            ->required('name', 'Feature name')
            ->required('identifier', 'Internal identifier')
            ->required('category', 'Category');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/admin/features');
        }

        $identifier = $this->normalizeIdentifier((string) $_POST['identifier']);
        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO platform_features (identifier, name, description, category, icon, is_active, available_for_plans, sort_order, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                $identifier,
                trim((string) $_POST['name']),
                trim((string) ($_POST['description'] ?? '')) ?: null,
                trim((string) $_POST['category']),
                trim((string) ($_POST['icon'] ?? '')) ?: null,
                isset($_POST['is_active']) ? 1 : 0,
                isset($_POST['available_for_plans']) ? 1 : 0,
                (int) ($_POST['sort_order'] ?? 0),
            ]);
            $featureId = (int) Database::pdo()->lastInsertId();
        } catch (Throwable $exception) {
            app_log('Feature creation failed', ['message' => $exception->getMessage(), 'identifier' => $identifier]);
            Flash::error('Could not create feature. Please use a unique internal identifier and try again.');
            $_SESSION['_old'] = $_POST;
            $this->redirect('/admin/features');
        }
        FeatureService::clearCache();
        ActivityLogger::log('platform_feature_created', 'platform_feature', $featureId, null, ['identifier' => $identifier]);
        Flash::success('Platform feature registered. It is now available in plan selectors if enabled for plans.');
        $this->redirect('/admin/features');
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $feature = $this->feature((int) $id);
        $validator = (new Validator())
            ->required('name', 'Feature name')
            ->required('identifier', 'Internal identifier')
            ->required('category', 'Category');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/admin/features');
        }

        $identifier = $this->normalizeIdentifier((string) $_POST['identifier']);
        try {
            $stmt = Database::pdo()->prepare(
                'UPDATE platform_features
                 SET identifier = ?, name = ?, description = ?, category = ?, icon = ?, is_active = ?, available_for_plans = ?, sort_order = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([
                $identifier,
                trim((string) $_POST['name']),
                trim((string) ($_POST['description'] ?? '')) ?: null,
                trim((string) $_POST['category']),
                trim((string) ($_POST['icon'] ?? '')) ?: null,
                isset($_POST['is_active']) ? 1 : 0,
                isset($_POST['available_for_plans']) ? 1 : 0,
                (int) ($_POST['sort_order'] ?? 0),
                (int) $feature['id'],
            ]);
        } catch (Throwable $exception) {
            app_log('Feature update failed', ['message' => $exception->getMessage(), 'identifier' => $identifier]);
            Flash::error('Could not update feature. Please use a unique internal identifier and try again.');
            $_SESSION['_old'] = $_POST;
            $this->redirect('/admin/features');
        }
        FeatureService::clearCache();
        ActivityLogger::log('platform_feature_updated', 'platform_feature', (int) $feature['id'], null, ['identifier' => $identifier]);
        Flash::success('Feature updated.');
        $this->redirect('/admin/features');
    }

    private function feature(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM platform_features WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $feature = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$feature) {
            throw new HttpException(404, 'Feature not found.');
        }
        return $feature;
    }

    private function normalizeIdentifier(string $identifier): string
    {
        $identifier = strtolower(trim($identifier));
        $identifier = preg_replace('/[^a-z0-9_\.\-]+/', '_', $identifier) ?? '';
        $identifier = trim($identifier, '_.-');
        return $identifier !== '' ? $identifier : slugify((string) ($_POST['name'] ?? 'feature'));
    }
}
