<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\FileController;
use App\Controllers\ShareController;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Router;
use App\Core\Session;

require dirname(__DIR__) . '/bootstrap.php';

$router = new Router();

// Home just redirects based on auth state; no fancy landing page yet.
$router->get('/', static function (): void {
    if (Auth::check()) {
        header('Location: ' . rtrim((string) app_config('base_url'), '/') . '/dashboard');
        exit;
    }

    header('Location: ' . rtrim((string) app_config('base_url'), '/') . '/login');
    exit;
});

// Auth routes.
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/profile', [AuthController::class, 'showProfile']);
$router->post('/profile', [AuthController::class, 'updateProfile']);

// Dashboard + file operations.
$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/files', [FileController::class, 'index']);
$router->post('/files/upload', [FileController::class, 'upload']);
$router->get('/files/download', [FileController::class, 'download']);

// Sharing routes (private create/revoke + public token access).
$router->post('/shares/create', [ShareController::class, 'create']);
$router->post('/shares/revoke', [ShareController::class, 'revoke']);
$router->get('/shared', [ShareController::class, 'showPublic']);
$router->post('/shared', [ShareController::class, 'showPublic']);

// One dispatch to rule them all.
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
