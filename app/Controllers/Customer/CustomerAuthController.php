<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\CustomerAuth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\ActivityLogger;
use App\Services\GoogleOAuthService;
use PDO;
use Throwable;

class CustomerAuthController extends Controller
{
    public function showRegister(): void
    {
        if (CustomerAuth::check()) {
            $this->redirect('/customer');
        }
        $this->render('customer.register', [
            'pageTitle' => 'Create Customer Account',
            'googleEnabled' => GoogleOAuthService::isConfigured(),
        ], 'layouts/auth');
        clear_old_input();
    }

    public function register(): void
    {
        $this->verifyCsrf();

        $validator = (new Validator())
            ->required('name', 'Name')
            ->max('name', 'Name', 190)
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('password', 'Password')
            ->min('password', 'Password', 8)
            ->matches('password_confirmation', 'password', 'Password confirmation');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/customer/register');
        }

        $pdo = Database::pdo();
        $email = strtolower(trim((string) $_POST['email']));
        $password = (string) $_POST['password'];

        $check = $pdo->prepare('SELECT id FROM customer_accounts WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch(PDO::FETCH_ASSOC)) {
            Flash::error('This email already has a customer account. Please log in instead.');
            $_SESSION['_old'] = ['name' => $_POST['name'] ?? '', 'email' => $email];
            $this->redirect('/customer/register');
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO customer_accounts (name, email, phone, password_hash, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, "active", NOW(), NOW())'
            );
            $stmt->execute([
                trim((string) $_POST['name']),
                $email,
                trim((string) ($_POST['phone'] ?? '')) ?: null,
                password_hash($password, PASSWORD_DEFAULT),
            ]);
            $accountId = (int) $pdo->lastInsertId();
        } catch (Throwable $exception) {
            app_log('Customer registration failed', ['message' => $exception->getMessage()]);
            Flash::error('Could not create your account. Please try again.');
            $this->redirect('/customer/register');
        }

        CustomerAuth::attempt($accountId);
        ActivityLogger::log('customer_registered', 'customer_account', $accountId);
        Flash::success('Welcome! Your customer account is ready — no approval needed.');
        $this->redirect('/customer');
    }

    public function showLogin(): void
    {
        if (CustomerAuth::check()) {
            $this->redirect('/customer');
        }
        $this->render('customer.login', [
            'pageTitle' => 'Customer Login',
            'googleEnabled' => GoogleOAuthService::isConfigured(),
        ], 'layouts/auth');
        clear_old_input();
    }

    public function login(): void
    {
        $this->verifyCsrf();

        $validator = (new Validator())
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('password', 'Password');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/customer/login');
        }

        $email = strtolower(trim((string) $_POST['email']));
        $password = (string) ($_POST['password'] ?? '');

        $stmt = Database::pdo()->prepare('SELECT * FROM customer_accounts WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account || empty($account['password_hash']) || !password_verify($password, $account['password_hash'])) {
            Flash::error('Invalid email or password.');
            $_SESSION['_old'] = ['email' => $email];
            $this->redirect('/customer/login');
        }

        if ($account['status'] !== 'active') {
            Flash::error('This customer account is not active.');
            $this->redirect('/customer/login');
        }

        if (password_needs_rehash((string) $account['password_hash'], PASSWORD_DEFAULT)) {
            $rehash = Database::pdo()->prepare('UPDATE customer_accounts SET password_hash = ?, updated_at = NOW() WHERE id = ?');
            $rehash->execute([password_hash($password, PASSWORD_DEFAULT), (int) $account['id']]);
        }

        CustomerAuth::attempt((int) $account['id']);
        Flash::success('Welcome back, ' . $account['name'] . '.');
        $this->redirect('/customer');
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        CustomerAuth::logout();
        Flash::success('You have been logged out.');
        $this->redirect('/');
    }

    // ---- Google OAuth (authorization code flow) -------------------------

    public function googleRedirect(): void
    {
        if (!GoogleOAuthService::isConfigured()) {
            Flash::warning('Google sign-in is not configured on this platform yet.');
            $this->redirect('/customer/login');
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['customer_oauth_state'] = $state;
        $this->redirect_external(GoogleOAuthService::authorizationUrl($state));
    }

    public function googleCallback(): void
    {
        if (!GoogleOAuthService::isConfigured()) {
            Flash::warning('Google sign-in is not configured on this platform yet.');
            $this->redirect('/customer/login');
        }

        $error = trim((string) ($_GET['error'] ?? ''));
        if ($error !== '') {
            Flash::warning('Google sign-in was cancelled or failed.');
            $this->redirect('/customer/login');
        }

        $code = (string) ($_GET['code'] ?? '');
        $state = (string) ($_GET['state'] ?? '');
        if ($code === '' || $state === '' || empty($_SESSION['customer_oauth_state']) || !hash_equals((string) $_SESSION['customer_oauth_state'], $state)) {
            unset($_SESSION['customer_oauth_state']);
            Flash::error('Google sign-in security check failed. Please try again.');
            $this->redirect('/customer/login');
        }
        unset($_SESSION['customer_oauth_state']);

        try {
            $claims = GoogleOAuthService::exchange($code);
        } catch (Throwable $exception) {
            app_log('Google customer sign-in failed', ['message' => $exception->getMessage()]);
            Flash::error('Google sign-in could not be completed. You can still use email + password.');
            $this->redirect('/customer/login');
            return;
        }

        $pdo = Database::pdo();

        $stmt = $pdo->prepare('SELECT * FROM customer_accounts WHERE google_sub = ? LIMIT 1');
        $stmt->execute([$claims['sub']]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account && $claims['email']) {
            $stmt = $pdo->prepare('SELECT * FROM customer_accounts WHERE email = ? LIMIT 1');
            $stmt->execute([$claims['email']]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($account) {
                // Link this Google identity to the existing verified email account.
                if ($account['status'] !== 'active') {
                    Flash::error('This customer account is not active.');
                    $this->redirect('/customer/login');
                }
                $link = $pdo->prepare('UPDATE customer_accounts SET google_sub = ?, updated_at = NOW() WHERE id = ?');
                $link->execute([$claims['sub'], (int) $account['id']]);
            }
        }

        if (!$account) {
            // Account creation on first successful Google authentication.
            $email = $claims['email'] ?: ('google-' . substr(sha1((string) $claims['sub']), 0, 12) . '@google.local');
            try {
                $insert = $pdo->prepare(
                    'INSERT INTO customer_accounts (name, email, phone, password_hash, google_sub, status, created_at, updated_at)
                     VALUES (?, ?, NULL, NULL, ?, "active", NOW(), NOW())'
                );
                $insert->execute([$claims['name'], $email, $claims['sub']]);
                $account = [
                    'id' => (int) $pdo->lastInsertId(),
                    'name' => $claims['name'],
                    'status' => 'active',
                ];
                ActivityLogger::log('customer_created_via_google', 'customer_account', (int) $account['id']);
            } catch (Throwable $exception) {
                app_log('Google customer account creation failed', ['message' => $exception->getMessage()]);
                Flash::error('Could not create your customer account. Please try again.');
                $this->redirect('/customer/login');
                return;
            }
        } elseif ($account['status'] !== 'active') {
            Flash::error('This customer account is not active.');
            $this->redirect('/customer/login');
        }

        CustomerAuth::attempt((int) $account['id']);
        Flash::success('Signed in with Google.');
        $this->redirect('/customer');
    }

    private function redirect_external(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
