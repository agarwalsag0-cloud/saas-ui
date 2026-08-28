<?php

declare(strict_types=1);

namespace App\Controllers\Business;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\ActivityLogger;
use App\Services\UploadService;
use Throwable;

class WebsiteController extends BaseBusinessController
{
    public const PRESETS = [
        'modern' => [
            'label' => 'Modern',
            'primary_color' => '#2563eb',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f97316',
            'background_style' => 'gradient',
            'button_style' => 'pill',
            'text_style' => 'modern',
            'layout_style' => 'showcase',
        ],
        'elegant' => [
            'label' => 'Elegant',
            'primary_color' => '#7c2d12',
            'secondary_color' => '#1c1917',
            'accent_color' => '#d97706',
            'background_style' => 'soft',
            'button_style' => 'pill',
            'text_style' => 'serif',
            'layout_style' => 'classic',
        ],
        'minimal' => [
            'label' => 'Minimal',
            'primary_color' => '#111827',
            'secondary_color' => '#f8fafc',
            'accent_color' => '#64748b',
            'background_style' => 'light',
            'button_style' => 'rounded',
            'text_style' => 'system',
            'layout_style' => 'compact',
        ],
        'professional' => [
            'label' => 'Professional',
            'primary_color' => '#0f766e',
            'secondary_color' => '#0f172a',
            'accent_color' => '#14b8a6',
            'background_style' => 'soft',
            'button_style' => 'rounded',
            'text_style' => 'system',
            'layout_style' => 'classic',
        ],
        'bold' => [
            'label' => 'Bold',
            'primary_color' => '#7c3aed',
            'secondary_color' => '#111827',
            'accent_color' => '#06b6d4',
            'background_style' => 'gradient',
            'button_style' => 'pill',
            'text_style' => 'modern',
            'layout_style' => 'showcase',
        ],
    ];

    public function edit(): void
    {
        $this->requireFeature('public_website', 'Public Business Website');
        $settings = $this->settings();
        $access = \App\Services\WebsiteAccessService::evaluate($this->business, $this->subscription, $this->rawSettings());

        $this->renderBusiness('business.website_settings', [
            'pageTitle' => 'Website Settings',
            'active' => 'website',
            'settings' => $settings,
            'presets' => self::PRESETS,
            'socialLinks' => json_decode($this->business['social_links'] ?: '{}', true) ?: [],
            'sectionVisibility' => $this->sectionVisibility($settings),
            'websitePath' => url('/p/' . $this->business['slug']),
            'websiteAccess' => $access,
        ]);
        clear_old_input();
    }

    public function preview(): void
    {
        $this->requireFeature('public_website', 'Public Business Website');
        (new \App\Controllers\PublicPortalController())->renderSite((string) $this->business['slug'], true);
    }

    public function publish(): void
    {
        $this->requireFeature('public_website', 'Public Business Website');
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $businessId = $this->tenantId();

        $access = \App\Services\WebsiteAccessService::evaluate($this->business, $this->subscription, $this->rawSettings());
        if (!$access['portal_usable']) {
            Flash::error('Your subscription or business status must be active before publishing.');
            $this->redirect('/business/website');
        }

        $stmt = Database::pdo()->prepare(
            'UPDATE business_settings SET website_published = 1, website_published_at = NOW(), updated_at = NOW() WHERE business_id = ?'
        );
        $stmt->execute([$businessId]);

        \App\Services\ActivityLogger::log('website_published', 'business', $businessId, $businessId);
        \App\Services\NotificationService::create('business', 'website_published', 'Website published', 'Your public business website is now live.', $businessId, null, 'business', $businessId);
        Flash::success('Website published. It is now available at its public URL.');
        $this->redirect('/business/website');
    }

    public function unpublish(): void
    {
        $this->requireFeature('public_website', 'Public Business Website');
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $businessId = $this->tenantId();

        $stmt = Database::pdo()->prepare(
            'UPDATE business_settings SET website_published = 0, updated_at = NOW() WHERE business_id = ?'
        );
        $stmt->execute([$businessId]);

        \App\Services\ActivityLogger::log('website_unpublished', 'business', $businessId, $businessId);
        Flash::warning('Website unpublished. Visitors can no longer open it; your content and settings are preserved.');
        $this->redirect('/business/website');
    }

    public function submitReview(): void
    {
        $this->guardWriteAccess();
        $this->verifyCsrf();
        $businessId = $this->tenantId();

        if (!in_array((string) $this->business['status'], ['pending', 'changes_requested'], true)) {
            Flash::info('Your business is already submitted or approved for review.');
            $this->redirect('/business');
        }

        $stmt = Database::pdo()->prepare(
            'UPDATE businesses SET status = "under_review", submitted_for_review_at = NOW(), review_note = NULL, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$businessId]);

        \App\Services\NotificationService::create(
            'super_admin',
            'business_submitted_for_review',
            'Business submitted for review',
            $this->business['name'] . ' submitted their profile and content for review.',
            $businessId,
            null,
            'business',
            $businessId
        );
        \App\Services\ActivityLogger::log('business_submitted_for_review', 'business', $businessId, $businessId);
        Flash::success('Submitted for review. The platform team will review your business and contact you.');
        $this->redirect('/business');
    }

    private function rawSettings(): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM business_settings WHERE business_id = ? LIMIT 1');
        $stmt->execute([$this->tenantId()]);
        return $stmt->fetch() ?: null;
    }

