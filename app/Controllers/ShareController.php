<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\FileCipher;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\FileItem;
use App\Models\ShareLink;

// If this file is opened directly in a browser, bounce to root launcher.
if (!defined('APP_BOOTSTRAPPED')) {
    header('Location: /');
    exit;
}

final class ShareController extends Controller
{
    public function create(): void
    {
        Auth::requireLogin();

        // CSRF first so strangers cannot create links from another tab/site.
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            Session::flash('error', 'CSRF check failed. Share link not created.');
            $this->redirect('/files');
        }

        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
        }

        // Ensure the file belongs to current user before sharing it.
        $fileId = (int) ($_POST['file_id'] ?? 0);
        $file = (new FileItem())->findByIdAndUser($fileId, (int) $user['id']);

        if (!$file) {
            Session::flash('error', 'File not found. Share canceled.');
            $this->redirect('/files');
        }

        // Clamp expiration to a safe range for this starter app.
        $expiresDays = (int) ($_POST['expires_in_days'] ?? app_config('security.share_default_days', 7));
        $expiresDays = max(1, min(30, $expiresDays));

        // Keep permissions predictable even if someone tampers with form input.
        $permission = (string) ($_POST['permission'] ?? 'view');
        if (!in_array($permission, ['view', 'download', 'edit'], true)) {
            $permission = 'view';
        }

        // Password is optional, but if provided it is hashed before saving.
        $password = trim((string) ($_POST['share_password'] ?? ''));

        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresDays * 86400));
        $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;

        (new ShareLink())->create([
            'file_id' => (int) $file['id'],
            'token' => $token,
            'permission' => $permission,
            'expires_at' => $expiresAt,
            'password_hash' => $passwordHash,
            'created_by' => (int) $user['id'],
        ]);

        $shareUrl = $this->buildShareUrl($token);

        (new ActivityLog())->log(
            (int) $user['id'],
            'share_created',
            'Created share link for file #' . (int) $file['id'],
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        Session::flash('success', 'Share link created. Handle with care (or chaos).');
        Session::flash('share_link', $shareUrl);
        $this->redirect('/files');
    }

    public function revoke(): void
    {
        Auth::requireLogin();

        if (!Csrf::verify($_POST['_token'] ?? null)) {
            Session::flash('error', 'CSRF check failed. Revoke blocked.');
            $this->redirect('/dashboard');
        }

        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
        }

        $shareId = (int) ($_POST['share_id'] ?? 0);
        (new ShareLink())->revoke($shareId, (int) $user['id']);

        (new ActivityLog())->log(
            (int) $user['id'],
            'share_revoked',
            'Revoked share link #' . $shareId,
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        Session::flash('success', 'Share link revoked. Link status: retired.');
        $this->redirect('/dashboard');
    }

    public function showPublic(): void
    {
        // Public route: token in URL identifies the shared resource.
        $token = (string) ($_GET['token'] ?? '');
        $share = (new ShareLink())->findByToken($token);

        if (!$share) {
            http_response_code(404);
            echo 'Share link not found.';
            return;
        }

        // Dead links should return gone/invalid quickly.
        if ($share['revoked_at'] !== null || strtotime((string) $share['expires_at']) < time()) {
            http_response_code(410);
            echo 'Share link expired or revoked.';
            return;
        }

        // Password-protected link flow: POST password, verify hash, then stream.
        if ($share['password_hash'] && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = (string) ($_POST['share_password'] ?? '');
            if (!password_verify($password, (string) $share['password_hash'])) {
                $this->view('shares/public', ['title' => 'Shared File', 'share' => $share, 'error' => 'Wrong password.']);
                return;
            }

            $this->streamSharedFile($share);
            return;
        }

        if (!$share['password_hash']) {
            $this->streamSharedFile($share);
            return;
        }

        $this->view('shares/public', ['title' => 'Shared File', 'share' => $share]);
    }

    private function streamSharedFile(array $share): void
    {
        // Stream path: encrypted bytes -> decrypt -> send as file download.
        $storagePath = rtrim((string) app_config('storage.upload_dir'), '/') . '/' . $share['storage_name'];
        if (!file_exists($storagePath)) {
            http_response_code(404);
            echo 'Shared file no longer exists.';
            return;
        }

        $cipherBytes = (string) file_get_contents($storagePath);
        $plainBytes = FileCipher::decrypt($cipherBytes, (string) $share['iv_base64']);

        header('Content-Type: ' . $share['mime_type']);
        header('Content-Disposition: attachment; filename="' . addslashes((string) $share['original_name']) . '"');
        echo $plainBytes;
    }

    private function buildShareUrl(string $token): string
    {
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $isHttps ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost:3000');
        $basePath = rtrim((string) app_config('base_url'), '/');

        return $scheme . '://' . $host . $basePath . '/shared?token=' . urlencode($token);
    }
}
