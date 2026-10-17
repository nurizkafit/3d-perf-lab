# DEPLOYMENT.md — Hosting requirements and deployment

This file is the authority for hosting requirements and the build and deploy procedure. The target is **Hostinger shared hosting** (the user's plan). Check each value marked **Confirm** in hPanel before M5.2, and record the result in `LOG.md`.

---

## 1. Principle

The production server runs **PHP and MariaDB only**. It does not run Node.js, a queue worker, or any other long-running process.

- Front-end assets are built with Vite on the developer machine or in CI, then uploaded.
- Scheduled work runs through one cron entry that calls Laravel's scheduler.

Reason: on Hostinger Business and Cloud plans, npm runs only inside Hostinger's Node.js app deployment; you cannot run npm commands through SSH. A Laravel app does not need Node.js at runtime.

---

## 2. Server requirements

| Item | Requirement | Confirm |
|---|---|---|
| PHP | 8.2 or later. Must meet the minimum of the chosen Laravel version. Select it in hPanel → PHP Configuration | Confirm version |
| PHP extensions | `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip` | Confirm all enabled |
| PHP functions | `proc_open`, `exec` are not required at runtime. Composer over SSH needs them in the CLI only | Confirm |
| Database | MariaDB 10.6+ or MySQL 8+ | Confirm version |
| Composer | Available over SSH (`composer --version`) | Confirm |
| SSH | Enabled in hPanel → SSH Access | Confirm port and user |
| Git | Available over SSH, or hPanel Git deployment | Confirm |
| Cron | hPanel → Cron Jobs, 1-minute frequency | Confirm minimum frequency |
| HTTPS | Free SSL enabled for the domain | Confirm |
| Disk | Enough for uploads: plan for (number of assets × average size × 1.1) + staging | Confirm quota and inode limit |

### PHP limits (set in hPanel → PHP Configuration → PHP Options)

| Setting | Value | Why |
|---|---|---|
| `upload_max_filesize` | ≥ the app maximum file size (default 50 MB) | One model per request |
| `post_max_size` | ≥ `upload_max_filesize` + 1 MB | Request overhead |
| `max_execution_time` | ≥ 60 | Registration of large batches |
| `memory_limit` | ≥ 256M | Hash and validation of large files |
| `max_file_uploads` | ≥ 20 | GLTF with dependent files |

The app reads its own maximum from `ASSET_MAX_UPLOAD_MB`. It must be **lower than or equal to** the PHP limits. The upload page shows the effective maximum.

---

## 3. Directory layout on the server

Laravel's `public/` folder must be the web root. Hostinger serves each domain from `domains/<domain>/public_html/`.

Recommended layout:

```text
~/domains/<domain>/
├── app-core/            ← Laravel project (app/, bootstrap/, config/, storage/, vendor/, .env …)
└── public_html/         ← contents of Laravel's public/ (index.php, build/, .htaccess, favicon)
```

Change `public_html/index.php` so that its two `require` paths point to `../app-core/vendor/autoload.php` and `../app-core/bootstrap/app.php`. Tell Laravel where the public folder is (`$app->usePublicPath(...)` in `bootstrap/app.php`, or the equivalent in the installed Laravel version).

Alternative (if the plan allows changing the document root to a subfolder): point the domain root to `app-core/public` and do not move files. **Confirm** whether hPanel allows this for the plan.

Never put `app-core/` inside `public_html/`.

---

## 4. Environment

Production `.env` (template: `.env.production.example` in the project root):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<domain>
APP_DISPLAY_TIMEZONE=Asia/Jakarta   # dates are stored in UTC and shown in this zone (LOG.md D38)
APP_KEY=                       # php artisan key:generate --show (locally), paste here

DB_CONNECTION=mariadb          # or mysql, per Laravel version
DB_HOST=localhost              # Confirm in hPanel → Databases
DB_PORT=3306
DB_DATABASE=<hostinger db name>
DB_USERNAME=<hostinger db user>
DB_PASSWORD=<password>

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database      # processed by the scheduler, no worker daemon
FILESYSTEM_DISK=local

ASSET_MAX_UPLOAD_MB=50
STAGING_RETENTION_DAYS=7

ADMIN_NAME=
ADMIN_EMAIL=
ADMIN_PASSWORD=                # used once by the seeder; remove after first login
```

---

## 5. Build and deploy procedure

### 5.1 First deployment

On the developer machine:

1–3. `bin/make-release.sh` builds the assets, copies the project without `.env` files, `node_modules/`, storage contents, `storage/verify/`, `fixtures/`, `docs/`, `tests/`, and development files, installs the production `vendor/` (`composer install --no-dev --optimize-autoloader`), and writes `release/<name>.tar.gz` with two folders: `app-core/` (with `.htaccess` = `Require all denied`, from `deploy/app-core.htaccess`) and `public_html/` (the contents of `public/` with `build/`, and the front controller from `deploy/public_html/index.php`, which points to `../app-core/` and calls `usePublicPath`).

On the server (SSH):

4. Upload the archive and extract it in `~/domains/<domain>/`: put `app-core/` next to `public_html/`.
5. Copy the contents of the archive's `public_html/` into the domain's `public_html/` (the `index.php` is already adapted, §3).
6. Create `app-core/.env` from the template.
7. (Only if `vendor/` was not uploaded) `composer install --no-dev --optimize-autoloader`.
8. `php artisan migrate --force`
9. `php artisan db:seed --class=ProductionSeeder --force` (admin, tools, presets, device classes, Targets — no fixtures, no objects).
10. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
11. Permissions: `storage/` and `bootstrap/cache/` writable by the PHP user (usually `755` folders, `644` files on Hostinger).
12. Add the cron entry (§6).
13. Run the smoke test (§8).

### 5.2 Updates

1. Build locally (`npm run build`).
2. Upload changed files (or `git pull` in `app-core/` and upload `public/build/` separately, because `public/build/` is not in git).
3. Copy changed files from `app-core/public/` into `public_html/` (at least `build/`).
4. `php artisan down` → `composer install --no-dev --optimize-autoloader` (if `composer.lock` changed) → `php artisan migrate --force` → `php artisan optimize` → `php artisan up`.

Optional later: a GitHub Actions workflow that builds and uploads through SSH. Ask before you add it.

### 5.3 Rules

- Never run `migrate:fresh`, `db:wipe`, or seeders other than `ProductionSeeder` on production.
- Never upload `fixtures/` or `docs/` to the server.
- Never set `APP_DEBUG=true` on production.

---

## 6. Cron

One entry, every minute (hPanel → Cron Jobs → custom command):

```text
/usr/bin/php /home/<user>/domains/<domain>/app-core/artisan schedule:run >> /dev/null 2>&1
```

**Confirm** the PHP binary path in hPanel (it can be a version-specific path).

Scheduled tasks (defined in the app):

| Task | Frequency |
|---|---|
| Delete staged files of abandoned upload sessions (`CleanupStagedUploads`, sync) | Daily |
| Mark running runs past their expiry as invalid: 30 minutes, or the estimated run time + 15 minutes when longer (`ExpireTestRuns`, sync; D59) | Every 5 minutes |

No queue worker runs: the scheduler runs the jobs on the sync connection. Laravel removes expired database sessions by its own lottery.

---

## 7. Storage and security on the server

- All uploads stay in `app-core/storage/app/private/`. No `storage:link` is needed.
- `public_html/.htaccess` is Laravel's default. Add: `Options -Indexes`.
- Add an `.htaccess` with `Require all denied` in `app-core/` as a second barrier in case of a wrong document root.
- Serve `.glb` with `Content-Type: model/gltf-binary` and `.gltf` with `model/gltf+json` (the app sets these in the streaming controller).
- Enable gzip/Brotli for `.gltf`, `.js`, `.css` where the host supports it. **Confirm**.
- Back up the database and `storage/app/private/` (hPanel backups or a scheduled export). **Confirm** the backup policy of the plan.

---

## 8. Smoke test (after each deployment)

1. `/` loads over HTTPS, fonts and icons render.
2. `/reports` loads (empty state is OK).
3. Log in as the admin. Change the seeded admin password.
4. Create a user. Log in as that user in a private window.
5. Upload two small GLB files with bulk upload; register them.
6. Run a Side-by-Side Test with 2 assets, 1 repetition, Static only; open the result.
7. Publish a report with one approved asset; open it logged out; the reproduction loads the model.
8. Unpublish; the public URL and the model token URL return 404.
9. Check `storage/logs/laravel.log` for errors.
10. Confirm that the cron runs (check the scheduler log line or the expired-runs job effect).

Record the result in `LOG.md`.
