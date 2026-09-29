# SecureFileShare

SecureFileShare is an educational file-sharing application built with vanilla PHP 8.1+ and a small MVC-style structure. It lets users manage files and share them through expiring links, while keeping the implementation approachable for learning and extension.

> **Educational project:** This starter is not production-ready. Review the security configuration and harden the application before using it with sensitive files or exposing it to the internet.

## Features

- Register, sign in, update a profile, and view a storage-usage dashboard.
- Upload files with drag-and-drop, organize them with folders and tags, and search their names and metadata.
- Encrypt uploaded file contents at rest with AES-256-CBC and record SHA-256 checksums for integrity checks on owner downloads.
- Create share links with an optional password, an expiration period, and revocation.
- Track account and file activity.
- Explore a small JWT-based API example in `public/api.php`.
- Use SQLite by default, with a MySQL schema available.

Uploads are currently limited to 25 MiB and an allow-list of MIME types configured in `config/app.php`. New accounts have a 50 MiB storage quota by default.

## Get started

### Requirements

- PHP 8.1 or later.
- PHP extensions: PDO with SQLite (`pdo_sqlite`), OpenSSL, and Fileinfo.
- Write access to `database/` and `storage/uploads/`.

Composer is not required to run the app: `bootstrap.php` registers a small autoloader. The `composer.json` file declares the PHP requirement and optional PSR-4 autoload configuration.

### Run locally

From the project root, start PHP's development server:

```bash
php -S localhost:3000 -t public
```

Open [http://localhost:3000](http://localhost:3000). SQLite is the default; if the configured database does not exist or has not been initialized, the application creates it from `database/schema.sqlite.sql` on its first request.

1. Register at `/register`, then sign in at `/login`.
2. Open **My Files** (`/files`) to upload a file, optionally adding a folder name and comma-separated tags.
3. Create a share link from a file row. Set an optional password and an expiration from 1 to 30 days.
4. Manage and revoke links from the dashboard.

The SQLite schema seeds a demo administrator:

| Email | Password |
| --- | --- |
| `admin@example.com` | `admin123` |

This account is for local demonstrations only. Change or remove it before deployment, and do not expose a default installation to the public internet.

### Configuration

The active settings are in [`config/app.php`](config/app.php). In particular, set unique, secret values for `security.app_key` and `security.jwt_secret` before use beyond local development. Changing the encryption key makes existing encrypted uploads unreadable, so keep it safe and backed up.

The `.env.example` file lists example settings but is not loaded by the application. Update `config/app.php` to change the database driver, credentials, upload limits, or storage path.

### Optional MySQL setup

1. Create the database and tables by importing [`database/schema.sql`](database/schema.sql) into MySQL 8+.
2. Change the `db` settings in `config/app.php` to use the `mysql` driver and your database credentials.
3. Ensure `pdo_mysql` is enabled in PHP.

## Project layout

- `app/Controllers/` — request handlers
- `app/Core/` — routing, authentication, database, CSRF, sessions, and encryption
- `app/Models/` — database access
- `app/Views/` — page templates
- `config/app.php` — application configuration
- `database/` — SQLite and MySQL schemas and the local SQLite database
- `public/` — web document root, entry points, and static assets
- `storage/uploads/` — encrypted uploaded file contents

When deploying, configure the web server's document root to `public/`; do not serve the project root, `app/`, `config/`, `database/`, or `storage/` directly.

## Help and documentation

This README and the source code are the project documentation; there is no separate documentation site. For questions, bug reports, or feature requests, [open an issue](https://github.com/VoidLance/course-files-php-securefileshare/issues). For questions about a specific feature, include the relevant route or file and steps to reproduce the issue.

The project is maintained by [@VoidLance](https://github.com/VoidLance).

## Contributing

Contributions are welcome. Open an issue to discuss larger changes, then submit a focused pull request with a clear description and any relevant manual test steps. Keep changes suitable for the project's educational scope and update this README when setup or user-facing behavior changes.

There is no separate contribution guide or license file in this repository.
