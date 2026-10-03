# LOG.md — Implementation history

The agent updates this file at each milestone boundary and after each decision. Format: `AGENT.md` §12. Newest entry at the top of "History".

---

## Current status

| Item              | Value                                   |
| ----------------- | --------------------------------------- |
| Current milestone | M2.2 — Administration                   |
| Status            | Completed — waiting for user review; M3.1 not started |
| Last updated      | 2026-10-03                              |

---

## Decisions made before implementation

These decisions were made by the user during the specification review. They are already reflected in the specification files.

| #   | Decision                                                                                                                                                                                                | Where                                  |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------- |
| D1  | Clean-slate project. The old "delivered" notes do not apply                                                                                                                                             | All files                              |
| D2  | Docs in `docs/`; root `CLAUDE.md` imports `docs/AGENT.md`; new `BENCHMARK.md` and `DEPLOYMENT.md`                                                                                                       | `docs/README.md`                       |
| D3  | Dev test models in `fixtures/models/`, separate from user uploads; never deployed                                                                                                                       | `fixtures/models/README.md`            |
| D4  | Fonts: Funnel Display (headings), Atkinson Hyperlegible Next (body)                                                                                                                                     | `DESIGN.md` §3                         |
| D5  | Style: Soft Modern Brutalism — brutalist signals, soft surfaces                                                                                                                                         | `DESIGN.md` §1                         |
| D6  | Components: Basecoat on Tailwind CSS. Micro-interactions: Alpine.js + CSS                                                                                                                               | `AGENT.md` §5                          |
| D7  | Icons: Phosphor, Bold weight                                                                                                                                                                            | `DESIGN.md` §6                         |
| D8  | Hosting: Hostinger shared hosting; assets built locally/CI; no Node.js on the server                                                                                                                    | `DEPLOYMENT.md`                        |
| D9  | Decision support: budgets only (pass/fail). No weighted view, no score, no ranking                                                                                                                      | `BENCHMARK.md` §6                      |
| D10 | MVP methods: Comparison, Stress, Headroom. Later: Camera Path, Device Matrix, Visual Check                                                                                                              | `BENCHMARK.md` §4, §8; `PROJECT.md` §4 |
| D11 | Target devices: desktop and mobile. Device class from an admin-managed list; auto-detection suggests only; no free-text labels                                                                          | `BENCHMARK.md` §7                      |
| D12 | Budget presets managed by admin; user selects and adjusts per test; test stores a snapshot                                                                                                              | `PRD.md` §6.3                          |
| D13 | Admin "dropdown" management = option lists used in forms (tools, presets, device classes, budgets)                                                                                                      | `PRD.md` §11                           |
| D14 | Bulk upload table columns: thumbnail, file name, asset name, author, tool, production time, format, file size; bulk apply for author, tool, average production time; asset name defaults from file name | `PRD.md` §7.1                          |
| D15 | Description is optional (in the expanded row), because it is not in the agreed table columns                                                                                                            | `PRD.md` §7.1                          |
| D16 | One upload flow for one or many files; no separate single-file form                                                                                                                                     | `PRD.md` §7.1                          |
| D17 | Reports answer one question: question → budget result → evidence → limitations → author's conclusion                                                                                                    | `PRD.md` §10                           |
| D18 | Public reproduction shows recorded results only; it does not measure the visitor                                                                                                                        | `PRD.md` §10.5                         |
| D19 | (M0.1, Q1) Hostinger PHP is 8.3. Use Laravel 13                                                                                                                                                         | `AGENT.md` §5.1                        |
| D20 | (M0.1) New databases `render_lab` (working) and `render_lab_verify` (verification). The old `perf_lab` and `perf_lab_verify` belong to an older project; do not use or modify them                     | `AGENT.md` §9; `PROJECT.md` §3          |
| D21 | (M0.1, Q2) Numeric data uses Atkinson Hyperlegible Mono (`@fontsource-variable/atkinson-hyperlegible-mono`)                                                                                             | `DESIGN.md` §3; `AGENT.md` §5.2        |
| D22 | (M0.2) Budget presets link to device classes many-to-many through `budget_preset_device_class`, replacing `budget_presets.device_class_id`                                                               | `PRD.md` §11, §12                      |
| D23 | (M0.2) Seed one default camera (Auto-frame), lighting (Studio), and background (Neutral, `neutral-100`) preset                                                                                          | `PRD.md` §11                           |
| D24 | (M0.2) `min_instances_at_budget` is seeded disabled with no limit in all budget presets                                                                                                                  | `BENCHMARK.md` §6.2                    |
| D32 | (before M2.2) Add `users.last_login_at`, set on each successful login, shown in the admin user list | `PRD.md` §12 |
| D29 | (M1.3 review) Results and report screens show tested-asset metadata; asset columns name tool/format/author | `PRD.md` §8.4, `DESIGN.md` §9.4 |
| D30 | (M1.3 review) Sidebar groups are headings, not collapsible menus; every item visible with its own icon | `SITEMAP.md` §5, `DESIGN.md` §6, §8.1 |
| D31 | (M1.3 review) Budget items and metrics show a one-line explanation next to the label | `DESIGN.md` §9.4 |
| D28 | (Q6) Placeholder text uses `neutral-600` (5.45:1 on white) | `DESIGN.md` §2.1 |
| D27 | New neutral scale 50–950 (adds 500 and 700): one OKLCH ramp from `#F8FAFC` to `#1F2128`. Text `neutral-950`; shadows and strong borders `neutral-800`; dark fills `neutral-700`; `neutral-900`/`950` not used for components. Replaces D26 | `DESIGN.md` front matter, §2 |
| D26 | (after M1.2) Hard shadows, strong borders, and dark fills use `neutral-800`; no non-text color darker than `neutral-900`; `neutral-950` is text only. Benchmark background is `neutral-800` | `DESIGN.md` §1, §2, §9, §10 |
| D25 | (M0.2) Local first admin uses the user's email with a generated password stored only in the local `.env` / `.env.verify`                                                                                | `config/lab.php`                       |

