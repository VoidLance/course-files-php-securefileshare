<?php

declare(strict_types=1);

// App config: one place to rule them all (and blame later).
return [
    'app_name' => 'SecureFileShare',
    // Keep empty to auto-detect from script path (works for / and /SecureFileShare/public).
    'base_url' => '',
    'db' => [
        // Default: sqlite (no DB server needed). Set to mysql if you want MySQL.
        'driver' => 'sqlite',
        'sqlite_path' => dirname(__DIR__) . '/database/app.sqlite',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'secure_file_share',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'app_key' => 'change-me-before-production-please',
        'jwt_secret' => 'change-me-too-super-secret-key',
        'share_default_days' => 7,
        'max_upload_size_bytes' => 25 * 1024 * 1024,
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'application/pdf',
            'text/plain',
            'application/zip',
        ],
    ],
    'storage' => [
        'upload_dir' => dirname(__DIR__) . '/storage/uploads',
    ],
];