    public function update(): void
    {
        $this->requireFeature('public_website', 'Public Business Website');
        $this->guardWriteAccess();
        $this->verifyCsrf();

        $canWebsiteCustomization = $this->hasFeature('website_customization');
        $validator = (new Validator())
            ->required('name', 'Business name')
            ->required('category', 'Business category')
            ->max('tagline', 'Tagline', 255)
            ->max('description', 'About section', 3000)
            ->email('email', 'Email');

        if ($canWebsiteCustomization) {
            $validator
                ->in('theme_preset', 'Theme preset', array_keys(self::PRESETS))
                ->in('layout_style', 'Website layout', ['classic', 'showcase', 'compact'])
                ->in('background_style', 'Background style', ['light', 'soft', 'gradient', 'dark'])
                ->in('button_style', 'Button style', ['rounded', 'pill', 'square'])
                ->in('text_style', 'Text style', ['system', 'serif', 'modern']);
        }

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/business/website');
        }

        $businessId = $this->tenantId();
        $currentSettings = $this->settings();
        $canCustomBranding = $this->hasFeature('custom_branding');
        $canSeo = $this->hasFeature('basic_seo');
        $logoPath = $this->business['logo_path'] ?? null;
        $coverPath = $this->business['cover_path'] ?? null;
        try {
            if ($canCustomBranding) {
                $logoPath = UploadService::image('logo', 'uploads/businesses/' . $businessId . '/branding', $this->business['logo_path'] ?? null);
                $coverPath = UploadService::image('cover', 'uploads/businesses/' . $businessId . '/branding', $this->business['cover_path'] ?? null);
            }
        } catch (Throwable $exception) {
            Flash::error($exception->getMessage());
            $_SESSION['_old'] = $_POST;
            $this->redirect('/business/website');
        }

        $social = [
            'facebook' => trim((string) ($_POST['facebook'] ?? '')),
            'instagram' => trim((string) ($_POST['instagram'] ?? '')),
            'whatsapp' => trim((string) ($_POST['whatsapp'] ?? '')),
            'youtube' => trim((string) ($_POST['youtube'] ?? '')),
            'linkedin' => trim((string) ($_POST['linkedin'] ?? '')),
        ];
        $social = array_filter($social, fn($value) => $value !== '');

        $sections = ['about', 'categories', 'featured', 'listings', 'offers', 'contact', 'location', 'social_links'];
        $currentVisibility = $this->sectionVisibility($currentSettings);
        $visibility = [];
        foreach ($sections as $section) {
            $lockedByPlan =
                ($section === 'categories' && !$this->hasFeature('categories')) ||
                ($section === 'featured' && !$this->hasFeature('featured_listings')) ||
                ($section === 'listings' && !$this->hasFeature('product_management') && !$this->hasFeature('service_management')) ||
                ($section === 'offers' && !$this->hasFeature('offers'));

            // Preserve saved configuration for sections locked by the current plan.
            // Public rendering still hides them until the feature is included again.
            $visibility[$section] = $lockedByPlan ? (bool) ($currentVisibility[$section] ?? true) : isset($_POST['sections'][$section]);
        }

        $allowPublicEnquiries = $this->hasFeature('enquiries')
            ? (isset($_POST['allow_public_enquiries']) ? 1 : 0)
            : (int) ($currentSettings['allow_public_enquiries'] ?? 1);
        $allowPublicOrders = $this->hasFeature('orders')
            ? (isset($_POST['allow_public_orders']) ? 1 : 0)
            : (int) ($currentSettings['allow_public_orders'] ?? 1);

