<?php use App\Core\Csrf; ?>
<section class="card auth-card">
    <h1>Login</h1>
    <p>Welcome back. Time to wrangle your files.</p>

    <form action="<?= rtrim((string) app_config('base_url'), '/') ?>/login" method="post" class="stack">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <small>Need an account? <a href="<?= rtrim((string) app_config('base_url'), '/') ?>/register">Register here</a>.</small>
</section>
