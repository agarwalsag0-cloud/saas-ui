<?php

declare(strict_types=1);

namespace App\Controllers\Business;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\ActivityLogger;
use App\Services\UploadService;
use Throwable;

class ProfileController extends BaseBusinessController
{
    public function edit(): void
    {
        $this->requireFeature('business_profile', 'Business Profile');
        $stmt = Database::pdo()->prepare('SELECT * FROM business_settings WHERE business_id = ? LIMIT 1');
        $stmt->execute([$this->tenantId()]);

        $this->renderBusiness('business.profile', [
            'pageTitle' => 'Business Profile',
            'active' => 'profile',
            'settings' => $stmt->fetch() ?: [],
            'socialLinks' => json_decode($this->business['social_links'] ?: '{}', true) ?: [],
        ]);
        clear_old_input();
    }

    public function update(): void
    {
        $this->requireFeature('business_profile', 'Business Profile');
        $this->verifyCsrf();

        $validator = (new Validator())
            ->required('name', 'Business name')
            ->required('category', 'Category')
            ->max('tagline', 'Tagline', 255)
            ->email('email', 'Email')
            ->max('description', 'Description', 3000);

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/business/profile');
        }

        $businessId = $this->tenantId();
        try {
            $logoPath = UploadService::image('logo', 'uploads/businesses/' . $businessId . '/branding', $this->business['logo_path'] ?? null);
            $coverPath = UploadService::image('cover', 'uploads/businesses/' . $businessId . '/branding', $this->business['cover_path'] ?? null);
        } catch (Throwable $exception) {
            Flash::error($exception->getMessage());
            $this->redirect('/business/profile');
        }

        $social = [
            'facebook' => trim((string) ($_POST['facebook'] ?? '')),
            'instagram' => trim((string) ($_POST['instagram'] ?? '')),
            'whatsapp' => trim((string) ($_POST['whatsapp'] ?? '')),
            'youtube' => trim((string) ($_POST['youtube'] ?? '')),
            'linkedin' => trim((string) ($_POST['linkedin'] ?? '')),
        ];
        $social = array_filter($social, fn($value) => $value !== '');

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'UPDATE businesses
             SET name = ?, category = ?, tagline = ?, description = ?, phone = ?, email = ?, address = ?, city = ?, state = ?, country = ?, website = ?, social_links = ?, timings = ?, logo_path = ?, cover_path = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            trim((string) $_POST['name']),
            trim((string) $_POST['category']),
            trim((string) ($_POST['tagline'] ?? '')) ?: null,
            trim((string) ($_POST['description'] ?? '')) ?: null,
            trim((string) ($_POST['phone'] ?? '')) ?: null,
            mb_strtolower(trim((string) ($_POST['email'] ?? ''))) ?: null,
            trim((string) ($_POST['address'] ?? '')) ?: null,
            trim((string) ($_POST['city'] ?? '')) ?: null,
            trim((string) ($_POST['state'] ?? '')) ?: null,
            trim((string) ($_POST['country'] ?? 'India')) ?: null,
            trim((string) ($_POST['website'] ?? '')) ?: null,
            json_encode($social, JSON_UNESCAPED_SLASHES),
            trim((string) ($_POST['timings'] ?? '')) ?: null,
            $logoPath,
            $coverPath,
            $businessId,
        ]);

        $themeColor = valid_hex_color($_POST['theme_color'] ?? '', '#2563eb');
        $accentColor = valid_hex_color($_POST['accent_color'] ?? '', '#f97316');
        $settingsStmt = $pdo->prepare(
            'INSERT INTO business_settings (business_id, primary_color, theme_color, accent_color, portal_settings, created_at, updated_at)
             VALUES (?, ?, ?, ?, NULL, NOW(), NOW())
             ON DUPLICATE KEY UPDATE primary_color = VALUES(primary_color), theme_color = VALUES(theme_color), accent_color = VALUES(accent_color), updated_at = NOW()'
        );
        $settingsStmt->execute([
            $businessId,
            $themeColor,
            $themeColor,
            $accentColor,
        ]);

        ActivityLogger::log('business_profile_updated', 'business', $businessId, $businessId);
        Flash::success('Profile updated.');
        $this->redirect('/business/profile');
    }
}
