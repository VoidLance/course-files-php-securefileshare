# Project 4: Secure File Sharing Platform

Beginner-friendly PHP 8 starter project for a secure file sharing app.

This project is intentionally built in simple MVC-style vanilla PHP so students can understand every line before jumping into Laravel/Symfony.

## What This Starter Already Includes

- User registration, login, logout, and profile update
- Role column (`admin`, `premium`, `regular`) in DB
- CSRF protection for forms
- Secure password hashing (`password_hash`)
- File upload + drag-and-drop UI + upload progress bar
- File encryption at rest (`AES-256-CBC` with OpenSSL)
- File integrity check (SHA-256 checksum)
- Folder and tag metadata on files
- Share links with:
  - unique tokens
  - expiration dates
  - optional password protection
  - permissions (`view`, `download`, `edit`)
  - revoke support
- Storage quota checks per user
- Activity logs (login, upload, download, sharing)
- JWT helper + tiny REST API demo
- Mobile-friendly responsive UI

## Full Feature Roadmap (Mapped To Your Brief)

### 1) User Management

- [x] Register, login, logout
- [x] Profile update (name/avatar URL)
- [x] Role support in DB
- [ ] Email verification flow
- [ ] Optional 2FA flow (TOTP)
- [ ] Password reset by email

### 2) File Management

- [x] Upload + progress bar
- [ ] Multiple/chunked upload
- [x] Drag/drop upload
- [x] Folders + tags
- [ ] Rich preview (image/pdf/text inline)
- [ ] Versioning UI (table exists)
- [x] Encryption at rest

### 3) File Sharing

- [x] Share links
- [x] Expiration date
- [x] Password protected share link
- [x] Granular permissions field
- [ ] Share via email
- [x] Revoke access

### 4) Storage/Quotas

- [x] Per-user quota check
- [x] Usage dashboard
- [ ] Premium upgrade/payment flow
- [ ] Auto cleanup for expired links/files

### 5) Search/Filter

- [x] Basic search by name/folder/tags
- [ ] Full-text content indexing
- [ ] Advanced filters and sorting controls

### 6) Collaboration

- [x] Workspace and comments tables
- [ ] Shared workspace UI/logic
- [ ] Comments + @mention notifications

### 7) Admin Panel

- [ ] Admin dashboard and user management tools
- [ ] System-wide search
- [ ] Link monitoring/settings pages

### 8) Security Features

- [x] Encryption + checksum + audit logs
- [ ] Virus scanning integration (ClamAV)
- [ ] IP access restrictions
- [ ] Organization sharing policy rules

### 9) API

- [x] Basic REST endpoint and JWT auth helper
- [ ] OAuth 2.0 integration
- [x] Basic rate-limiting example

### 10) Mobile Responsiveness

- [x] Responsive web UI
- [ ] Native mobile app (optional)

## Project Structure

- `app/Controllers` - route handlers
- `app/Models` - DB logic via PDO
- `app/Core` - router, auth, csrf, db, jwt, encryption
- `app/Views` - templates
- `config/app.php` - app config
- `database/schema.sql` - database setup
- `database/schema.sqlite.sql` - SQLite setup (default)
- `public` - web root (`index.php`, assets, `api.php`)
- `storage/uploads` - encrypted file storage

## Setup

Default setup uses SQLite (no separate DB server needed).

1. Ensure app can write to `database/` and `storage/`.
2. Keep `db.driver` as `sqlite` in `config/app.php` (already default).
3. Run local server from this project folder:

```bash
php -S localhost:3000 -t public
```

4. Open:
   - `http://localhost:3000`

SQLite DB file (`database/app.sqlite`) is auto-created on first request.

### Optional: Use MySQL Instead

1. Set `db.driver` to `mysql` in `config/app.php`.
2. Import `database/schema.sql` into MySQL.
3. Update MySQL credentials in `config/app.php`.

Important:
- Do not browse files inside `app/Controllers` directly in the browser.
- Use app routes only (`/`, `/login`, `/files`, etc.) through `public/index.php`.

Default seeded admin user:
- Email: `admin@example.com`
- Password: `admin123`

## API Quick Test

1. Log in via web UI first.
2. Request token:

```bash
curl -X POST http://localhost:3000/api/token
```

3. Use token:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:3000/api/files
```

## Replication Checklist (Feature-by-Feature)

Use this when you want proof a feature works, not just vibes.

1. Register and login
   - Open `/register`, create user, then login at `/login`.
2. Upload with encryption at rest
   - Go to `/files`, drag and drop a file, click upload.
   - Verify file appears in table and can be downloaded.
3. Confirm quota enforcement
   - Temporarily lower `storage_quota_bytes` for your user in DB.
   - Upload a larger file and confirm quota error appears.
4. Confirm share link expiry/password
   - Create share link with password and 1-day expiry.
   - Open shared URL in private window, enter password, download file.
   - Revoke link from dashboard and confirm it returns gone/invalid.
5. Confirm activity logs
   - Login, upload, download, create/revoke share.
   - Check `activity_logs` table for corresponding events.
6. Confirm API auth and rate limit
   - Request `/api/token` while logged in.
   - Call `/api/files` with bearer token.
   - Hammer API >60 requests/minute and confirm HTTP 429.

## Important Student Notes

- This starter is educational, not production-ready.
- For production: use proper env config, strict CSP headers, secure cookies, queue workers, object storage (S3), and malware scanning.
- Yes, some TODO boxes are unchecked on purpose. That is your semester adventure.

## Troubleshooting

- Error: `Database connection failed. Your SQL server is either sleeping or offended.`
   - Cause: app is configured for MySQL, but MySQL is not running or credentials are wrong.
   - Fast fix: switch to SQLite in `config/app.php` by setting `db.driver` to `sqlite`.

- Error: `GET /SecureFileShare/app/Controllers/FileController.php ... 500`
   - Cause: a controller class file was requested directly as a page.
   - Fix: run server with public root and open app routes:

```bash
php -S localhost:3000 -t public
```

Then open `http://localhost:3000` (or `http://localhost:3001` if port 3000 is busy).