## Open decisions (ask at the stated milestone)

| #   | Question                                                                                                | Milestone |
| --- | ------------------------------------------------------------------------------------------------------- | --------- |
| Q3  | About page text and contact methods (M1.2 uses marked placeholders until supplied)                     | Before M5.2 |
| Q4  | Chart library (candidate: uPlot)                                                                        | M4.1      |
| Q5  | Hostinger "Confirm" items in `DEPLOYMENT.md`                                                            | M5.2      |

---

## History

## 2026-10-03 — M2.2 Administration — completed

**Scope:** User management (list, create, edit, role change, deactivate/activate, reset password, guards); option list management for all eight lists (create, edit, deactivate, reorder, set default; structured forms); budget preset form with item enable flags and limits. Also before M2.2: `last_login_at` (D32).

**Done:**
- Migration `2026_10_03_000500_add_last_login_at_to_users_table` (run on `render_lab` and `render_lab_verify`; adds one nullable column, no data change). `LoginController` stores the time after each successful login.
- `Admin\UserController` + Form Requests `StoreUserRequest`, `UpdateUserRequest`, `ChangeUserStatusRequest`, `ResetPasswordRequest` (each authorizes through `UserPolicy`). Admin sets a temporary password (Laravel `Password::defaults()`, at least 8 characters) on create and reset. Guards: no self-deactivation (policy, 403); the last active admin cannot be demoted or deactivated (validation error).
- `App\Support\OptionLists`: one definition per list (model, type, default support), structured validation rules, input → stored values, form values, one-line summary, and the "in use" check. `Admin\OptionListController` + `OptionItemRequest` / `OptionActionRequest`, authorized by the `manage-option-lists` gate (admins).
- Actions: create, edit, deactivate, activate, set default (one per list; only active items), reorder (up/down, positions rewritten 0..n), delete (only when not in use and not the default). The default cannot be deactivated.
- Structured forms (`components/admin/option-form-fields`): camera (FOV, position mode, distance, elevation, azimuth), lighting (ambient, 1–3 directional lights with add/remove, environment map), background (design-token select), resolution (width × height), instance steps (comma-separated ascending counts, 1–500), device class (category, platform), budget preset (device class checkboxes + every budget item with enable flag, limit, unit, and explanation). No JSON fields.
- Pages: `admin/users/{index,create,edit}`, `admin/options/{index,form}`. The app layout shows the result of the last action (`status`) and action errors. Sample admin data removed from `SampleScreens`; the sidebar reads list names from `OptionLists`.

**Decisions:**
- D32 — `last_login_at` — user.
- Exactly one default per list that has defaults; the default cannot be deactivated or deleted; only an active item can become the default (`PRD.md` §11 updated) — agent; reason: the Lab needs a usable default for every list.
- "In use" definition: tools (assets, test tool constraint), device classes (test runs, budget preset links); presets never, because tests store copies (`PRD.md` §11 updated) — agent, from the existing snapshot rule.
- Field ranges in the preset forms (for example FOV 10–120°, resolution 320–3840 px, light intensity 0–20) — agent-routine validation; they do not change any default or metric.
- Option lists authorize through a gate (`manage-option-lists`) because they span four models — agent-routine.
- No forced password change after a temporary password (not in the spec) — agent-routine; can be added later.

**Checks:**
- `php artisan test`: 82 passed (393 assertions). New: `AdminUsersTest` (11) — list with last login; admin creates a user who can then log in (and `last_login_at` is set); validation; edit; last active admin cannot be demoted; self-deactivation 403; deactivate another admin; deactivate/activate; reset password; a user cannot manage accounts. `AdminOptionListsTest` (16) — every list page and form renders, no JSON fields, sidebar links to all eight lists, **a tool used by a seeded fixture asset (Blender) cannot be deleted, only deactivated**, a device class linked to a seeded budget preset cannot be deleted, an unused item can be deleted, structured camera/lighting/instance-step/budget input stored correctly, validation errors, enabled budget item needs a limit, set default, default cannot be deactivated, reorder, a user gets 403.
- Browser (local, view only): users, user edit, tools, budgets, budget edit, lighting and camera forms at 1440 and 384 wide — no console errors, no horizontal page scroll. Lighting form: lights 1 → 2 → 3 → 2; Add is disabled at 3; hidden rows are disabled so they are not submitted.
- Pint: passed. `npm run build`: success.

**Issues:**
- Number inputs show the browser's locale decimal separator (for example `16,7`); the stored value is correct. Revisit if users find it confusing.
- In the local working database `render_lab` no asset exists yet, so all tools show Delete there; the "in use" rule is proven in `render_lab_verify` with the fixture assets.

