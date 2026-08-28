<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Validator;
use App\Services\ActivityLogger;
use PDO;
use Throwable;

class SetupController extends Controller
{
    public function show(): void
    {
        if ($this->superAdminExists()) {
            Flash::info('Initial Super Admin already exists. Sign in at the admin portal entry.');
            $this->redirect('/admin/login');
        }

        $this->render('auth.setup', [
            'pageTitle' => 'Initial Super Admin Setup',
        ], 'layouts/auth');
        clear_old_input();
    }

    public function store(): void
    {
        $this->verifyCsrf();

        if ($this->superAdminExists()) {
            Flash::warning('Initial setup is already complete.');
            $this->redirect('/admin/login');
        }

        $validator = (new Validator())
            ->required('name', 'Name')
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('password', 'Password');

        $errors = $validator->errors();
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirmation'] ?? '');

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Password confirmation does not match.';
        }

        if ($errors) {
            $this->flashValidationErrors($errors);
            $this->redirect('/setup');
        }

        $pdo = Database::pdo();
        $email = strtolower(trim((string) $_POST['email']));

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch(PDO::FETCH_ASSOC)) {
            Flash::error('This email is already used.');
            $_SESSION['_old'] = ['name' => $_POST['name'] ?? '', 'email' => $email];
            $this->redirect('/setup');
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (business_id, name, email, phone, password_hash, role, status, created_at, updated_at)
                 VALUES (NULL, ?, ?, NULL, ?, "super_admin", "active", NOW(), NOW())'
            );
            $stmt->execute([
                trim((string) $_POST['name']),
                $email,
                password_hash($password, PASSWORD_DEFAULT),
            ]);
            $userId = (int) $pdo->lastInsertId();
        } catch (Throwable $exception) {
            app_log('Initial super admin creation failed', ['message' => $exception->getMessage()]);
            Flash::error('Could not create Super Admin. Please check database setup and try again.');
            $this->redirect('/setup');
        }

        ActivityLogger::log('initial_super_admin_created', 'user', $userId);
        Auth::attempt($email, $password);
        Flash::success('Super Admin created successfully. You are now logged in.');
        $this->redirect('/admin');
    }

    public static function superAdminExistsStatic(): bool
    {
        try {
            $stmt = Database::pdo()->query('SELECT COUNT(*) FROM users WHERE role = "super_admin"');
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private function superAdminExists(): bool
    {
        return self::superAdminExistsStatic();
    }
}
