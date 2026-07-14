<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Jwt;
use App\Models\FileItem;

require dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

// Trim project base path so route checks stay clean locally.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/api', PHP_URL_PATH) ?: '/api';
$basePath = rtrim((string) app_config('base_url', ''), '/');
if ($basePath !== '' && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
    $path = $path === '' ? '/' : $path;
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Tiny token bucket in session. Not enterprise-level, but great for class demos.
$rate = $_SESSION['api_rate'] ?? ['count' => 0, 'time' => time()];
if ((time() - (int) $rate['time']) >= 60) {
    $rate = ['count' => 0, 'time' => time()];
}
$rate['count']++;
$_SESSION['api_rate'] = $rate;

if ($rate['count'] > 60) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

// Step 1 to replicate API usage: login in web UI, then POST /api/token.
if ($path === '/api/token' && $method === 'POST') {
    if (!Auth::check()) {
        http_response_code(401);
        echo json_encode(['error' => 'Login required first']);
        exit;
    }

    $user = Auth::user();
    $token = Jwt::encode([
        'sub' => (int) ($user['id'] ?? 0),
        'role' => (string) ($user['role'] ?? 'regular'),
    ]);

    echo json_encode(['token' => $token]);
    exit;
}

// Step 2: call GET /api/files with Authorization: Bearer <token>.
if ($path === '/api/files' && $method === 'GET') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing bearer token']);
        exit;
    }

    $token = substr($authHeader, 7);
    $payload = Jwt::decode($token);

    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }

    $files = (new FileItem())->allForUser((int) $payload['sub']);
    echo json_encode(['data' => $files]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'API route not found']);
