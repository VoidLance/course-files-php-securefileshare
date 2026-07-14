<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\FileItem;
use App\Models\ShareLink;
use App\Models\User;

// If this file is opened directly in a browser, bounce to root launcher.
if (!defined('APP_BOOTSTRAPPED')) {
    header('Location: /');
    exit;
}

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
        }

        $fileModel = new FileItem();
        $shareModel = new ShareLink();
        $activityModel = new ActivityLog();
        $userModel = new User();

        $files = $fileModel->allForUser((int) $user['id']);
        $shares = $shareModel->allForUser((int) $user['id']);
        $activities = $activityModel->latestForUser((int) $user['id']);

        $usage = $userModel->storageUsageBytes((int) $user['id']);
        $quota = (int) $user['storage_quota_bytes'];

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'files' => $files,
            'shares' => $shares,
            'activities' => $activities,
            'usage' => $usage,
            'quota' => $quota,
        ]);
    }
}
