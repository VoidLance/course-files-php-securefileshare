<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        // Login recipe: find user by email, then verify hashed password.
        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        // New session ID after login helps prevent session fixation.
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);

        return true;
    }

    public static function user(): ?array
    {
        // Session stores only user_id; profile data always comes fresh from DB.
        $userId = Session::get('user_id');
        if (!is_int($userId) && !is_numeric($userId)) {
            return null;
        }

        $userModel = new User();
        return $userModel->findById((int) $userId);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            // If not logged in, redirect to login and leave a flash message.
            Session::flash('error', 'Please log in before touching private files.');
            header('Location: ' . rtrim((string) app_config('base_url'), '/') . '/login');
            exit;
        }
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
