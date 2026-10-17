# 3D Render Test Dashboard

Measure how 3D web assets (GLB/GLTF) load and render in real browsers on real devices, compare them, and publish reports that answer one question with evidence.

- **Assets:** bulk upload with browser inspection, library, detail, edit, delete.
- **Performance Lab:** Side-by-Side Test (2–4 assets, linked panes) and Crowd Test (copies until the scene stops being Smooth), with Static, Spin, and Physics motion scenarios and Targets per device class.
- **Results:** history, test detail, Tool summary, JSON/CSV export, reports with an interactive public reproduction.

The specification is in [`docs/`](docs/README.md). Start with `docs/PRD.md` (what), `docs/BENCHMARK.md` (how it measures), and `docs/LOG.md` (what was built and decided).

## Stack

PHP 8.3 · Laravel 13 · MariaDB · Blade · Tailwind CSS 4 + Basecoat · Alpine.js · Three.js · cannon-es (physics) · uPlot (charts) · Vite. Production runs PHP and MariaDB only (Hostinger shared hosting); assets are built locally. See `docs/AGENT.md` §5 and `docs/DEPLOYMENT.md`.

## Local development

```bash
composer install
npm install
cp .env.example .env          # set DB_* (database render_lab), ADMIN_EMAIL, ADMIN_PASSWORD
php artisan key:generate
php artisan migrate
php artisan db:seed           # admin, tools, presets, device classes, Targets (no fixtures)
php artisan serve             # http://localhost:8000
npm run dev
```

Before uploading assets, an admin adds the research plan's objects in **Administration → Objects** (Object is required for every upload).

Local-only pages (`APP_ENV=local`): `/_ui` (UI kit), `/_viewer` (fixture viewer), `/_engine` (engine self-test on any fixture).

## Verification environment (tests and browser checks)

The working database `render_lab` and `storage/app/private/` hold real data. Tests and browser checks use an isolated environment instead: database `render_lab_verify`, storage `storage/verify/`, port 8001, `.env.verify` (template `.env.verify.example`).

```bash
bin/verify-db-setup.sh        # create/migrate/seed render_lab_verify and import fixtures/models/
bin/verify-serve.sh           # http://127.0.0.1:8001
php artisan test              # PHP feature and unit tests (against render_lab_verify)
npm run test:js               # pure benchmark logic (Node test runner)
npm run build                 # production assets; the entry bundle must not contain Three.js or cannon-es
```

The scripts refuse to run when the configured database is not `render_lab_verify`. Development models live in `fixtures/models/` (never user data; `node fixtures/models/generate-test-spheres.mjs` regenerates the synthetic spheres).

## Deployment

See `docs/DEPLOYMENT.md`. In short:

```bash
bin/make-release.sh           # release/<name>.tar.gz with app-core/ and public_html/
```

Upload and extract on the server, create `app-core/.env` from `.env.production.example`, then `php artisan migrate --force`, `php artisan db:seed --class=ProductionSeeder --force`, `php artisan optimize`, add the cron entry, and run the smoke test (`docs/DEPLOYMENT.md` §8).
