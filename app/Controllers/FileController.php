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
use App\Models\User;

// If this file is opened directly in a browser, bounce to root launcher.
if (!defined('APP_BOOTSTRAPPED')) {
    header('Location: /');
    exit;
}

final class FileController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
        }

        // Search is optional; blank query returns all user files.
        $query = trim((string) ($_GET['q'] ?? ''));
        $model = new FileItem();
        $files = $query !== ''
            ? $model->searchForUser((int) $user['id'], $query)
            : $model->allForUser((int) $user['id']);

        $this->view('files/index', [
            'title' => 'My Files',
            'user' => $user,
            'files' => $files,
            'query' => $query,
        ]);
    }

    public function upload(): void
    {
        Auth::requireLogin();

        // Step 1: reject forged form submissions.
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            Session::flash('error', 'CSRF check failed. Upload blocked.');
            $this->redirect('/files');
        }

        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
        }

        // Step 2: verify PHP actually received a file payload.
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            Session::flash('error', 'No file selected. Your upload is imaginary.');
            $this->redirect('/files');
        }

        $upload = $_FILES['file'];

        // Step 3: stop early if PHP upload layer reported an error.
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Upload failed with error code ' . (int) $upload['error']);
            $this->redirect('/files');
        }

        $maxSize = (int) app_config('security.max_upload_size_bytes');
        $size = (int) ($upload['size'] ?? 0);

        // Step 4: enforce quota before touching disk.
        $userModel = new User();
        $usage = $userModel->storageUsageBytes((int) $user['id']);
        $quota = (int) $user['storage_quota_bytes'];

        if ($usage + $size > $quota) {
            Session::flash('error', 'Storage quota exceeded. Time to clean up or go premium.');
            $this->redirect('/files');
        }

        // Step 5: enforce file size limits.
        if ($size <= 0 || $size > $maxSize) {
            Session::flash('error', 'File size invalid or above max upload limit.');
            $this->redirect('/files');
        }

        // Step 6: mime allow-list check (basic content safety gate).
        $tmpName = (string) ($upload['tmp_name'] ?? '');
        $mimeType = mime_content_type($tmpName) ?: 'application/octet-stream';
        $allowed = app_config('security.allowed_mime_types', []);

        if (!in_array($mimeType, $allowed, true)) {
            Session::flash('error', 'File type not allowed. The bouncer said no.');
            $this->redirect('/files');
        }

        // Step 7: encrypt bytes before saving to storage.
        $plainBytes = (string) file_get_contents($tmpName);
        $encrypted = FileCipher::encrypt($plainBytes);

        // Step 8: save encrypted blob on disk using random storage filename.
        $storageName = bin2hex(random_bytes(20)) . '.bin';
        $storagePath = rtrim((string) app_config('storage.upload_dir'), '/') . '/' . $storageName;

        file_put_contents($storagePath, $encrypted['cipher']);

        // Step 9: store metadata (original name, checksum, iv, folder, tags) in DB.
        $tags = trim((string) ($_POST['tags'] ?? ''));
        $folder = trim((string) ($_POST['folder_name'] ?? 'root'));

        $fileId = (new FileItem())->create([
            'user_id' => (int) $user['id'],
            'original_name' => (string) ($upload['name'] ?? 'uploaded_file'),
            'storage_name' => $storageName,
            'mime_type' => $mimeType,
            'size_bytes' => $size,
            'folder_name' => $folder !== '' ? $folder : 'root',
            'tags' => $tags !== '' ? $tags : null,
            'checksum_sha256' => $encrypted['checksum'],
            'iv_base64' => $encrypted['iv'],
            'version_number' => 1,
        ]);

        // Step 10: audit log makes investigations way less painful later.
        (new ActivityLog())->log(
            (int) $user['id'],
            'file_upload',
            'Uploaded file #' . $fileId,
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        Session::flash('success', 'File uploaded and encrypted at rest. Vault mode: enabled.');
        $this->redirect('/files');
    }

    public function download(): void
    {
        Auth::requireLogin();

        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
        }

        // Only owner can download via this route.
        $fileId = (int) ($_GET['id'] ?? 0);
        $file = (new FileItem())->findByIdAndUser($fileId, (int) $user['id']);

        if (!$file) {
            http_response_code(404);
            echo 'File not found.';
            return;
        }

        $storagePath = rtrim((string) app_config('storage.upload_dir'), '/') . '/' . $file['storage_name'];
        if (!file_exists($storagePath)) {
            http_response_code(404);
            echo 'Stored file missing.';
            return;
        }

        // Read encrypted bytes from storage, then decrypt in memory.
        $cipherBytes = (string) file_get_contents($storagePath);
        $plainBytes = FileCipher::decrypt($cipherBytes, (string) $file['iv_base64']);

        // Integrity check: if hash changed, fail loudly instead of serving junk.
        $checksum = hash('sha256', $plainBytes);
        if (!hash_equals((string) $file['checksum_sha256'], $checksum)) {
            http_response_code(409);
            echo 'Integrity check failed. Data may be corrupted.';
            return;
        }

        (new ActivityLog())->log(
            (int) $user['id'],
            'file_download',
            'Downloaded file #' . (int) $file['id'],
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . strlen($plainBytes));
        header('Content-Disposition: attachment; filename="' . addslashes((string) $file['original_name']) . '"');

        echo $plainBytes;
    }
}
