<?php use App\Core\Csrf; ?>
<section class="card auth-card">
    <h1>Register</h1>
    <p>Create an account and start sharing files responsibly (or at least pretend to).</p>

    <form action="<?= rtrim((string) app_config('base_url'), '/') ?>/register" method="post" class="stack">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">

        <label>Name</label>
        <input type="text" name="name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" minlength="8" required>

        <button type="submit">Register</button>
    </form>

    <small>Already registered? <a href="<?= rtrim((string) app_config('base_url'), '/') ?>/login">Login</a>.</small>
</section>
