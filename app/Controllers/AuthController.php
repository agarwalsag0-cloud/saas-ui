<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CustomerAuth;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\ActivityLogger;

/**
 * Portal-entry authentication for staff accounts (users table):
 *
 *   /login          → public chooser: Customer vs Business (no admin hints)
 *   /business/login → Business Portal login (business roles only)
 *   /admin/login    → protected Super Admin login (super_admin only, never linked publicly)
 *
 * Super Admin credentials are rejected on the business login and vice versa,
 * regardless of the password being correct (role filter inside Auth::attempt).
 */
class AuthController extends Controller
{
    private const THROTTLE_WINDOW = 600;   // seconds
    private const THROTTLE_MAX = 5;        // attempts per window per portal per session

    public function showChoice(): void
    {
        $this->render('auth.login', [
            'pageTitle' => 'Sign in',
        ], 'layouts/auth');
        clear_old_input();
    }

    // ---- Business Portal ------------------------------------------------------

    public function showBusinessLogin(): void
    {
        if (Auth::isBusinessUser()) {
            $this->redirect('/business');
        }

        $this->render('auth.business_login', [
            'pageTitle' => 'Business Portal Login',
        ], 'layouts/auth');
        clear_old_input();
    }

    public function businessLogin(): void
    {
        $this->verifyCsrf();
        $this->checkThrottle('business');

        $validator = (new Validator())
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('password', 'Password');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/business/login');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $error = null;

        if (!Auth::attempt($email, $password, $error, ['business_owner', 'business_staff'])) {
            $this->recordFailedAttempt('business');
            Flash::error($error ?? 'Could not log in.');
            $_SESSION['_old'] = ['email' => $email];
            $this->redirect('/business/login');
        }

        $this->resetThrottle('business');
        ActivityLogger::log('login', 'user', Auth::id());
        Flash::success('Welcome back.');
        $this->redirect(Auth::consumeIntended('/business') ?? '/business');
    }

    // ---- Super Admin portal (separate, protected entry) ------------------------

    public function showAdminLogin(): void
    {
        if (!SetupController::superAdminExistsStatic()) {
            $this->redirect('/setup');
        }

        if (Auth::isSuperAdmin()) {
            $this->redirect('/admin');
        }

        $this->render('auth.admin_login', [
            'pageTitle' => 'Admin Sign in',
        ], 'layouts/auth');
        clear_old_input();
    }

    public function adminLogin(): void
    {
        $this->verifyCsrf();

        if (!SetupController::superAdminExistsStatic()) {
            $this->redirect('/setup');
        }

        $this->checkThrottle('admin');

        $validator = (new Validator())
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('password', 'Password');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/admin/login');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $error = null;

        if (!Auth::attempt($email, $password, $error, ['super_admin'])) {
            $this->recordFailedAttempt('admin');
            Flash::error($error ?? 'Could not log in.');
            $_SESSION['_old'] = ['email' => $email];
            $this->redirect('/admin/login');
        }

        $this->resetThrottle('admin');
        ActivityLogger::log('login', 'user', Auth::id());
        Flash::success('Welcome back, Administrator.');
        $this->redirect(Auth::consumeIntended('/admin') ?? '/admin');
    }

    // ---- Shared logout ----------------------------------------------------------

    public function logout(): void
    {
        $this->verifyCsrf();
        $wasAdmin = Auth::isSuperAdmin();
        ActivityLogger::log('logout', 'user', Auth::id());
        Auth::logout();
        Flash::success('You have been logged out.');
        $this->redirect($wasAdmin ? '/admin/login' : '/login');
    }

    // ---- Login throttle (per portal, per session) --------------------------------

    private function checkThrottle(string $portal): void
    {
        $state = $_SESSION['_login_throttle'][$portal] ?? null;
        if (is_array($state) && ($state['locked_until'] ?? 0) > time()) {
            $mins = max(1, (int) ceil(($state['locked_until'] - time()) / 60));
            Flash::error('Too many failed sign-in attempts. Please wait ' . $mins . ' minute' . ($mins === 1 ? '' : 's') . ' before trying again.');
            $this->redirect($portal === 'admin' ? '/admin/login' : '/business/login');
        }
    }

    private function recordFailedAttempt(string $portal): void
    {
        $now = time();
        $state = $_SESSION['_login_throttle'][$portal] ?? ['count' => 0, 'window_start' => $now];
        if (($now - (int) ($state['window_start'] ?? $now)) > self::THROTTLE_WINDOW) {
            $state = ['count' => 0, 'window_start' => $now];
        }
        $state['count'] = (int) $state['count'] + 1;
        if ($state['count'] >= self::THROTTLE_MAX) {
            $state['locked_until'] = $now + self::THROTTLE_WINDOW;
            $state['count'] = 0;
            $state['window_start'] = $now + self::THROTTLE_WINDOW;
        }
        $_SESSION['_login_throttle'][$portal] = $state;
    }

    private function resetThrottle(string $portal): void
    {
        unset($_SESSION['_login_throttle'][$portal]);
    }
}
