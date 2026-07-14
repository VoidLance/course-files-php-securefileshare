<section class="card auth-card">
    <h1>Shared File</h1>
    <p><strong><?= htmlspecialchars((string) ($share['original_name'] ?? 'Unknown file')) ?></strong></p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars((string) $error) ?></div>
    <?php endif; ?>

    <p>This link is password-protected. Enter password to access file.</p>
    <form action="<?= rtrim((string) app_config('base_url'), '/') ?>/shared?token=<?= urlencode((string) ($share['token'] ?? '')) ?>" method="post" class="stack">
        <label>Share Password</label>
        <input type="password" name="share_password" required>
        <button type="submit">Unlock File</button>
    </form>
</section>