**Files:** `database/migrations/2026_10_03_000500_add_last_login_at_to_users_table.php`, `app/Support/OptionLists.php`, `app/Http/Controllers/Admin/{UserController,OptionListController}.php`, `app/Http/Requests/Admin/*`, `app/Models/User.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php`, `resources/views/pages/admin/**`, `resources/views/components/admin/option-form-fields.blade.php`, `resources/views/layouts/app.blade.php`, `resources/views/components/navigation/sidebar.blade.php`, `resources/css/components.css`, `tests/Feature/{AdminUsersTest,AdminOptionListsTest,AppScreensTest}.php`, `docs/{PRD,SITEMAP}.md`.

**Next:** M3.1 — Viewer foundation (not started).

## 2026-10-03 — M2.1 Authentication and roles — completed

**Scope:** Login, logout, login rate limit, "account deactivated" message; `role` and `is_active` middleware; policies for assets, tests, reports, users, upload sessions; remove the temporary no-login route group and protect all app routes.

**Done:**
- `GET/POST /login` (guest only), `POST /logout`; no registration route. `Auth\LoginController`, `Auth\LoginRequest` (validation, rate limit, deactivated check). `layouts/auth.blade.php`, `pages/auth/login.blade.php`.
- Rate limit: 5 failed attempts per email + IP, then a 60-second lockout with the message "Too many login attempts. Try again in N seconds."
- Deactivated accounts: after a correct password the login shows "This account is deactivated. Ask an administrator to reactivate it."; with a wrong password the normal message shows, so the deactivated message never confirms an account. `EnsureActiveUser` (`active`) signs out an account deactivated during its session.
- `EnsureRole` (`role:admin`) returns 403. Aliases and the guest/user redirects are in `bootstrap/app.php`.
- Policies (auto-discovered): `AssetPolicy`, `TestSessionPolicy`, `ReportPolicy`, `UserPolicy`, `UploadSessionPolicy`, following `PRD.md` §4 and `SITEMAP.md` §3. Models `TestSession`, `Report`, `UploadSession` added (fillable and casts only). `User::isAdmin()`, `User::owns()`.
- Routes: all app routes behind `auth` + `active`; `/admin/*` also behind `role:admin`. The temporary local/testing-only group from M1.3 is removed. Screens still show sample data until their milestones.
- Header shows the signed-in user (email and role) and a working Log out; the sidebar shows Administration and Option lists to admins only; the public header links to Log in, or to Dashboard when signed in.
- `User` model and factory default to `role = user`, `is_active = true`, matching the database defaults.

**Decisions:**
- Rate limit values (5 attempts, 60 s, per email + IP), Laravel's usual login throttle — agent-routine; `PRD.md` says "rate limited" without numbers.
- Upload sessions are visible to their creator only, not to admins (`SITEMAP.md` §3.2 "Session owner") — agent, following the spec literally.
- Permanent-delete blocking (published report) and the "last active admin" guard are left to M3.3 and M2.2, where those flows are built; the policies note it.
- No "remember me" option (not in the spec) — agent-routine.

**Checks:**
- `php artisan test`: 55 passed (262 assertions), including: guests redirected from all 14 app routes; no `/register`; active login lands on the dashboard; wrong password rejected; deactivated user cannot log in and sees the message; deactivated-during-session signs out; rate limit after 5 failures; logout; a user gets 403 on `/admin/users` and `/admin/options/tools`; a user does not see the admin menu; policy matrix for each ability (owner / other user / admin).
- Browser (headless Chrome, local `render_lab`, seeded admin): guest `/dashboard` → `/login`; wrong password shows the error under Email; correct login → `/dashboard`; user menu Log out → `/`; `/dashboard` afterwards → `/login`. No console errors.
- Pint: passed. `npm run build`: success.

**Issues:**
- Resolved by D32 (`last_login_at`).

**Files:** `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Middleware/{EnsureRole,EnsureActiveUser}.php`, `app/Policies/*`, `app/Models/{User,TestSession,Report,UploadSession}.php`, `bootstrap/app.php`, `routes/web.php`, `database/factories/UserFactory.php`, `resources/views/layouts/auth.blade.php`, `resources/views/pages/auth/login.blade.php`, `resources/views/components/navigation/{header,sidebar,public-header}.blade.php`, `app/Support/SampleScreens.php`, `tests/Feature/{AuthenticationTest,PolicyTest,AppScreensTest,PublicSiteTest}.php`.

**Next:** M2.2 — Administration (not started).

## 2026-10-03 — M1.3 Dashboard shell and screens — completed (approved by the user)

**Scope:** App shell (collapsible sidebar, off-canvas on small screens, header with device class chip), benchmark shell, and static layouts with sample data for all app screens; temporary no-login route group. Also before M1.3: placeholder color (Q6 → D28).

