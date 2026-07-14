<?php use App\Core\Csrf; ?>
<section class="card">
    <h1>Profile</h1>

    <form action="<?= rtrim((string) app_config('base_url'), '/') ?>/profile" method="post" class="stack">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">

        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars((string) ($user['name'] ?? '')) ?>" required>

        <label>Avatar URL</label>
        <input type="url" name="avatar_path" value="<?= htmlspecialchars((string) ($user['avatar_path'] ?? '')) ?>" placeholder="https://...">

        <label>Role</label>
        <input type="text" value="<?= htmlspecialchars((string) ($user['role'] ?? 'regular')) ?>" disabled>

        <button type="submit">Save Profile</button>
    </form>
</section>
