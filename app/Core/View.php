<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $view, array $params = [], $layout = 'layouts/app'): void
    {
        $viewFile = APP_PATH . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';
        if (!is_file($viewFile)) {
            throw new HttpException(500, 'View not found.');
        }

        extract($params, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === false || $layout === null) {
            echo $content;
            return;
        }

        $layoutFile = APP_PATH . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, (string) $layout) . '.php';
        if (!is_file($layoutFile)) {
            throw new HttpException(500, 'Layout not found.');
        }

        require $layoutFile;
    }
}
