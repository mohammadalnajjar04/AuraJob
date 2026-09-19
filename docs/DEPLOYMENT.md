# AuraJob Deployment Guide

This guide covers a production-oriented deployment of AuraJob. The application is plain PHP with MySQL and does not require a framework or JavaScript build step.

## 1. Server Requirements

- PHP 8.1+
- MySQL 8+
- PHP extensions: `mysqli`, `openssl`
- HTTPS-enabled web server (Apache, Nginx, or equivalent)
- A deployment account that can write only to the directories that genuinely need runtime writes

## 2. Use `public/` as the Document Root

The web server document root must point to the project's `public/` directory, not the repository root.

Example layout:

```text
/var/www/aurajob/
├── app/
├── database/
├── docs/
├── public/        <- web document root
├── storage/
└── .env
```

This keeps `.env`, application internals, SQL files, documentation, and runtime storage outside direct HTTP access.

## 3. Create the Database

Import the combined submission file into a fresh database server:

```bash
mysql -u root -p < database/database.sql
```

For production, create a dedicated application user with only the permissions AuraJob needs:

```sql
CREATE USER 'aurajob_app'@'localhost' IDENTIFIED BY 'use-a-strong-unique-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON aurajob_db.* TO 'aurajob_app'@'localhost';
FLUSH PRIVILEGES;
```

Do not reuse the example password above.

## 4. Configure Environment Variables

Copy `.env.example` to `.env` on the server and set real values:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=aurajob_db
DB_USER=aurajob_app
DB_PASS=<production-database-password>
APP_ENCRYPTION_KEY=<64-hex-character-key>
```

Generate the encryption key with PHP:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Never commit or publish `.env`.

## 5. Production PHP Settings

Recommended production behavior:

- `display_errors = Off`
- `log_errors = On`
- keep PHP and extensions patched
- use HTTPS so session cookies receive the `Secure` flag
- configure a private server-side PHP error log

Do not show stack traces, SQL errors, or secrets to visitors.

## 6. Filesystem Permissions

Application source code should normally be read-only to the web-server process.

`storage/uploads/` is reserved for runtime uploads. The current release does not expose an upload workflow, so it does not require public write access for normal use. If an upload feature is enabled later, grant the narrowest required write permission only to this directory and keep it outside `public/`.

## 7. HTTPS and Domain

Serve AuraJob over HTTPS in production. Point the domain's virtual host/server block to `public/`.

No framework router is required: the application uses physical PHP routes such as:

```text
/jobs.php
/applicant/dashboard.php
/employer/dashboard.php
```

## 8. Pre-Launch Checklist

Before making the site public:

1. Import the intended production database data.
2. Create a fresh `.env` with production-only credentials.
3. Generate a unique `APP_ENCRYPTION_KEY` and back it up securely.
4. Confirm `.env` is not tracked by Git.
5. Confirm the server document root is `public/`.
6. Enable HTTPS.
7. Disable browser-visible PHP errors.
8. Test registration, login, logout, applicant authorization, employer authorization, job search, applications, Saved Jobs, notifications, and destructive POST actions.
9. Verify CSRF-protected actions still work behind the production domain.
10. Take a database backup before launch and establish a backup routine.

## 9. Smoke Test After Deployment

Verify these public routes first:

```text
/
/jobs.php
/login.php
/register.php
```

Then verify one applicant account and one employer account end-to-end.

Also confirm that requests for repository-internal paths do not expose raw files, including `.env`, `app/`, `database/`, `docs/`, and `storage/`.


## Branded Error Pages

AuraJob includes public `404.php` and `500.php` pages. On Apache, the bundled
`public/.htaccess` maps HTTP 404/500 responses to these branded pages when
`AllowOverride FileInfo` is enabled.

For Nginx, add equivalent rules inside the server block:

```nginx
error_page 404 /404.php;
error_page 500 502 503 504 /500.php;
```

Keep detailed PHP/database errors in server logs only; never expose stack traces
or database messages to visitors.


## Social Sharing Metadata

AuraJob's public Home, Find Jobs, and Job Details pages include canonical,
Open Graph, and Twitter Card metadata. The shared social preview image is:

```text
/public/assets/images/aurajob-social-preview.jpg
```

Because absolute metadata URLs are built from the current HTTPS/host request,
configure the production domain correctly and force HTTPS before launch.


## Company Logo Uploads

Employer company logos are stored outside the public web root under:

```text
storage/uploads/company-logos/
```

The directory must be writable by the PHP/web-server process. Logos are
validated as PNG, JPEG, or WebP images, limited to 2 MB, renamed to random
server-generated filenames, and served through `public/company-logo.php`.

Do not point the web server directly at `storage/`.