**Done:**
- `layouts/app.blade.php` (`<x-layouts::app title description :sample>` with an `actions` slot for the page header), `layouts/benchmark.blade.php` (`data-benchmark="running"`, no sidebar, header, or toasts).
- `navigation/sidebar` (groups Assets, Performance Lab, Option lists as `<details>`; rail mode shows group icons that link to the group's main page; admin section), `navigation/header` (phone menu button, device chip, user menu), `performance/device-chip` (warning chip "Device class not set" until M4.1 stores a class).
- `resources/js/features/app-shell.js` (collapse state in `localStorage`, drawer open/close, Escape closes and returns focus), `resources/js/features/upload.js` (bulk upload table behaviour).
- Screens (all with the `Sample data` badge except the Lab method page, which has none): dashboard, device confirmation, asset library (grid and table via `?view=`; GET filter form), bulk upload, asset detail, asset edit, Lab method page, Comparison configuration (4 numbered steps + run bar), benchmark mode, test history, test detail, report editor, admin users, admin option list (all eight lists).
- Components: `performance/metric-card`, `performance/step-card`, `assets/asset-card`, `assets/upload-dropzone`, `assets/upload-table`.
- `App\Support\SampleScreens` holds the sample data (never stored).
- Temporary routes in `routes/web.php`, registered only when `APP_ENV` is `local` or `testing`; removed in M2.1.
- Items without a screen yet (Stress, Headroom, Log out, save/publish/register actions) are disabled with "Coming in M#" or "arrives in M#" text; nothing links to a 404.

**Decisions:**
- D28 — placeholder text `neutral-600` — user.
- Sidebar built with Alpine + CSS instead of Basecoat's sidebar, which has no icon-rail mode; DESIGN.md §8.1 wording updated — agent (implementation detail; visuals follow DESIGN.md). Flagged to the user.
- Temporary routes also registered in `testing` so feature tests can render every screen; never in production — agent-routine.
- Bulk apply protects a row from the next bulk apply after any hand edit in that row (name, author, tool, time, description); "Overwrite edited rows" re-applies to the skipped rows only — agent, following PRD.md §7.1 literally.
- Checkbox and radio inputs keep Basecoat sizing; their border is `neutral-600` for 3:1 contrast — agent-routine.

**Checks:**
- Bulk apply on sample rows (headless Chrome): Apply to selected changed only the two selected rows; Apply to all skipped the hand-edited row with the message "Applied to 2 rows. 1 edited row was not changed."; Overwrite edited rows updated it; the invalid row cannot be selected; remove/undo updates the staged count; changed cells flash once (`.cell-flash` present at 60 ms, gone at 560 ms).
- Sidebar: collapse to a 3.5rem icon rail, state kept after reload; phone drawer opens from the header button, focus moves into it, Escape closes it and returns focus to the button.
- Screens at 1440, 820, and 384 wide: no console errors, no horizontal page scroll (after fixing the dashboard grid).
- Keyboard: every Tab stop on dashboard, upload, comparison, users, and report editor has a visible focus style.
- Lighthouse accessibility: dashboard 100, upload 100, comparison 100, test detail 100.
- `php artisan test`: 28 passed (121 assertions). Pint: passed. `npm run build`: success. No `px` design values and no raw colors in `resources/`.

**User review (round 1) and changes:**
1. "The result report didn't show the metadata from the assets." → Test detail and report editor start with a "Tested assets" table (`assets/tested-assets`: order, name, tool, author, format, file size, vertices, triangles, production time, uploaded by). Budget-table and metric-table column headers show tool, format, and author. Report editor approval list shows tool · author · format · size (D29).
2. "Don't hide menu under collapsible menu." → Sidebar rebuilt with section headings; every item always visible with its own icon; in the rail, headings become dividers (D30).
3. "/lab/comparison: titles should align with the content; more left padding before the asset numbers." → Basecoat's `.card > header` side padding removed for our cards; step content indented by the number width + gap (2.75rem, from `sm`); asset rows have `ps-4` (D-routine).
4. "Information about budget items and runtime metrics." → `App\Support\MetricDefinitions` holds labels, units, rules, applicable methods, and one-line explanations; shown under each budget item (budget step, budget tables, public report) and each runtime metric, plus a "How to read this table" note on test detail (D31).
- Also fixed: the Comparison budget step listed "Headroom instances", which applies to Headroom only (BENCHMARK.md §6.1). It now lists the seven items that apply to Comparison, with Desktop — Standard seed limits.
- Checks after the changes: screens at 1440 and 384 wide without errors or horizontal page scroll; `php artisan test` passed; build passed.

**Approval:** the user approved the M1.3 layouts on 2026-10-03.

**Issues:**
- None open from the layout review.
- Number inputs show the browser's locale decimal separator (for example `16,7` in a German-locale browser). Decide input locale handling when forms become real (M2.2/M4.1).

**Files:** `routes/web.php`, `app/Support/SampleScreens.php`, `resources/views/layouts/{app,benchmark}.blade.php`, `resources/views/components/{navigation,performance,assets}/*`, `resources/views/pages/{dashboard,device,assets,lab,results,admin}/*`, `resources/js/features/{app-shell,upload}.js`, `resources/js/app.js`, `resources/css/components.css`, `tests/Feature/AppScreensTest.php`, `docs/DESIGN.md`.

**Next:** M2.1 — Authentication and roles (not started).

## 2026-10-03 — Visual change: new neutral scale (D27) — completed

**Scope:** User feedback: no `neutral-500`/`neutral-700`, and `neutral-900`/`neutral-950` were too close. Rebuild the neutral scale from `#F8FAFC` to `#1F2128`; keep component shadows, borders, and fills around `neutral-700`–`neutral-800`.
**Done:**
- Scale generated in OKLCH: lightness spacing follows Tailwind's slate ramp, rescaled between the two end colors; hue and chroma interpolated between the ends. New values: 50 `#F8FAFC`, 100 `#F3F5F8`, 200 `#E7EAEE`, 300 `#D5DADF`, 400 `#A5ADB7`, 500 `#7D8591`, 600 `#636A76`, 700 `#525763`, 800 `#3E424C`, 900 `#2F323A`, 950 `#1F2128`. `neutral-0` stays `#FFFFFF`.
- Primary text moved from `neutral-900` to `neutral-950` (same color as before, `#1F2128`), in CSS, Basecoat foreground variables, and views.
- Shadows and strong borders stay on `neutral-800` (now `#3E424C`, lighter than before). Dark fills (`Sample data` badge, skip link, step squares) use `neutral-700`.
- DESIGN.md: token values, color usage table (adds 500 and 700), §2.2 rule, §9.2 step square. Kit page `/_ui` shows all shades.
**Decisions:** D27 — user (scale and range); mapping of 700 to fills and 800 to borders/shadows — agent, within the user's stated 700–800 range.
**Checks:** contrast — `neutral-600` on `neutral-100` 4.99:1 and on white 5.45:1 (secondary text, AA); white on `neutral-700` 7.24:1; white on `neutral-800` 10.06:1. `npm run build` success. `php artisan test` 22 passed. Lighthouse accessibility: Landing 100, Gallery 100, Report detail 100.
**Issues:** placeholder `neutral-400` is now 2.27:1 on white (Q6). `neutral-500` (3.73:1) is a new middle option for Q6. Input border `neutral-300` is 1.41:1 on white, the same situation as before; WCAG 1.4.11 asks 3:1 for control boundaries — follow-up for M5.1.
**Files:** `resources/css/tokens.css`, `resources/css/components.css`, `resources/views/**` (text color class), `resources/views/dev/ui.blade.php`, `docs/DESIGN.md`.
**Next:** M1.3 — Dashboard shell and screens (not started).

## 2026-10-03 — Visual change: lighter dark tone (D26) — completed

**Scope:** User feedback after M1.2: the hard shadow was too dark.
**Done:** `--shadow-hard-*` now use `neutral-800`. Strong borders, dark fills (`Sample data` badge, skip link), selected tab and focus borders, and the dialog backdrop use `neutral-800`. Text colors unchanged (`neutral-900`). DESIGN.md updated: shadow tokens, §1.1, §1.4, §2.1, §2.2 rule, §9.2 step square, §9.3 benchmark background (`neutral-950` → `neutral-800`), §10 chart series.
**Decisions:** D26 — user.
**Checks:** `npm run build` success; `php artisan test` 22 passed; screenshots of `/_ui` and `/reports` reviewed; no non-text use of `neutral-900`/`neutral-950` left in `resources/css` (text colors only).
**Files:** `resources/css/tokens.css`, `resources/css/components.css`, `docs/DESIGN.md`.
**Next:** M1.3 — Dashboard shell and screens (not started).

## 2026-10-03 — M1.2 Public site — completed

**Scope:** Public shell (header, footer), Landing, report gallery with search and filters, report detail layout, About, Contact, with sample data and empty/loading/error states.

**Done:**
- `layouts/public.blade.php` with skip link, `navigation/public-header` (desktop links, phone menu with Alpine toggle, `aria-current` on the active page), `navigation/public-footer`. Layouts are an anonymous component namespace: `<x-layouts::public>` (registered in `AppServiceProvider`).
- Routes: `/`, `/reports`, `/reports/{slug}`, `/about`, `/contact` (`routes/web.php`). Laravel's stock `welcome.blade.php` removed.
- `Public\ReportController`: gallery search (`q`, title and question) and filters (`method`, `device` from active device classes) by query parameters, plain GET form (works without JavaScript); invalid values are ignored. Detail returns 404 for an unknown slug.
- `App\Support\SampleReports`: four sample reports (Comparison, Stress repeated, Headroom, Stress mixed) using the public fields allowed in `SITEMAP.md` §7. Never stored. Every gallery card and the detail page show the `Sample data` badge.
- Report detail sections in `DESIGN.md` §12 order: question lead, budget result table, interactive reproduction (placeholder viewer frame with the label and the "device is not measured" note), evidence (metric cards + chart placeholder), asset characteristics, configuration, environment, limitations (standard list from `BENCHMARK.md` §9 + author's), conclusion.
- Components: `reports/report-card`, `reports/report-section`, `performance/budget-table`, `performance/viewer` (container only; Three.js mounts here from M3.1).
- States: gallery empty state (no match); loading (skeleton cards) and error states for the gallery and the detail viewer, previewed with `?_state=loading|error` in the local environment only.
- About: section-anchor sidebar + readable content, marked `Placeholder text`. Contact: no contact data, marked `Placeholder` (Q3).
- Header shows "Log in" as a disabled item ("Coming in M2.1"), so it never links to a 404.

**Decisions:**
- About and contact content not supplied; marked placeholders used as `PROJECT.md` M1.2 allows — user (by not supplying), agent placed placeholders.
- `?_state=` preview parameter for loading/error states, honored only when `APP_ENV=local` — agent-routine.
- Layouts as the `layouts::` anonymous component namespace so they stay in `resources/views/layouts/` (`SITEMAP.md` §4/§6) — agent-routine.
- Mixed-asset sample uses a second dummy Blender asset ("Apple, low poly") because a mixed scenario needs two assets of one tool — agent-routine (sample data only).

**Checks:**
- All public routes return 200 without login; unknown slug 404 (feature tests).
- Headless Chrome at 1280, 768, 384 wide on landing, gallery, detail, about, contact, empty, loading, and error states: no console errors, no horizontal page scroll.
- Lighthouse 12 accessibility (one-off `npx`, not a project dependency): Landing 100 (mobile and desktop), Gallery 100 (mobile and desktop), Report detail 100 (mobile and desktop).
- Keyboard: first Tab reaches the skip link, Enter moves focus to `main`; every Tab stop has a visible focus style; the filter form submits with Enter; the phone menu opens with the keyboard.
- `php artisan test`: 22 passed (73 assertions). Pint: passed. `npm run build`: success. No `px` design values and no raw colors in `resources/`.

**Issues:**
- On phones the landing content is taller than one viewport (three stacked cards). The page uses `min-height: 100svh − header` and fits on tablet and desktop.
- Q3 (About text, contact methods) and Q6 (placeholder color) are still open.

**Files:** `app/Http/Controllers/Public/ReportController.php`, `app/Support/SampleReports.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php`, `resources/views/layouts/public.blade.php`, `resources/views/pages/public/*`, `resources/views/components/{navigation,reports,performance}/*`, `resources/css/components.css`, `tests/Feature/PublicSiteTest.php`.

**Next:** M1.3 — Dashboard shell and screens (not started).

## 2026-10-03 — M1.1 Design system and UI kit — completed

**Scope:** Tokens, Basecoat theme, brutalist overrides, motion rules, Blade UI components, local-only kitchen-sink page `/_ui`. Also (before M1.1, at the user's request): dummy metadata for the fixture models.

**Done:**
- Fixture register (`fixtures/models/README.md`) now lists `blender/apel.glb` (Apple), `prisma3d/ayam.glb` (Chicken), `spline/buku.glb` (Book) with dummy authors and production times. `bin/verify-db-setup.sh` registered all three in `render_lab_verify`; a second run added nothing.
- `resources/css/tokens.css`: every DESIGN.md token as a Tailwind theme value (colors, fonts, type scale, radius, shadows, easing) or CSS variable (borders, durations, layout sizes), and Basecoat's variables mapped onto them. Tailwind's default neutral scale and Basecoat's px-based radius values are replaced.
- `resources/css/components.css`: brutalist overrides for button, card, input/select/textarea, badge, table, dialog, dropdown/popover, tabs, toast, empty state; `.section-label`, `.num`, `.skeleton`, `.cell-flash`; reduced-motion rules; `html[data-benchmark="running"]` kill switch.
- Blade components in `resources/views/components/ui/`: `icon`, `button`, `badge`, `card`, `input`, `textarea`, `select`, `field-messages`, `dialog`, `dropdown`, `table`, `tabs`, `toast`, `empty-state`, `section-label`.
- `resources/js/app.js`: `data-dialog-open` trigger, backdrop click closes, Tab focus trap for modal dialogs, `window.toast()` (builds text with `textContent`, errors stay until closed, others close after 4 s).
- `/_ui` (local only) shows every component in default, hover, focus, active, disabled, error, and loading states; hover/focus/active are forced with `data-demo` for side-by-side review.
- Removed the M0.1 `/_health` page and `resources/js/three/health-cube.js` (temporary by design).

**Decisions:**
- `textarea` and `field-messages` (shared hint/error line) added to `components/ui/`; `SITEMAP.md` §6 updated — agent-routine (file placement).
- Select uses Basecoat's styled native `<select>` (keyboard and screen-reader behavior from the browser) — agent-routine.
- Dialog uses the native `<dialog>` with `showModal()`, plus a small Tab trap because Chrome lets Tab leave a modal dialog to the browser UI — agent-routine.
- Toasts use Basecoat's toaster, but the app builds the toast DOM itself: Basecoat's `toaster.toast()` inserts title and description as HTML, which would not escape user content — agent-routine (security).
- Overlay and focus-ring tints are token colors with alpha (`primary` 35% for the ring per DESIGN.md §1.4, `neutral-950` 50% for the dialog backdrop, `destructive` 25% for an invalid field's focus ring) — agent-routine; no new color.
- Phosphor icon names: all names in the DESIGN.md §6 map exist in blade-phosphor-icons 2.4.0 (`*-bold`). Extra icons used: `circle-notch` (loading), `warning-circle` (field error), `x` (close), `caret-down` (dropdown), `info` (info toast).

**Checks:**
- `/_ui` in headless Chrome at 1280, 768, and 384 wide: no console errors, no horizontal page scroll (tables scroll inside their container).
- Keyboard: every Tab stop on `/_ui` shows a visible outline or ring. Dialog opens with Enter, focus moves inside, Tab stays inside, Escape closes, focus returns to the trigger. Tabs move with arrow keys. Dropdown opens with Enter, ArrowDown moves to the first item, Escape closes.
- Toasts: success closes after 4 s; error stays until Close is pressed.
- Reduced motion (emulated): hover transform `none`, transitions 0.01 ms, skeleton pulse runs once. Benchmark kill switch: transitions `0s`, animations `none`.
- No `px` design values and no raw color values in `resources/css`, `resources/views`, `resources/js` (scan excludes Laravel's stock `welcome.blade.php`, replaced in M1.2).
- Contrast (WCAG): white on primary 5.19, success 5.02, warning 5.02, destructive 6.47, info 5.17; neutral-600 on neutral-100 5.17; primary-dark on primary-tint 6.58.
- `php artisan test`: 12 passed (48 assertions), including badge icon+word, input error ARIA, escaping, loading button. `npm run build`: success; no Three.js in the entry bundle. Pint: passed.

**Issues:**
- Placeholder color `neutral-400` on white has contrast 2.4:1 (DESIGN.md §2.1 assigns `neutral-400` to placeholders). WCAG does not strictly require placeholder contrast, but it is low. Decision needed: keep, or use `neutral-600` for placeholders.
- CSS bundle is 278.78 kB (33.99 kB gzip) because all Basecoat components are imported. Trim at M5.1.
- Basecoat's own package CSS contains px values (for example toast text size); our overrides replace the ones that matter for the used components.

**Files:** `resources/css/{app,tokens,components}.css`, `resources/js/app.js`, `resources/views/components/ui/*`, `resources/views/dev/ui.blade.php`, `routes/web.php`, `tests/Feature/UiComponentsTest.php`, `tests/Feature/VerificationEnvironmentTest.php`, `fixtures/models/README.md`, `docs/SITEMAP.md`.

**Next:** M1.2 — Public site (not started). Before it starts: About page text and contact methods (Q3).

## 2026-10-03 — M0.2 Database and environments — completed

**Scope:** Full schema migrations (`PRD.md` §12), base seeders, verification environment and scripts, `DevFixtureSeeder`, scheduler with two empty jobs.

**Done:**
- Migrations: `users` gains `role` (admin|user) and `is_active`; option lists (`tools`, `option_presets`, `device_classes`, `budget_presets`, `budget_preset_device_class`); assets (`assets` with soft deletes, `asset_metrics`, `upload_sessions`, `upload_items`); tests (`test_sessions`, `test_session_assets`, `test_runs`, `test_results`, `budget_evaluations`); reports (`reports`, `report_public_assets`). Laravel's `sessions`, `cache`, `jobs` tables kept.
- Foreign keys restrict deletes of referenced records (tools, device classes, users, assets used by tests or reports), so "deactivate, do not delete" holds in the database too.
- Models for the seeded tables plus `Asset` (relations only, no business logic). `User` gains `role` and `is_active`.
- `config/lab.php`: admin seed values, `asset_max_upload_mb`, `staging_retention_days`, `run_expiry_minutes`, `verify_database`.
- Seeders (all idempotent, `firstOrCreate`): `AdminUserSeeder` (never overwrites an existing account), `ToolSeeder`, `OptionPresetSeeder`, `DeviceClassSeeder`, `BudgetPresetSeeder` (with class links).
- `DevFixtureSeeder`: reads the `fixtures/models/README.md` register, skips missing or invalid files with a warning, copies GLB files to verification storage with UUID names, creates assets owned by the first admin. It refuses to run unless the database is `render_lab_verify` and the private disk is under `storage/verify/`.
- Verification environment: `.env.verify` (git-ignored; template `.env.verify.example`), private disk root from `PRIVATE_STORAGE_PATH` (`verify/private`), scripts `bin/verify-db-setup.sh`, `bin/verify-db-reset.sh`, `bin/verify-serve.sh` with the shared guard `bin/_verify-guard.sh` (clears config cache, checks database and storage root).
- Tests: `phpunit.xml` points at `render_lab_verify`; `tests/TestCase.php` throws if a test connects to any other database; feature tests use transactions so verification data stays.
- Scheduler (`routes/console.php`): `CleanupStagedUploads` daily, `ExpireTestRuns` every five minutes, both on the `sync` connection inside `schedule:run` (no worker). Both jobs are empty shells.
- Working database `render_lab` migrated (it was empty) and seeded.

**Decisions:**
- D22–D25 — user (see table above).
- Session and cache back on the `database` driver now that the tables exist — agent-routine.
- Physical details not in `PRD.md` §12: `timestamps` on option tables, `asset_metrics`, `budget_evaluations`; `sort_order` on `budget_presets`; `upload_sessions.status` values `open | completed | expired`; `asset_id` nullable on `budget_evaluations` (mixed scenarios evaluate the asset set) — agent-routine.
- Private local disk: `'serve' => false`, so Laravel does not expose private files through its built-in signed file route (`AGENT.md` §8) — agent-routine.
- GLTF fixtures are skipped by `DevFixtureSeeder` until M3.2 adds dependent-file grouping — agent-routine.

**Checks:**
- `migrate` on empty `render_lab`: success. `bin/verify-db-setup.sh` on new `render_lab_verify`: success.
- Seeders run three times: no duplicates (users 1, tools 3, option presets 7, device classes 11, budget presets 4, preset–class links 9).
- Guard: with `.env.verify` pointed at `render_lab`, `verify-serve.sh` and `verify-db-reset.sh` exit 1 with "Refused: the verify environment uses database 'render_lab', not 'render_lab_verify'." With storage pointed at `app/private`, `verify-db-setup.sh` exits 1. `.env.verify` restored afterwards. `render_lab` was empty during the refusal test.
- `DevFixtureSeeder` against `render_lab`: refused.
- `php artisan test`: 8 passed (35 assertions) against `render_lab_verify`.
- `bin/verify-serve.sh`: port 8001 serves `/` (200); `/_health` is 404 outside `local`. Port 8000 `/_health`: 200, DB OK.
- `php artisan schedule:list` shows both jobs. `npm run build`: success. Pint: passed.

**Issues:**
- `fixtures/models/` has no model files yet; `DevFixtureSeeder` skipped all three register rows. Add the files and real Author/time values to the README register, then run `bin/verify-db-setup.sh`.
- The register still has placeholder authors (`_name_`); the seeder strips the underscores.

**Files:** `database/migrations/*`, `database/seeders/*`, `app/Models/{User,Tool,OptionPreset,DeviceClass,BudgetPreset,Asset}.php`, `app/Jobs/{CleanupStagedUploads,ExpireTestRuns}.php`, `config/lab.php`, `config/filesystems.php`, `routes/console.php`, `bin/*`, `phpunit.xml`, `tests/TestCase.php`, `tests/Feature/VerificationEnvironmentTest.php`, `.env.example`, `.env.verify.example`, `.gitignore`, `docs/PRD.md`, `docs/BENCHMARK.md`, `docs/AGENT.md`.

**Next:** M1.1 — Design system and UI kit (not started).

## 2026-10-03 — M0.1 Stack and tooling — completed

**Scope:** Install and prove the stack: Laravel, MariaDB connection, Vite, Tailwind v4, Basecoat, Alpine.js, Three.js, Fontsource fonts, Phosphor icons; git; Graphify; temporary `/_health` page.

**Done:**
- Laravel 13.34.0 installed through a temporary folder and moved into the root without overwriting `CLAUDE.md`, `docs/`, or `fixtures/`. The skeleton's own `CLAUDE.md` and `AGENTS.md` (Laravel Boost guidelines) were not copied.
- Database `render_lab` created (utf8mb4, empty). `.env` uses `DB_CONNECTION=mariadb`.
- Front end: Tailwind v4 with `@tailwindcss/vite`, Basecoat, Alpine.js, Three.js, self-hosted fonts. Tailwind theme fonts: `--font-sans` (Atkinson Hyperlegible Next), `--font-heading` (Funnel Display 600/700), `--font-mono` (Atkinson Hyperlegible Mono).
- `codeat3/blade-phosphor-icons` installed; Bold icons use `<x-phosphor-{name}-bold />`.
- Local-only `/_health` page (`routes/web.php`, `resources/views/dev/health.blade.php`) with six checks. Three.js is loaded by `import()` from `resources/js/app.js` only when the page has `[data-health-cube]`.
- Git initialized (branch `main`, no commit yet). `.gitignore` adds `/storage/verify` and `/graphify-out` to the Laravel defaults (`.env`, `/vendor`, `/node_modules`, `/public/build` already present).
- Graphify: `graphify claude install` (appended its section to `CLAUDE.md`; nothing else changed; PreToolUse hooks in `.claude/settings.json`), `graphify hook install` (post-commit, post-checkout), first graph built with `graphify update .`. Ignore file: `.graphifyignore` (gitignore syntax; `.gitignore` is also honored).

**Versions:** PHP 8.5.11 (local CLI; production target 8.3), Laravel 13.34.0, Composer 2.10.3, Node 26.8.2, npm 11.19.1, MariaDB 13.0.2 (local), Vite 8.3.2, laravel-vite-plugin 3.2.0, Tailwind CSS 4.3.3, Basecoat 1.0.2, Alpine.js 3.17.4, Three.js 0.186.1 (r186), blade-phosphor-icons 2.4.0, Fontsource packages 5.3.0, Graphify 0.9.74.

**Decisions:**
- Laravel 13 for PHP 8.3 — user (D19).
- New databases `render_lab` / `render_lab_verify` — user (D20).
- Atkinson Hyperlegible Mono for numeric data — user (D21). Measured result: Atkinson Hyperlegible Next has proportional digits by default (`1111` = 26.06 px, `8888` = 39.38 px at body size); Mono has equal widths (40.45 px each).
- Local `.env` sets `SESSION_DRIVER=file` and `CACHE_STORE=file`, because the schema (sessions/cache tables) belongs to M0.2. Revisit at M0.2 — agent-routine.
- Local `.env` uses `DB_SOCKET=/tmp/mysql.sock`, because the local MariaDB user authenticates by unix socket. `.env.example` keeps TCP settings — agent-routine.
- Removed the skeleton's Bunny font loader from `vite.config.js` (`DESIGN.md` §3 forbids runtime third-party fonts) — agent-routine.

**Checks:**
- `php artisan serve` (port 8000): `/_health` returns 200. `npm run dev` (Vite dev server): page works with HMR assets. `npm run build`: succeeds.
- `/_health` in headless Chrome (Playwright, desktop 1280 wide and phone 384 wide): DB OK (`render_lab`), all three font families loaded, Basecoat button styled, Phosphor `cube-bold` renders, Alpine toggle shows/hides content, Three.js r186 cube renders; no console errors; no horizontal scroll at phone width.
- Build manifest: `resources/js/app.js` has no static imports and one dynamic import (`resources/js/three/health-cube.js`). The entry chunk contains no Three.js code (no `WebGLRenderer`/`REVISION` match).
- `php artisan test`: 2 passed (default Laravel tests; still on in-memory SQLite until M0.2 points them at `render_lab_verify`).

**Issues:**
- Basecoat 1.0 uses `class="btn" data-variant="outline"` (attribute variants), not `btn-outline` classes. Use this syntax in M1.1.
- CSS bundle is 259.57 kB (30.48 kB gzip) because `@import 'basecoat-css'` includes all components. Review at M1.1/M5.1 (import only used components).
- Vite warns that the Three.js chunk is larger than 500 kB (524.86 kB, 130.05 kB gzip). Expected for Three.js; it is loaded only on viewer pages.
- Browser check used headless Chrome; the Claude in Chrome extension was not connected.
- The old databases `perf_lab` and `perf_lab_verify` still exist on the machine. They were not changed.

**Files:** `composer.json`, `package.json`, `vite.config.js`, `.env`, `.env.example`, `.gitignore`, `.graphifyignore`, `.claude/settings.json`, `routes/web.php`, `resources/css/app.css`, `resources/js/app.js`, `resources/js/three/health-cube.js`, `resources/views/dev/health.blade.php`, `CLAUDE.md` (Graphify section), `docs/AGENT.md`, `docs/DESIGN.md`, `docs/PROJECT.md`.

**Next:** M0.2 — Database and environments (not started).
