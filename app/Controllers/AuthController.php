<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\ActivityLogger;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (!SetupController::superAdminExistsStatic()) {
            $this->redirect('/setup');
        }

        if (Auth::check()) {
            $this->redirect(Auth::redirectPath());
        }

        $this->render('auth.login', [
            'pageTitle' => 'Login',
        ], 'layouts/auth');
        clear_old_input();
    }

    public function login(): void
    {
        $this->verifyCsrf();

        if (!SetupController::superAdminExistsStatic()) {
            $this->redirect('/setup');
        }

        $validator = (new Validator())
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('password', 'Password');

        if (!$validator->passes()) {
            $this->flashValidationErrors($validator->errors());
            $this->redirect('/login');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $error = null;

        if (!Auth::attempt($email, $password, $error)) {
            Flash::error($error ?? 'Could not log in.');
            $_SESSION['_old'] = ['email' => $email];
            $this->redirect('/login');
        }

        ActivityLogger::log('login', 'user', Auth::id());
        Flash::success('Welcome back.');
        $this->redirect(Auth::redirectPath());
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        ActivityLogger::log('logout', 'user', Auth::id());
        Auth::logout();
        Flash::success('You have been logged out.');
        $this->redirect('/login');
    }
}
