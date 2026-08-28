<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function render(string $view, array $params = [], $layout = 'layouts/app'): void
    {
        View::render($view, $params, $layout);
    }

    protected function redirect(string $path): void
    {
        redirect($path);
    }

    protected function verifyCsrf(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            throw new HttpException(419);
        }
    }

    protected function requirePost(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            throw new HttpException(405, 'Method not allowed.');
        }
    }

    protected function flashValidationErrors(array $errors): void
    {
        foreach ($errors as $error) {
            Flash::error($error);
        }
        $_SESSION['_old'] = $_POST;
    }
}
