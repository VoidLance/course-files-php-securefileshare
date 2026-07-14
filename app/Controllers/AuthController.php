<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\User;

// If this file is opened directly in a browser, bounce to root launcher.
if (!defined('APP_BOOTSTRAPPED')) {
    header('Location: /');
    exit;
}

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Login']);
    }

    public function login(): void
    {
        // Standard POST form guard: reject if CSRF token is missing/bad.
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            Session::flash('error', 'CSRF check failed. Nice try, chaos gremlin.');
            $this->redirect('/login');
        }

        // Pull and sanitize user input.
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!Auth::attempt($email, $password)) {
            Session::flash('error', 'Invalid credentials. Maybe check caps lock?');
            $this->redirect('/login');
        }

        $user = Auth::user();
        if ($user) {
            (new ActivityLog())->log((int) $user['id'], 'login', 'User logged in', $_SERVER['REMOTE_ADDR'] ?? null);
        }

        $this->redirect('/dashboard');
    }

    public function showRegister(): void
    {
        $this->view('auth/register', ['title' => 'Register']);
    }

    public function register(): void
    {
        // Same CSRF rule for registration forms.
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            Session::flash('error', 'CSRF check failed. Request rejected politely.');
            $this->redirect('/register');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || $email === '' || strlen($password) < 8) {
            Session::flash('error', 'Name/email required. Password needs 8+ chars.');
            $this->redirect('/register');
        }

        // Avoid duplicate emails so one identity maps to one account.
        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            Session::flash('error', 'Email already registered. Your clone got here first.');
            $this->redirect('/register');
        }

        // Password is stored hashed, never plain text.
        $created = $userModel->create([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'regular',
            'storage_quota_bytes' => 50 * 1024 * 1024,
        ]);

        if (!$created) {
            Session::flash('error', 'Registration failed. Database said not today.');
            $this->redirect('/register');
        }

        Session::flash('success', 'Registration done. Please log in.');
        $this->redirect('/login');
    }

    public function logout(): void
    {
        $user = Auth::user();
        if ($user) {
            (new ActivityLog())->log((int) $user['id'], 'logout', 'User logged out', $_SERVER['REMOTE_ADDR'] ?? null);
        }

        Auth::logout();
        Session::flash('success', 'Logged out. Session terminated with extreme prejudice.');
        $this->redirect('/login');
    }

    public function showProfile(): void
    {
        Auth::requireLogin();

        $this->view('auth/profile', [
            'title' => 'Profile',
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(): void
    {
        Auth::requireLogin();

        // Profile update also gets CSRF protection.
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            Session::flash('error', 'CSRF check failed. Profile not updated.');
            $this->redirect('/profile');
        }

        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $avatarPath = trim((string) ($_POST['avatar_path'] ?? ''));

        (new User())->updateProfile((int) $user['id'], $name, $avatarPath !== '' ? $avatarPath : null);
        Session::flash('success', 'Profile updated. Looking sharp.');

        $this->redirect('/profile');
    }
}
