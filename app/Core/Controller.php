<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view);
            return;
        }

        require dirname(__DIR__) . '/Views/layouts/main.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . rtrim((string) app_config('base_url'), '/') . $path);
        exit;
    }
}
