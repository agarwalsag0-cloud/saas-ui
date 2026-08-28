<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\BusinessRegistrationController;
use App\Controllers\PublicPortalController;
use App\Controllers\SetupController;
use App\Controllers\Admin\ActivityController as AdminActivityController;
use App\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\FeatureController as AdminFeatureController;
use App\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Controllers\Admin\PlanController as AdminPlanController;
use App\Controllers\Customer\CustomerAuthController;
use App\Controllers\Customer\CustomerController;
use App\Controllers\Business\CategoryController;
use App\Controllers\Business\DashboardController as BusinessDashboardController;
use App\Controllers\Business\EnquiryController;
use App\Controllers\Business\ListingController;
use App\Controllers\Business\NotificationController as BusinessNotificationController;
use App\Controllers\Business\OfferController;
use App\Controllers\Business\OrderController;
use App\Controllers\Business\ProfileController;
use App\Controllers\Business\SubscriptionController;
use App\Controllers\Business\WebsiteController;
use App\Core\HttpException;
use App\Core\Router;
use App\Core\View;

$router = new Router();

// Public, setup and auth routes
$router->get('/', [PublicPortalController::class, 'landing']);
$router->get('/setup', [SetupController::class, 'show']);
$router->post('/setup', [SetupController::class, 'store']);
$router->get('/register-business', [BusinessRegistrationController::class, 'show']);
$router->post('/register-business', [BusinessRegistrationController::class, 'store']);
$router->get('/login', [AuthController::class, 'showChoice']);
$router->get('/business/login', [AuthController::class, 'showBusinessLogin']);
$router->post('/business/login', [AuthController::class, 'businessLogin']);
$router->get('/admin/login', [AuthController::class, 'showAdminLogin']);
$router->post('/admin/login', [AuthController::class, 'adminLogin']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/robots.txt', [PublicPortalController::class, 'robots']);
$router->get('/sitemap.xml', [PublicPortalController::class, 'sitemap']);
$router->get('/p/{slug}', [PublicPortalController::class, 'portal']);
$router->get('/p/{slug}/listing/{listingSlug}', [PublicPortalController::class, 'listing']);
$router->post('/p/{slug}/enquiry', [PublicPortalController::class, 'submitEnquiry']);
$router->post('/p/{slug}/order', [PublicPortalController::class, 'submitOrder']);

// Customer account routes (self-service, no Super Admin approval needed)
$router->get('/customer/register', [CustomerAuthController::class, 'showRegister']);
$router->post('/customer/register', [CustomerAuthController::class, 'register']);
$router->get('/customer/login', [CustomerAuthController::class, 'showLogin']);
$router->post('/customer/login', [CustomerAuthController::class, 'login']);
$router->post('/customer/logout', [CustomerAuthController::class, 'logout']);
$router->get('/auth/google/redirect', [CustomerAuthController::class, 'googleRedirect']);
$router->get('/auth/google/callback', [CustomerAuthController::class, 'googleCallback']);
$router->get('/customer', [CustomerController::class, 'dashboard']);
$router->get('/customer/profile', [CustomerController::class, 'profile']);
$router->post('/customer/profile', [CustomerController::class, 'updateProfile']);
$router->post('/customer/password', [CustomerController::class, 'changePassword']);
$router->post('/customer/password/set-initial', [CustomerController::class, 'setInitialPassword']);
$router->get('/customer/enquiries', [CustomerController::class, 'enquiries']);
$router->get('/customer/enquiries/{id}', [CustomerController::class, 'enquiryDetail']);
$router->get('/customer/orders', [CustomerController::class, 'orders']);
$router->get('/customer/orders/{id}', [CustomerController::class, 'orderDetail']);
$router->get('/customer/notifications', [CustomerController::class, 'notifications']);
$router->post('/customer/notifications', [CustomerController::class, 'notifications']);

// Super Admin routes
$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/businesses', [AdminBusinessController::class, 'index']);
$router->get('/admin/businesses/create', [AdminBusinessController::class, 'create']);
$router->post('/admin/businesses', [AdminBusinessController::class, 'store']);
$router->get('/admin/businesses/{id}', [AdminBusinessController::class, 'show']);
$router->get('/admin/businesses/{id}/edit', [AdminBusinessController::class, 'edit']);
$router->post('/admin/businesses/{id}', [AdminBusinessController::class, 'update']);
$router->post('/admin/businesses/{id}/status', [AdminBusinessController::class, 'changeStatus']);
$router->post('/admin/businesses/{id}/website', [AdminBusinessController::class, 'toggleWebsite']);
$router->post('/admin/businesses/{id}/subscription', [AdminBusinessController::class, 'saveSubscription']);
$router->post('/admin/businesses/{id}/payments', [AdminBusinessController::class, 'recordPayment']);
$router->post('/admin/businesses/{id}/archive', [AdminBusinessController::class, 'archive']);
$router->get('/admin/businesses/{id}/preview', [AdminBusinessController::class, 'previewWebsite']);
$router->get('/admin/activity', [AdminActivityController::class, 'index']);
$router->get('/admin/plans', [AdminPlanController::class, 'index']);
$router->post('/admin/plans', [AdminPlanController::class, 'store']);
$router->get('/admin/plans/{id}/edit', [AdminPlanController::class, 'edit']);
$router->post('/admin/plans/{id}', [AdminPlanController::class, 'update']);
$router->post('/admin/plans/{id}/toggle', [AdminPlanController::class, 'toggle']);
$router->get('/admin/features', [AdminFeatureController::class, 'index']);
$router->post('/admin/features', [AdminFeatureController::class, 'store']);
$router->post('/admin/features/{id}', [AdminFeatureController::class, 'update']);
$router->get('/admin/notifications', [AdminNotificationController::class, 'index']);
$router->post('/admin/notifications/read', [AdminNotificationController::class, 'markRead']);

// Business owner routes
$router->get('/business', [BusinessDashboardController::class, 'index']);
$router->get('/business/profile', [ProfileController::class, 'edit']);
$router->post('/business/profile', [ProfileController::class, 'update']);
$router->get('/business/website', [WebsiteController::class, 'edit']);
$router->post('/business/website', [WebsiteController::class, 'update']);
$router->get('/business/website/preview', [WebsiteController::class, 'preview']);
$router->post('/business/website/publish', [WebsiteController::class, 'publish']);
$router->post('/business/website/unpublish', [WebsiteController::class, 'unpublish']);
$router->post('/business/submit-review', [WebsiteController::class, 'submitReview']);
$router->get('/business/subscription', [SubscriptionController::class, 'show']);
$router->get('/business/categories', [CategoryController::class, 'index']);
$router->post('/business/categories', [CategoryController::class, 'store']);
$router->post('/business/categories/{id}', [CategoryController::class, 'update']);
$router->post('/business/categories/{id}/delete', [CategoryController::class, 'delete']);
$router->get('/business/listings', [ListingController::class, 'index']);
$router->get('/business/listings/create', [ListingController::class, 'create']);
$router->post('/business/listings', [ListingController::class, 'store']);
$router->get('/business/listings/{id}/edit', [ListingController::class, 'edit']);
$router->post('/business/listings/{id}', [ListingController::class, 'update']);
$router->post('/business/listings/{id}/archive', [ListingController::class, 'archive']);
$router->get('/business/enquiries', [EnquiryController::class, 'index']);
$router->post('/business/enquiries/{id}/status', [EnquiryController::class, 'updateStatus']);
$router->get('/business/orders', [OrderController::class, 'index']);
$router->post('/business/orders/{id}/status', [OrderController::class, 'updateStatus']);
$router->get('/business/offers', [OfferController::class, 'index']);
$router->post('/business/offers', [OfferController::class, 'store']);
$router->post('/business/offers/{id}', [OfferController::class, 'update']);
$router->post('/business/offers/{id}/delete', [OfferController::class, 'delete']);
$router->get('/business/notifications', [BusinessNotificationController::class, 'index']);
$router->post('/business/notifications/read', [BusinessNotificationController::class, 'markRead']);

try {
    $router->dispatch();
} catch (HttpException $exception) {
    http_response_code($exception->statusCode());
    $view = 'errors.' . $exception->statusCode();
    $viewFile = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
    if (!is_file($viewFile)) {
        $view = 'errors.generic';
    }
    View::render($view, [
        'pageTitle' => $exception->statusCode() . ' Error',
        'statusCode' => $exception->statusCode(),
        'message' => $exception->getMessage(),
    ], 'layouts/public');
} catch (Throwable $exception) {
    http_response_code(500);
    app_log('Unhandled exception', ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
    View::render('errors.500', [
        'pageTitle' => 'Server Error',
        'message' => 'Something went wrong. Please try again later.',
    ], 'layouts/public');
}