        $themePreset = $canWebsiteCustomization ? (string) ($_POST['theme_preset'] ?? ($currentSettings['theme_preset'] ?? 'modern')) : (string) ($currentSettings['theme_preset'] ?? 'modern');
        $layoutStyle = $canWebsiteCustomization ? (string) ($_POST['layout_style'] ?? ($currentSettings['layout_style'] ?? 'classic')) : (string) ($currentSettings['layout_style'] ?? 'classic');
        $backgroundStyle = $canWebsiteCustomization ? (string) ($_POST['background_style'] ?? ($currentSettings['background_style'] ?? 'light')) : (string) ($currentSettings['background_style'] ?? 'light');
        $buttonStyle = $canWebsiteCustomization ? (string) ($_POST['button_style'] ?? ($currentSettings['button_style'] ?? 'rounded')) : (string) ($currentSettings['button_style'] ?? 'rounded');
        $textStyle = $canWebsiteCustomization ? (string) ($_POST['text_style'] ?? ($currentSettings['text_style'] ?? 'system')) : (string) ($currentSettings['text_style'] ?? 'system');
        $primary = $canWebsiteCustomization ? valid_hex_color($_POST['primary_color'] ?? '', self::PRESETS['modern']['primary_color']) : valid_hex_color($currentSettings['primary_color'] ?? ($currentSettings['theme_color'] ?? ''), self::PRESETS['modern']['primary_color']);
        $secondary = $canWebsiteCustomization ? valid_hex_color($_POST['secondary_color'] ?? '', self::PRESETS['modern']['secondary_color']) : valid_hex_color($currentSettings['secondary_color'] ?? '', self::PRESETS['modern']['secondary_color']);
        $accent = $canWebsiteCustomization ? valid_hex_color($_POST['accent_color'] ?? '', self::PRESETS['modern']['accent_color']) : valid_hex_color($currentSettings['accent_color'] ?? '', self::PRESETS['modern']['accent_color']);

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $businessStmt = $pdo->prepare(
                'UPDATE businesses
                 SET name = ?, category = ?, tagline = ?, description = ?, phone = ?, email = ?, address = ?, city = ?, state = ?, country = ?, website = ?, social_links = ?, timings = ?, logo_path = ?, cover_path = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $businessStmt->execute([
                trim((string) $_POST['name']),
                trim((string) $_POST['category']),
                trim((string) ($_POST['tagline'] ?? '')) ?: null,
                trim((string) ($_POST['description'] ?? '')) ?: null,
                trim((string) ($_POST['phone'] ?? '')) ?: null,
                strtolower(trim((string) ($_POST['email'] ?? ''))) ?: null,
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

            $settingsStmt = $pdo->prepare(
                'INSERT INTO business_settings
                 (business_id, show_in_directory, allow_indexing, allow_public_enquiries, allow_public_orders, theme_preset, layout_style, background_style, button_style, text_style, primary_color, secondary_color, theme_color, accent_color, section_visibility, seo_title, seo_description, portal_settings, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    show_in_directory = VALUES(show_in_directory),
                    allow_indexing = VALUES(allow_indexing),
                    allow_public_enquiries = VALUES(allow_public_enquiries),
                    allow_public_orders = VALUES(allow_public_orders),
                    theme_preset = VALUES(theme_preset),
                    layout_style = VALUES(layout_style),
                    background_style = VALUES(background_style),
                    button_style = VALUES(button_style),
                    text_style = VALUES(text_style),
                    primary_color = VALUES(primary_color),
                    secondary_color = VALUES(secondary_color),
                    theme_color = VALUES(theme_color),
                    accent_color = VALUES(accent_color),
                    section_visibility = VALUES(section_visibility),
                    seo_title = VALUES(seo_title),
                    seo_description = VALUES(seo_description),
                    updated_at = NOW()'
            );
            $canSeoForIndexing = $this->hasFeature('basic_seo');
            $allowIndexing = $canSeoForIndexing
                ? (isset($_POST['allow_indexing']) ? 1 : 0)
                : (int) ($currentSettings['allow_indexing'] ?? 1);

            $settingsStmt->execute([
                $businessId,
                isset($_POST['show_in_directory']) ? 1 : 0,
                $allowIndexing,
                $allowPublicEnquiries,
                $allowPublicOrders,
                $themePreset,
                $layoutStyle,
                $backgroundStyle,
                $buttonStyle,
                $textStyle,
                $primary,
                $secondary,
                $primary,
                $accent,
                json_encode($visibility, JSON_UNESCAPED_SLASHES),
                $canSeo ? (trim((string) ($_POST['seo_title'] ?? '')) ?: null) : ($currentSettings['seo_title'] ?? null),
                $canSeo ? (trim((string) ($_POST['seo_description'] ?? '')) ?: null) : ($currentSettings['seo_description'] ?? null),
            ]);

            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            app_log('Website settings update failed', ['message' => $exception->getMessage()]);
            Flash::error('Could not save website settings. Please try again.');
            $_SESSION['_old'] = $_POST;
            $this->redirect('/business/website');
        }

        ActivityLogger::log('website_settings_updated', 'business', $businessId, $businessId);
        Flash::success('Website settings saved. Your public website updated automatically.');
        $this->redirect('/business/website');
    }

    private function settings(): array
    {
        $settings = $this->rawSettings() ?? [];
        return array_merge([
            'website_enabled' => 1,
            'website_published' => 0,
            'website_published_at' => null,
            'allow_indexing' => 1,
            'show_in_directory' => 1,
            'allow_public_enquiries' => 1,
            'allow_public_orders' => 1,
            'theme_preset' => 'modern',
            'layout_style' => 'classic',
            'background_style' => 'light',
            'button_style' => 'rounded',
            'text_style' => 'system',
            'primary_color' => $settings['theme_color'] ?? '#2563eb',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f97316',
            'section_visibility' => null,
            'seo_title' => null,
            'seo_description' => null,
        ], $settings);
    }

    private function sectionVisibility(array $settings): array
    {
        $defaults = [
            'about' => true,
            'categories' => true,
            'featured' => true,
            'listings' => true,
            'offers' => true,
            'contact' => true,
            'location' => true,
            'social_links' => true,
        ];
        $stored = json_decode((string) ($settings['section_visibility'] ?? ''), true);
        return is_array($stored) ? array_merge($defaults, $stored) : $defaults;
    }
}
