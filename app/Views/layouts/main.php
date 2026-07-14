<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

$baseUrl = rtrim((string) app_config('base_url'), '/');
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <title><?= htmlspecialchars($title ?? 'SecureFileShare') ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">SecureFileShare</div>
    <nav>
        <?php if ($currentUser): ?>
            <a href="<?= $baseUrl ?>/dashboard">Dashboard</a>
            <a href="<?= $baseUrl ?>/files">Files</a>
            <a href="<?= $baseUrl ?>/profile">Profile</a>
            <form action="<?= $baseUrl ?>/logout" method="post" class="inline-form">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <button type="submit">Logout</button>
            </form>
        <?php else: ?>
            <a href="<?= $baseUrl ?>/login">Login</a>
            <a href="<?= $baseUrl ?>/register">Register</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container">
    <?php if ($error = Session::flash('error')): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success = Session::flash('success')): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($shareLink = Session::flash('share_link')): ?>
        <div class="alert alert-share">
            <strong>Share link ready:</strong>
            <div class="share-link-row">
                <input
                    id="shareLinkField"
                    type="text"
                    readonly
                    value="<?= htmlspecialchars($shareLink) ?>"
                    aria-label="Generated share link"
                >
                <a class="share-open-link" href="<?= htmlspecialchars($shareLink) ?>" target="_blank" rel="noopener noreferrer">Open</a>
                <button type="button" id="copyShareLinkButton">Copy</button>
            </div>
        </div>
    <?php endif; ?>

    <?php require $viewFile; ?>
</main>

<script src="<?= $baseUrl ?>/assets/js/app.js"></script>
</body>
</html>
