# LOG.md — Implementation history

The agent updates this file at each milestone boundary and after each decision. Format: `AGENT.md` §12. Newest entry at the top of "History".

---

## Current status

| Item              | Value                                   |
| ----------------- | --------------------------------------- |
| Current milestone | M5.2 — Deployment (prepared; live deployment waits for the user) |
| Status            | All milestones built (M4.3–M5.1). M5.2 prepared; the live deployment and its smoke test need the user. Next: the user's end-to-end test, then one feedback and bug-fix session (D60). Open: Q3, Q5 |
| Last updated      | 2026-10-17                              |

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
| D65 | (2026-10-13, M4.4 gate, recommended) "Most copies" with one repetition: the highest count wins; equal counts, including two results that reach the cap (≥ cap), are "Too close to call" | `BENCHMARK.md` §4.5 |
| D64 | (2026-10-13, M4.4 gate, recommended) Crowd look phase = the explore viewer (one asset or tool set, a motion scenario, a copy-count slider, default 16 copies; not measured). The same viewer is used on the results page | `BENCHMARK.md` §4.6 |
| D63 | (2026-10-13, M4.4 gate, recommended) Synthetic development fixtures: a heavy test sphere (about 200k triangles) and a "Test sphere" object in each tool folder with different detail. Dummy metadata, development only, never user data | `fixtures/models/README.md` |
| D62 | (2026-10-13, M4.4 gate, recommended) Crowd repetitions default 1, range 1–3. The estimate shows a typical time and a maximum; the run expiry uses the maximum | `BENCHMARK.md` §4.6, §5 |
| D61 | (2026-10-13) M4.3 approved by the user, with the six agent choices listed in the M4.3 report ("Natural" = neutral best marker badge) | `LOG.md` M4.3 |
| D60 | (2026-10-13) Workflow change: the agent builds the remaining milestones (M4.4 → M5.1, and M5.2 up to the live deployment) one at a time without waiting for approval between them. New decisions take the recommended option and are logged as "agent, for review". The user tests end to end after all milestones; one feedback and bug-fix session follows. The live deployment (M5.2) still needs the user's confirmation and account access | `AGENT.md` §2, `PROJECT.md` §1 |
| D59 | (2026-10-09, Q10) Run expiry: 30 minutes after start, or the estimated run time + 15 minutes when that is longer, for Side-by-Side and Crowd runs. A valid long run is not lost | `BENCHMARK.md` §5, `config/lab.php` |
| D58 | (2026-10-09) The M4.1 phone self-test is removed from the acceptance criteria. Consequence: the engine has no recorded check on a mobile GPU; phone results are first seen in real tests | `PROJECT.md` M4.1 |
| D57 | (2026-10-09, M4.3 gate) The "Three Bird fixtures" acceptance item of M4.3 is pending. The user tests it manually with their own models | `PROJECT.md` M4.3 |
| D56 | (2026-10-09, M4.3 gate) During Side-by-Side measurement, the measured pane keeps its grid position and size; the other panes show their last frame as a static image | `DESIGN.md` §9.3 |
| D55 | (2026-10-09, Q9) Smoothness labels are neutral badges with the word and the FPS-equivalent, no icon and no status color. Best markers are neutral outline badges with the `trophy` icon; "Too close to call" uses `equals`. Pane layout: 2 side by side, 3–4 in a 2 × 2 grid, one column on phones. `DESIGN.md` budget, old method, and "no winner markers" rules replaced | `DESIGN.md` §2.2, §5.1, §6, §8.3, §9.2–9.4, §10, §12 |
| D54 | (2026-10-07, Q8) Milestone order: M4.2 Lab reset and foundations → M4.3 Side-by-Side → M4.4 Crowd → M4.5 Tool summary → M4.6 Results, reports, publishing. Replaces the order in D43 | `PROJECT.md` §2, §3 |
| D53 | (2026-10-07, Q7) Delete all existing assets in `render_lab` (40 assets, all Blender, about 1.6 MB of files) instead of adding an Object to them; they are uploaded again with an Object. Executed in M4.2 after a backup and a final confirmation with the exact list | `PROJECT.md` M4.2 |
| D52 | (2026-10-07, Q7) Object is an admin-managed option list, and it is required for every new upload | `PRD.md` §6.1, §7.1, §11 |
| D51 | (2026-10-07, test redesign) Motion scenarios are selectable per test; all three are selected by default and the user can untick any (at least one stays selected) | `BENCHMARK.md` §4.2 |
| D50 | (2026-10-07, test redesign) Physics colliders: a simplified convex hull of each asset (close to the mesh shape, not exact; capped point count, same cap for every asset). Not a simple box, not the exact mesh | `BENCHMARK.md` §4.2 |
| D49 | (2026-10-07, test redesign) Physics library: `cannon-es` (approved dependency, loaded with dynamic `import()` on test pages only) | `AGENT.md` §5.2 |
| D48 | (2026-10-07, test redesign) Tool Crowd fairness: only objects that exist in every selected tool are compared; the user chooses which of those objects to include (and which asset, when a tool has more than one for an object). Missing objects are listed | `BENCHMARK.md` (spec rewrite pending) |
| D47 | (2026-10-07, test redesign) Crowd size: the research plan has about 20 objects; a mixed crowd allows up to 30 objects and grows as copies of each object (1×, 2×, 4×, …) | `BENCHMARK.md` (spec rewrite pending) |
| D46 | (2026-10-07, test redesign) Targets have no "minimum copies" check. The Crowd test shows the copy count and the "Most copies" badge only | `BENCHMARK.md` (spec rewrite pending) |
| D45 | (2026-10-07, test redesign) Smoothness is measured in three motion scenarios: Static, Spin, and Physics (objects in a box with gravity, box shaken). Physics library choice is open (new dependency) | `BENCHMARK.md` (spec rewrite pending) |
| D44 | (2026-10-07, test redesign) Automatic smoothness labels against the Target frame rate: Smooth = 95% of frames ≤ 1.25 × goal frame time and ≤ 1% stutter; Mostly smooth = 95% of frames ≤ 2 × goal frame time; Choppy = slower | `BENCHMARK.md` (spec rewrite pending) |
| D43 | (2026-10-07, test redesign) Replace milestones M4.2 Comparison, M4.3 Stress, M4.4 Headroom with: M4.2 Side-by-Side, M4.3 Crowd, M4.4 Targets and Tool summary; M4.5 Reports follows. Reason: the old tests hid the assets during measurement, showed numbers only, and the user could not see or interact with the assets | `PROJECT.md` (spec rewrite pending) |
| D42 | (2026-10-07, test redesign) Keep mixed-asset crowds. Goal: compare the crowd of objects A–Z made in Blender against the same objects made in Spline and in Prisma3D | `BENCHMARK.md` (spec rewrite pending) |
| D41 | (2026-10-07, test redesign) Add an **Object** field to assets, so that the same object made in different tools can be matched. The research plan defines which objects are created | `PRD.md` §6.1, §12 (spec rewrite pending) |
| D40 | (2026-10-07, test redesign) Delete the existing test data in `render_lab` (test sessions, runs, results, budget evaluations). Export a JSON backup first; the agent asks for a final confirmation with the exact row list before it deletes | `LOG.md` (executed at the start of M4.2) |
| D39 | (2026-10-07, test redesign) Results mark the best asset per metric (for example "Fastest load", "Smoothest"). Still no overall score and no weighted ranking. Replaces the "no ranking" part of D9 | `BENCHMARK.md` §1, `PRD.md` §3 (spec rewrite pending) |
| D38 | Times are stored in UTC and shown in one display zone, `APP_DISPLAY_TIMEZONE` (default `Asia/Jakarta`), for every viewer: default test names, result, asset, upload, user, and report dates. JSON/CSV exports keep UTC | `config/lab.php`, `DEPLOYMENT.md` §4 |
| D37 | (M4.3) `budget_evaluations.step_instances` (nullable): Stress frame items stored per step; summary items with null | `PRD.md` §12 |
| D36 | (M4.1, Q4) Chart library: uPlot, loaded with dynamic `import()` | `AGENT.md` §5.2, `DESIGN.md` §10 |
| D35 | (M4.1) Cold load: unique URL + `cache: 'reload'`; warm load: same URL, default cache (was `no-store`, which made the warm load download again) | `BENCHMARK.md` §3.2 |
| D34 | (M3.3 addition) Bulk delete in the library table: soft delete for own assets (admins: any); admins also bulk delete permanently, with assets used in tests marked and skipped | `PRD.md` §7.2, `SITEMAP.md` §3.2 |
| D33 | (M3.3) Permanent delete is blocked when any test uses the asset, not only a published report; results always keep their assets | `PRD.md` §7.4, `SITEMAP.md` §3.2 |
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
| Q5  | Hostinger "Confirm" items in `DEPLOYMENT.md`                                                            | M5.2      |

---

## History

## 2026-10-17 — Review fixes (feedback and bug-fix session, D60) — completed

**Scope:** fixes from the project review that the user selected: commit the open work, the memory risk in lists, the double submit, docs drift, and the build warning. Public text: Landing cards and the About "Targets, not scores" section, wording approved by the user.
**Done:**
- Committed the M4.4–M5.2 work (`6903ae8`).
- Lists no longer load raw samples: new scope `TestResult::withoutSamples()`, used by the results history, the dashboard, and the Tool summary. Each row can hold up to 3,600 values per series, which could exceed the PHP memory limit on shared hosting.
- Double submit: `RunResultService::submit` locks the run row and checks again that it is still running inside the transaction. A second submit gets a 422 and stores nothing.
- Invalidate, cancel, and run expiry end a run only while it is `running` (one conditional update). A cancel or invalidate that arrives after a completed submit returns 409 and keeps the run completed.
- `features/device.js` imports `three/environment.js` on demand, so it leaves the entry bundle and the `INEFFECTIVE_DYNAMIC_IMPORT` build warning is gone.
- `AGENT.md` §6.1 and §6.2 now list the real folders and files; stale "Stress/Headroom" comment in `viewer.js` replaced.
**Decisions:** fix selection and the public text — user (2026-10-17).
**Checks:** Pint passes. PHP 192 tests pass (1,147 assertions; new `test_a_submit_that_loses_the_race_stores_nothing`, `test_a_cancel_or_invalidate_after_a_submit_keeps_the_run_completed`). JS 21 pass. Build passes, no warning except the Three.js chunk size; the entry bundle contains no Three.js.
**Issues:**
- Landing hero line still says "against a clear performance budget"; not in the approved text.
- The `/device` page was not checked in a browser after the import change.
**Files:** `app/Models/TestResult.php`, `app/Http/Controllers/Api/TestRunController.php`, `resources/views/pages/public/{landing,about}.blade.php`, `app/Http/Controllers/Results/ResultController.php`, `app/Http/Controllers/Dashboard/DashboardController.php`, `app/Services/ToolSummaryService.php`, `app/Services/RunResultService.php`, `tests/Feature/SideBySideTest.php`, `resources/js/features/device.js`, `resources/js/three/viewer.js`, `docs/AGENT.md`.
**Next:** the user's wording for the public text; then the live deployment with the user.

## 2026-10-13 — M5.2 Deployment — prepared (stopped before the live deployment)

**Scope:** `DEPLOYMENT.md` procedure: production `.env` template, build, release, document root, permissions, cron, PHP limits, HTTPS; smoke test on the live site; `README.md` (`PROJECT.md` M5.2). Under D60 the agent stops before the live deployment: it publishes the site and needs the user's Hostinger account and confirmation.
**Done:**
- `.env.production.example` (production values from `DEPLOYMENT.md` §4: `APP_DEBUG=false`, secure session cookie, database sessions and cache, daily log at warning level, admin seed values).
- `database/seeders/ProductionSeeder.php` (admin, tools, presets, device classes, Targets; never fixtures or Objects). Ran on `render_lab_verify`: all seeders DONE, idempotent.
- `bin/make-release.sh`: builds the assets and writes `release/<name>.tar.gz` with `app-core/` (production `vendor/`, `.htaccess` = `Require all denied`) and `public_html/` (public files, `build/`, and `deploy/public_html/index.php`, which points to `../app-core/` and calls `usePublicPath`). Excludes `.env` files, `node_modules/`, storage contents, `storage/verify/`, `fixtures/`, `docs/`, `tests/`, the graph, and development files. Test run: 8.3 MB archive, no secrets or development data; the test archive was deleted (disk space).
- `README.md` for developers (stack, local setup, verification environment, tests, release).
- `DEPLOYMENT.md` §4, §5.1, §6 updated (release script, scheduler table: staging cleanup daily, run expiry every 5 minutes with the D59 rule, no queue worker).
**Not done (needs the user):**
- The "Confirm" items in `DEPLOYMENT.md` §2–§7 (Q5): PHP version and extensions, MariaDB version, SSH and Composer, cron frequency and PHP path, document root option, PHP limits, HTTPS, backups.
- The upload to Hostinger, `.env` with real credentials, migrate, `ProductionSeeder`, cron entry, and the smoke test (§8).
- About text and contact methods (Q3): the pages still show marked placeholders.
**Issues:**
- The development machine had only about 350 MB of free disk space at the end of this session. Free space before the next build or browser test.
**Files:** `.env.production.example`, `database/seeders/ProductionSeeder.php`, `bin/make-release.sh`, `deploy/public_html/index.php`, `deploy/app-core.htaccess`, `.gitignore` (`/release`), `README.md`, `docs/DEPLOYMENT.md`.
**Next:** the user's end-to-end test of all milestones; then one feedback and bug-fix session; then the live deployment with the user.

## 2026-10-13 — M5.1 Hardening and QA — completed

**Scope:** security review (uploads, authorization, CSRF, XSS, public boundary), error pages, accessibility pass, responsive pass, benchmark-integrity review, index review, performance check (`PROJECT.md` M5.1). Started without a gate (D60).
**Done and results:**
- **Security review.**
  - XSS: no `{!! !!}` in any view; JSON config blocks use `@json` (escapes `<`, `>`, `&`, quotes); the browser writes user text with `textContent`/`x-text` only.
  - CSRF: no exceptions; the `/api` JSON endpoints are in the web group and send the token (`api.js`).
  - Authorization: every POST/PUT/PATCH/DELETE action takes a Form Request with a policy check (35 actions checked; logout only needs auth + CSRF). New `HardeningTest`: every GET app route redirects guests to login (JSON endpoints 401), and every admin page returns 403 to a user.
  - Uploads: content, size, and hash validation from M3.2 unchanged; files stay in private storage with generated names.
  - Public boundary: `ReportsTest` (M4.6) checks the forbidden fields in the public HTML and JSON.
  - New: `SecurityHeaders` middleware (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`); public model files rate-limited (120 per minute).
- **Error pages** `errors/{403,404,419,429,500,503}` in the design system, with no session, database, or user data and `noindex`; each has one sentence and one action.
- **Accessibility and responsive pass** (headless Chrome, verification environment): 30 pages (22 app pages signed in as admin, 8 public pages including a Side-by-Side and a Crowd public report and the 404 page) at 384, 768, and 1440 wide. Checked: labels on every visible control, an accessible name on every button and link, `alt` on every image, no duplicate ids, exactly one `h1`, no skipped heading levels, `lang` on `<html>`, no horizontal page scroll. **Result: 0 issues; no console errors** (except the intended 404). Keyboard: pane toolbars use native radios with a visible focus outline; canvases are focusable and take arrow keys; dialogs trap focus (M1.1).
- **Benchmark-integrity review** against `BENCHMARK.md`: standard environment (antialias off, pixel ratio 1, drawing buffer from the preset, `NoToneMapping`, no shadows, no controls or overlays in benchmark mode); cold/warm load URLs and caches (D35); seeded shuffle per repetition with the seed recorded; cool-down by device category; invalidation (hidden tab, focus lost > 1 s, context lost); physics warm-up ≥ 2 s and ≥ 120 steps; one fixed physics step per frame; Crowd probe protocol and search rules; medians, min–max, Unstable > 25 %; labels and `smooth_copies` recomputed on the server; no DOM change during windows (counted in every run: 0 in all browser runs of M4.3–M4.4); the public reproduction never measures. **Finding fixed:** Technical details showed nine metrics without a label or explanation, so the JS heap was not marked as browser-reported; all metrics now have a definition in `MetricDefinitions` (`AGENT.md` §7).
- **Index review:** the history, Tool summary, and report queries use existing indexes (`test_sessions` created_by + (method, created_at); `test_runs` (status, started_at) + device_class_id; `test_results`/`target_checks` by run; `reports` (status, published_at) + unique slug; unique token). No change needed at the MVP scale.
- **Performance:** entry JS 131.8 kB (40.1 kB gzip), CSS 296.8 kB (35.0 kB gzip). Three.js (737 kB, 187 kB gzip) and cannon-es (motion chunk, 95 kB) load only on viewer, Lab, benchmark, results, and public report pages; the entry bundle contains neither.
- `px` check: no `px` design values in CSS or views.
**Checks:** PHP 190 tests pass (1,133 assertions; new `HardeningTest` 4). JS 21 pass. Pint passes. Build passes.
**Issues (open):**
- **Not done by the agent:** the responsive pass on a real phone and tablet (only emulated widths were checked); Lighthouse scores (no Lighthouse CLI; M1.2 recorded ≥ 95 for Landing and Gallery). Both belong to the user's end-to-end test.
- No Content-Security-Policy: Alpine.js evaluates expressions at runtime and would need `'unsafe-eval'`. Consider Alpine's CSP build later.
- The CSS bundle includes all Basecoat components (35 kB gzip).
- Fixture assets in `render_lab_verify` have no metrics rows and no thumbnails (development data only).
**Files:** `app/Http/Middleware/SecurityHeaders.php`, `bootstrap/app.php`, `routes/web.php`, `resources/views/errors/*.blade.php`, `app/Support/MetricDefinitions.php`, `tests/Feature/HardeningTest.php`.
**Next:** M5.2 Deployment — prepared by the agent; the live deployment needs the user (D60).

## 2026-10-13 — M4.6 Results, reports, publishing — completed

**Scope:** `PRD.md` §10: history with filters, test detail, JSON/CSV export, report editor, publish/unpublish with slug and tokens, public gallery and detail with real data, public reproduction, `PublicReportPresenter` (`PROJECT.md` M4.6). Started without a gate (D60).
**Done:**
- `/results` (`ResultController@index`): own tests (admins: all, with the creator name), filters method, device class, date range (display zone, invalid values ignored), 25 per page; columns name, method + scenario, date, assets, device class, Target, run status, result line (`TestSummary`: "2 of 3 assets meet all Target checks" or "Up to N Smooth copies (Static)"), report state.
- Test detail: actions Write/Edit report, JSON, CSV, Delete (dialog; blocked while the report is published; a draft report goes with the test; runs and results cascade).
- Exports (`ExportController`): JSON with test, assets, runs, results with raw samples, Target checks; CSV with one row per stored result (run, device class, asset or tool set, motion or "load", probe, repetition, cold/warm TTFF, metrics, label, copies, flags). Owner or admin.
- Report editor `/results/{test}/report` (`Results\ReportController`, `ReportService`, `UpdateReportRequest`, `PublishReportRequest`): title, question, standard limitations (`ReportLimitations`, all eight items of `BENCHMARK.md` §9), additional limitations (one per line), conclusion, public asset approval (default not approved), publish checklist, publish, unpublish, public link.
- Publishing: needs a question, one approved asset, and a completed run. Slug = title + 6 random characters, kept on republish. Tokens change on every publish and unpublish, so old file URLs return 404 at once.
- Public site: gallery and detail use published reports only (`PublicReportPresenter`); detail has the Target result (Side-by-Side) or "Smooth up to" (Crowd), the interactive reproduction (linked panes or the crowd explorer at the Smooth count, labelled "Interactive reproduction"; unapproved assets show a placeholder), evidence (values table and load phases, or probe charts), asset characteristics, configuration with Target, environment summary, limitations, conclusion. Public model files at `/reports/{slug}/assets/{token}/{name?}`. Assets and groups are keyed by position (a1, g1), never by ID.
- Dashboard (`DashboardController`): confirmed device class, counts (assets, own tests or all for admins, published reports), recent tests with result line, recent uploads, quick actions (Upload, New Side-by-Side, New Crowd, Tool summary).
- Removed: `SampleReports`, `SampleScreens`, the old `budget-table` component, the sample states of the public gallery. The app layout no longer shows "Sample data" by default.
**Decisions (agent, for review):**
- Approvals are kept while a report is unpublished; unpublish replaces the tokens instead of deleting the approvals, so republishing keeps the approvals with new tokens (PRD §10.4 "Republish … creates new tokens").
- Deleting a test also deletes its draft report; it is blocked while the report is published.
- The dashboard was built with real data in this milestone (the last sample screen), because the history and reports it links to became real.
- A GLTF public URL includes the stored file name, so its dependents resolve under the same token; a GLB URL does not.
- The public reproduction shows a Crowd group only when all of its assets are approved.
**Checks:**
- PHP: 186 tests pass. New `ReportsTest` (7, 95 assertions): private by default, publish blocked without question / approved asset / completed run; draft and unpublished URLs 404; unpublish revokes tokens at once; republish keeps the slug with new tokens; **public HTML (detail and gallery) contains no user email, storage path, stored file name, unapproved or private asset URL, private result URL, raw samples, run seed, `asset_id`, or asset database ID, and no `/api/` call** (the reproduction never measures or writes); owner/admin only for editor, export, publish; approved asset must belong to the test; JSON export has raw samples; CSV has one row per result; history filters and visibility; delete blocked while published, draft deleted with the test; dashboard real data. `PublicSiteTest` and `AppScreensTest` rewritten for real data.
- Build passes; entry bundle without Three.js and cannon-es.
- Browser (verification environment, headless Chrome): wrote and published a report for a Side-by-Side test and a Crowd test; as a guest the public pages loaded the panes and the crowd explorer from token URLs (all ready), no horizontal scroll at 1440 and 384, gallery shows 2 cards, no console errors.
**Issues:**
- The machine's disk had about 200 MB free during the browser checks; the agent's Chrome profiles (≈ 700 MB, in the session scratchpad) were deleted and are now removed after each run.
- The public Crowd reproduction cannot show a tool set when one of its assets is not approved; the page explains it.
**Files:** `app/Http/Controllers/Results/{ResultController,ExportController,ReportController}.php`, `app/Http/Controllers/Public/ReportController.php`, `app/Http/Controllers/Dashboard/DashboardController.php`, `app/Http/Requests/{Results/DeleteTestRequest,Reports/UpdateReportRequest,Reports/PublishReportRequest}.php`, `app/Services/ReportService.php`, `app/Support/{PublicReportPresenter,ReportLimitations,TestSummary}.php`, `app/Models/{Report,TestSession}.php`, views `pages/results/{index,show,report}`, `pages/public/{reports,report-show}`, `pages/dashboard/index`, `components/reports/report-card`, `layouts/app`; `routes/web.php`; tests `tests/Feature/{ReportsTest,PublicSiteTest,AppScreensTest}.php`; docs `SITEMAP.md` §2, `LOG.md`.
**Next:** M5.1 Hardening and QA (started directly, D60).

## 2026-10-13 — M4.5 Tool summary — completed

**Scope:** `BENCHMARK.md` §4.7, `PRD.md` §8.3: device class and tool selection, common objects, per-tool medians, Target check counts, copies, Tool crowd results, best markers, links to the source tests, "Not tested" cells (`PROJECT.md` M4.5). Started without a gate (D60).
**Done:**
- `GET /results/tools` (`Results\ToolSummaryController`, `pages/results/tools`): device class (default: the confirmed one), tools, and left-out objects in the query string; works without JavaScript and can be bookmarked. Missing objects are listed with the tools that lack them.
- `ToolSummaryService`: per object and tool, the most recent completed run on the device class — Side-by-Side for cold load and checks, Crowd Per asset for `smooth_copies`. Per tool: objects compared, median file size, median triangles, median cold load with "n of N tested", "x of y pass" per Target check with the Targets of the source tests, median Smooth copies per motion, and up to five Crowd Per tool results as measured. Best markers per row (Smallest file, Fastest load, Most copies per motion). Per-object table where every value links to the test it came from; "Not tested" where there is no result. Users see their own tests; admins see all.
- Lab card and sidebar link enabled; "Test history" is no longer highlighted on the Tool summary page.
**Decisions (agent, for review):**
- When a tool has several assets for one object, the summary uses the newest asset that has a result on the device class; without any result, the newest asset ("Not tested").
- The Tool summary compares medians across objects, which have no repetition spread, so its markers use the static rule (lowest or highest value wins; equal values share). The spread rule stays for test results.
- Objects are left out with "Leave out …" checkboxes (`exclude[]`), so the form works without JavaScript and every object is included by default.
**Checks:**
- PHP: 184 tests pass (990 assertions). New `ToolSummaryTest` (3): medians, "1 of 2 pass" / "3 of 3 pass", copies, and markers equal a manual calculation for two tools and three objects; another user's test and another device class are ignored; links to the source tests; a left-out object and a missing object are not included; the page asks for a device class and two tools. `VerificationEnvironmentTest` updated for the D63 fixture objects.
- Browser (verification environment): `/results/tools` with Blender, Prisma3D, Spline on Laptop — Integrated GPU — Test sphere compared, four objects listed as missing, Crowd values and Tool crowd results linked; no horizontal page scroll at 1440 and 384; no console errors.
**Issues:**
- A copy count at the cap shows as the number (for example "1,024 copies") without "≥" in the Tool summary; the source test shows "Reached the cap".
**Files:** `app/Services/ToolSummaryService.php`, `app/Http/Controllers/Results/ToolSummaryController.php`, `resources/views/pages/results/tools.blade.php`, `routes/web.php`, sidebar, `pages/lab/index`, `tests/Feature/{ToolSummaryTest,AppScreensTest,VerificationEnvironmentTest}.php`.
**Next:** M4.6 Results, reports, publishing (started directly, D60).

## 2026-10-13 — M4.4 Crowd Test — completed

**Scope:** `BENCHMARK.md` §4.6: Per asset and Per tool, tool and object selection (`PRD.md` §8.3), the visible search, cap and time limit, results with Most copies and the probe chart, the explore viewer (`PROJECT.md` M4.4). Gate decisions D61–D65 (recommended options, user). Started without a gate under D60.
**Done:**
- Synthetic fixtures (D63): `fixtures/models/generate-test-spheres.mjs` writes `blender/test-sphere.glb` (320 triangles), `spline/test-sphere.glb` (3,380), `prisma3d/test-sphere.glb` (33,620), `blender/heavy-sphere.glb` (200,000; 4.6 MB). Registered in `fixtures/models/README.md`; `DevFixtureSeeder` imports them into `render_lab_verify`.
- `GET /api/objects/common?tools=…` (`CommonObjects`, `CommonObjectsController`): objects present in every tool with candidate assets per tool (newest first), and the missing objects with the tools that lack them. Shared with the Tool summary (M4.5).
- `POST /api/tests` for `method=crowd`: Per asset 1–8 assets; Per tool 2+ tools and 1–30 objects with one asset per tool; a forced object that is missing in a tool, or an asset of another object or tool, is rejected. Repetitions 1–3, cap 1–4,096. Fixed probe protocol stored in the snapshot (1 s / Physics 2 s warm-up, 4 s window, 180 s limit, `instance_mode: clone`). Per tool stores one member per tool × object with `group_tool_id`; `TestSession::crowdShape()`, `CrowdGroups`.
- Estimate and expiry (D62): `RunEstimate::crowdSeconds()` (typical and maximum; same rule in `features/run-estimate.js`). The run expires after the maximum + 15 minutes.
- `/lab/crowd` (`Lab\CrowdController`, `features/crowd.js`): scenario switch, selector (Per asset), tool checkboxes with the common-object table, include ticks, asset choice when a tool has more than one, the missing list; look phase = explore viewer (D64); settings, estimate "typical, at most"; Target; Device; Run. `LabPage` now holds the data shared with the Side-by-Side page.
- Engine: `three/crowd.js` (templates, hulls once per template, round-robin clones, grid or physics crowd box, layout bounds for framing); `three/methods/crowd.js` (`searchCopies`: doubling, binary search to max(1, 5%), cap, time limit; the runner shows the crowd on one canvas, auto-frames each probe, updates the strip only between probes, counts DOM changes during windows); `three/crowd-viewer.js` (explore viewer, listeners removed on dispose). Benchmark viewer gained `frame(box)`.
- Server results: probes stored with the server's smoothness label; one search row per group × motion × repetition with `smooth_copies` derived by the server from the Smooth probes, `smooth_instances`, `cap_copies`, `at_cap`, the flags, and the probe count. No Target checks (D46). Payload checks: one search per group × motion × repetition, 1–64 probes, probes within the cap, no foreign groups.
- Results (`CrowdResults`, `partials/crowd`): "Smooth up to N copies" bars per motion in selection order with ≥ cap, Reached the cap, Time limit, Most copies (`BestMarkers::highestSpread`, D65) and Too close to call; explore viewer starting at the Smooth count; probe charts (`features/probe-chart.js`, uPlot: circle/square/triangle and S/M/C text, plus a text list for screen readers); tested assets by group; Technical details per probe; run environment. The results page is split into `partials/side-by-side` and `partials/crowd`.
- Lab card and sidebar link enabled.
**Decisions:**
- D61–D65 — user (recommended options).
- The server derives `smooth_copies` from the submitted probe summaries with its own label rule instead of trusting a submitted count — agent-routine (`BENCHMARK.md` §1.2: the server evaluates).
- The probe chart shows repetition 1; all probes of every repetition are in Technical details — agent, for review.
- The Most copies bars use the series colors in group order (`DESIGN.md` §10); the value is always printed — agent, for review.
- One look shake lasts 3 s (as in Side-by-Side) — agent-routine.
**Checks:**
- PHP: 181 tests pass (959 assertions). New `CrowdTest` (9: page; common objects and missing list with newest asset first; forced missing object and wrong asset rejected; Per asset limits, repetitions, cap; Per tool groups in tool and object order; server-derived `smooth_copies` with binary search, labels, no Target checks, results with Most copies; two results at the cap = Too close to call; incomplete, over-the-cap, and foreign results rejected; expiry = maximum + 15 min) and a Most copies unit test.
- JS: 21 pass (new: search doubling and 5% gap, first probe not Smooth = 0, cap flag, time-limit flag, gap of one; crowd estimate).
- Build passes; the entry bundle contains neither Three.js nor cannon-es.
- Browser (verification environment, headless Chrome, Apple M2, 60 Hz, Target Desktop 60 fps):
  - Per asset, Static, cap 1,024: **Test sphere (320 triangles) Smooth up to ≥ 1,024 copies (cap); Heavy test sphere (200k triangles) Smooth up to 224 copies** — the light fixture reaches more copies (sanity check passes). 126 s (estimate "about 3 min, at most 7 min"). The crowd was visible on the canvas during the search; 0 DOM changes during windows; no console errors.
  - Per tool (Blender, Prisma3D, Spline; object Test sphere; Apple, Chicken, Book, Heavy test sphere listed as missing), Static + Physics, cap 64: Static all ≥ 64 (cap, Too close to call); Physics Blender 60, Prisma3D ≥ 64 (Most copies), Spline 44; colliders 84 ms; 0 DOM changes during windows.
  - Results at 1440 and 384 wide: no horizontal page scroll after the probe-chart resize fix.
**Issues:**
- The time-limit stop is covered by the JS unit test and the server tests, not by a 3-minute browser run.
- Physics crowds are limited by physics time (`physics_p95_ms`) more than by drawing; this is what the scenario measures.
- Fixture assets in `render_lab_verify` still have no metrics rows (triangles show "—").
**Files:** `app/Support/{CommonObjects,CrowdGroups,CrowdResults,LabPage,RunEstimate,BestMarkers}.php`; `app/Http/Controllers/Api/{CommonObjectsController,TestRunController}.php`; `app/Http/Controllers/Lab/{CrowdController,SideBySideController,RunController}.php`; `app/Http/Requests/Lab/{CreateTestRequest,SubmitResultsRequest}.php`; `app/Services/{TestSessionService,RunResultService}.php`; `app/Models/{TestSession,TestRun}.php`; `resources/js/three/{crowd,crowd-viewer,viewer}.js`; `resources/js/three/methods/crowd.js`; `resources/js/features/{crowd,probe-chart,run-estimate,benchmark-run}.js`; views `pages/lab/{crowd,run,index}`, `pages/results/{show,partials/side-by-side,partials/crowd}`, `components/performance/crowd-explorer`; tests `tests/Feature/CrowdTest.php`, `tests/Unit/BestMarkersTest.php`, `tests/js/{crowd-search,run-estimate}.test.mjs`; `fixtures/models/{generate-test-spheres.mjs,README.md,*/test-sphere.glb,blender/heavy-sphere.glb}`; docs `BENCHMARK.md` §4.6, `PROJECT.md` §1, `LOG.md`.
**Next:** M4.5 Tool summary (started directly, D60).

## 2026-10-09 — M4.3 Side-by-Side Test — completed (waiting for user review)

**Scope:** `BENCHMARK.md` §4.3–§4.5: selector (2–4 assets), look phase with linked panes, configuration (motion scenarios, settings, Target, estimated run time), measure phase, results on the panes, value table, load-phase bars, Technical details (`PROJECT.md` M4.3). Plus the `/_engine` fixture picker, and the M4.3 gate decisions D55–D59.
**Done:**
- `/lab/side-by-side` (`Lab\SideBySideController`, `features/side-by-side.js`): steps 1 Assets → 2 Settings → 3 Target → 4 Device → Run; selection order with move and remove; motion checkboxes (at least one stays); estimate shown live (`features/run-estimate.js`, same rule as `App\Support\RunEstimate`); draft kept across `/device`. Lab card and sidebar link enabled.
- Look phase (`three/panes.js`): one renderer per pane, one shared camera and one shared orbit target, so orbit, pan, and zoom in any pane move all panes; mouse, touch, and keyboard (arrow keys pan, Shift + arrows orbit); motion switch Static / Spin / Physics for all panes; Shake (Physics only); Reset view; Fullscreen. Assets keep their real size. Used again on the results page with the presets the test stored.
- API: `POST /api/tests` (`CreateTestRequest`, `TestSessionService`: preset and Target snapshots, fixed motion order), `POST /api/tests/{test}/runs` returns the run URL, `POST /api/runs/{run}/results` (`SubmitResultsRequest`: schema, known keys only, and the payload must match the configuration exactly: every asset × repetition × load + each motion, no foreign or duplicate rows).
- `RunResultService::submit`: stores load rows and motion rows, then per asset the medians → `TargetEvaluator` → `target_checks` (Loads in time, Runs smooth per motion with label, File size).
- Benchmark page `/lab/runs/{run}` (`Lab\RunController`, `features/benchmark-run.js`, `three/methods/side-by-side.js`): panes keep their grid positions; the measured pane gets the `primary` border and "Measuring" before its windows; a fresh benchmark renderer per measured asset; cold and warm load; each selected motion with warm-up (Physics ≥ 2 s and settled) and window; the last frame stays as an image; cool-down; seeded shuffle per repetition; a mutation observer counts DOM changes during windows; the number of rendering panes is recorded.
- Results `/results/{test}` (`SideBySideResults`, `BestMarkers`): panes with best markers, smoothness labels, cold load, file size, Target checks, "Meets all Target checks"; value table in selection order with markers and "Too close to call"; Unstable note; load-phase stacked bars (HTML, `DESIGN.md` §10 colors, values in text); Tested assets (Object column added); Technical details per motion and the run environment. Without a completed run the page shows the last run status and reason.
- Run expiry (D59): `TestRun::expiresAt()` = start + max(30 min, estimate + 15 min); `expireStale()` checks each candidate.
- `/_engine` (local only): fixture picker for every file in `fixtures/models/`.
**Decisions:**
- D55–D59 — user (see the decision table). D55 "Best marker: Natural" was read as "neutral" — agent, for review.
- One renderer per pane (2–4 WebGL contexts) with one shared camera, instead of one renderer with scissor viewports — agent-routine.
- Before an asset is measured, its benchmark pane shows the stored thumbnail (or "Waiting to be measured"); after, its last measured frame — agent, for review.
- Look-phase Physics uses the run's physics seed; one look shake lasts 3 s of simulation time — agent, for review.
- The estimate uses a 4 s allowance per measured asset for loads and set-up — agent, for review.
- Load-phase bars are HTML segments, not uPlot (simpler, text values next to them) — agent-routine.
- The results page shows the most recent completed run of the test — agent, for review.
- New icons from the existing Phosphor set: `arrows-out-cardinal` (Shake), `timer` (estimate); added to `DESIGN.md` §6 — agent, for review.
**Checks:**
- PHP: 171 tests pass (882 assertions). New `SideBySideTest` (11: page, guests, 2–4 assets and motion rules, snapshots and selection order, run page creator-only with phone cool-down, submit with median checks and labels, incomplete/foreign/duplicate/wrong-motion payloads, other user, double submit, long vs short expiry, results with markers and "Too close to call", invalid run) and `BestMarkersTest` (5). Pint passed.
- JS: `npm run test:js` 15 pass (new: estimate equals the server values).
- Build: `npm run build` passes; the entry bundle contains neither Three.js (`WebGLRenderer`) nor cannon-es (`ConvexPolyhedron`).
- Browser (verification environment, headless Chrome, Apple M2 Metal, 60 Hz, fixtures Apple / Book / Chicken, Laptop — Integrated GPU, Target Desktop): look phase — 3 panes ready, a drag in pane 1 rotated all panes, Shift + arrows in pane 2 orbited all panes, Physics drop and Shake shown. Quick run (1 repetition, 1 s + 5 s) finished in 66 s. Full-protocol run (3 repetitions, 2 s + 10 s, Static / Spin / Physics) finished in 356 s (estimate "about 7 min"): all Smooth at 60 fps, all Target checks Pass; "Too close to call" for cold load and render work (spreads overlap), Smallest file on Book; **0 DOM changes during windows; 1 rendering pane per window**; memory back to baseline after dispose; seeds and orders recorded; no console errors. Results page at 1440, 768, 384 wide: no horizontal page scroll (tables scroll inside their containers).
**Issues:**
- Acceptance item "Three Bird fixtures" is pending (D57): the user tests it manually with their own Bird models.
- The fixture assets in `render_lab_verify` have no thumbnails and no metrics rows, so vertices and triangles show 0 there. Real uploads have both.
- Chrome rounds `performance.now()` to about 0.1 ms, so very small models give equal render work values; "Lightest to draw" then shows "Too close to call".
- The physics box is 3 × the asset size, so a small asset looks small in its box in the look phase. This follows `BENCHMARK.md` §4.2.
- The public sample report pages still use the old budget table (rebuilt in M4.6).
**Files:** `app/Http/Controllers/Lab/{SideBySideController,RunController}.php`; `app/Http/Controllers/Api/TestRunController.php`; `app/Http/Controllers/Results/ResultController.php`; `app/Http/Requests/Lab/{CreateTestRequest,SubmitResultsRequest}.php`; `app/Services/{TestSessionService,RunResultService}.php`; `app/Support/{RunEstimate,BestMarkers,SideBySideResults,ViewerConfig}.php`; `app/Models/TestRun.php`; `app/Jobs/ExpireTestRuns.php`; `config/lab.php`; `routes/web.php`; `resources/js/three/{panes,motion}.js`; `resources/js/three/methods/side-by-side.js`; `resources/js/features/{side-by-side,benchmark-run,run-estimate,engine-self-test}.js`; `resources/js/app.js`; `resources/css/components.css`; views `pages/lab/{side-by-side,run,index}`, `pages/results/show`, `components/performance/pane-toolbar`, `components/assets/tested-assets`, sidebar, `dev/engine`; tests `tests/Feature/{SideBySideTest,LabRunTest,AppScreensTest}.php`, `tests/Unit/BestMarkersTest.php`, `tests/js/run-estimate.test.mjs`; docs `DESIGN.md`, `BENCHMARK.md` §5, `PROJECT.md`, `LOG.md`.
**Next:** M4.4 Crowd Test (not started).

## 2026-10-08 — M4.2 Lab reset and foundations — completed (waiting for user review)

**Scope:** data reset, removal of the old test methods, new schema, Objects, Targets, and the three motion scenarios in the engine (`PROJECT.md` M4.2).
**Done:**
- Data reset (D40, D53), after two separate user confirmations. Backup in `storage/app/private/backups/2026-10-07-redesign/`: `test-data.json` (3 sessions, 6 session assets, 3 runs, 30 results, 52 budget evaluations), `asset-data.json` (40 assets, 40 metrics, 3 upload sessions, 60 upload items), `asset-files/` (80 files). Then deleted from `render_lab`: those rows and the 80 files. Users (1), tools (3), device classes (11), and option presets were kept.
- Removed: Comparison, Stress, and Headroom pages, routes, controllers, requests, JS runners (`three/methods/*`), `lab-config.js`, `budget-step.js`, `benchmark-run.js`, `results.js`, the benchmark page, the results partials, `BudgetEvaluator`, `TestSessionService`, `TestResultPresenter`, budget presets, budget evaluations, the instance-step list, and their tests. `POST /api/tests` and `POST /api/runs/{run}/results` return with M4.3/M4.4. The run lifecycle (start, invalidate, cancel, expiry) stays.
- Schema (`2026_10_08_000100_lab_redesign`): `objects`, `assets.object_id`, `targets` + `device_class_target`, new `test_sessions` / `test_session_assets` / `test_results`, `target_checks`. Migrated on `render_lab` and `render_lab_verify`; rollback tested on `render_lab_verify`.
- Objects: admin list; required Object column with bulk apply in bulk upload; Object on asset edit, detail, card, library table and filter, selector filter and label, search API. Fixture objects (Apple, Chicken, Book) come from the fixture register.
- Targets: admin list with structured fields and device class links; 4 seed Targets (`BENCHMARK.md` §6.2); shared Target step (`components/performance/target-step`, `TargetStep`, `target-step.js`); `Smoothness` (labels) and `TargetEvaluator` (Load, Smooth per motion, File size) on the server; the same label rule in `three/smoothness.js`.
- Motion engine (`three/motion.js`, `cannon-es` 0.20.0): Static, Spin (180°/s), Physics (simplified convex hull ≤ 32 points via farthest-point sampling, open kinematic box, seeded drop, 1.5 Hz shake, one 1/60 s step per frame). `measure.js` records physics time separately (`physics_p50_ms`, `physics_p95_ms`, physics samples) and uses the goal frame time from the Target for stutter. Seeded random moved to `three/random.js`.
- Engine self-test `/_engine` (local only): motion checkboxes, Target step, a label per motion, hull size, physics determinism replay.
- Lab page and sidebar: Side-by-Side (M4.3), Crowd (M4.4), and Tool summary (M4.5) shown as coming. Dashboard quick action links to the Lab. `/results/{test}` shows the test record until the results views exist.
**Decisions:**
- Physics warm-up lasts at least 2 s and at least 120 physics steps, so the shake starts at the same simulation step on every device (`BENCHMARK.md` §4.2) — agent, for review.
- The old migration `2026_10_04_000100` stays in place (it already ran); the redesign migration pauses foreign key checks while it rebuilds the test tables, because `reports` refers to `test_sessions` (both empty) — agent-routine.
- New icons from the existing Phosphor set: `users-three` (Crowd), `scales` (Tool summary), `shapes` (Objects), `flask` (Lab quick action) — agent, for review at the M4.3 design question (Q9).
- The public sample report pages still use the old budget table and method names (sample data). Their definitions moved to `SampleReports`; they are rebuilt with Targets in M4.6 — agent, for review.
**Checks:**
- PHP: 156 tests pass (745 assertions), including new `SmoothnessTest`, `TargetEvaluatorTest`, Object filter and required-field tests, Target admin tests, lifecycle tests. Pint clean.
- JS: `npm run test:js` 13 pass (smoothness edges; spin speed; hull ≤ 32 points and valid faces; a box hull keeps 8 corners; same physics states in two runs with one seed; physics time reported; objects stay in the shaken box). No cannon-es normal warnings.
- Build: `npm run build` passes. The entry bundle contains neither Three.js (`WebGLRenderer`) nor cannon-es (`ConvexPolyhedron`); cannon-es is only in the self-test chunk.
- Engine self-test (local, headless Chrome, Apple M2, Metal, 60 Hz, Target Desktop 60 fps): completed; Static / Spin / Physics all Smooth (p95 16.8 / 16.7 / 16.7 ms, 0% stutter, work p95 0.5 / 0.2 / 0.3 ms); physics p95 1.4 ms with 301 samples; hull 31 points, 58 faces, built in 6 ms; determinism replay identical after 421 steps; 0 DOM mutations during the windows; memory back to baseline; no console errors.
- Browser (verification environment, headless Chrome) at 1440, 768, 384 wide: Lab, Objects list and form, Targets list and form, upload, library table, asset detail and edit, dashboard — no horizontal page scroll, no console errors.
- Working database after migration: 4 Targets, 9 device class links, 0 objects, 0 assets.
**Issues:**
- `render_lab` has no objects yet. An admin must add the research plan's objects (Administration → Objects) before uploading, because Object is required.
- The bulk-apply of Object in the upload table was checked by feature tests (server), not by a browser drop test in this milestone.
- `DESIGN.md` is not updated yet (Q9): "no winner markers", old method icons, budget table, probe chart.
- Phone self-test (M4.1) is still open.
**Files:** `database/migrations/2026_10_08_000100_lab_redesign.php`; `app/Models/{AssetObject,Target,TargetCheck}.php`; `app/Services/{TargetEvaluator,RunResultService}.php`; `app/Support/{Smoothness,TargetStep,MetricDefinitions,OptionLists,SampleReports}.php`; `database/seeders/TargetSeeder.php`; `resources/js/three/{motion,smoothness,random,measure}.js`; `resources/js/features/{engine-self-test,target-step,upload,asset-selector}.js`; `resources/views/components/performance/target-step.blade.php`; asset, upload, admin, lab, and results views; `tests/Unit/{SmoothnessTest,TargetEvaluatorTest}.php`; `tests/js/{smoothness,motion}.test.mjs`; `package.json` (`cannon-es`).
**Next:** M4.3 Side-by-Side Test (not started; asks the visual design question Q9 at its gate).

## 2026-10-07 — Test redesign: specification rewrite — completed (waiting for user review)

**Scope:** replace the Comparison, Stress, and Headroom tests with a visual, interactive test scheme, based on the user's feedback that the old tests did not show the assets, could not be interacted with, and that Headroom and the 9-item budget were hard to understand.
**Done:** `BENCHMARK.md` rewritten (principles, motion scenarios, Side-by-Side, smoothness labels, best markers, Crowd, Tool summary, Targets, later methods, limitations). `PRD.md` updated (goals, non-goals, product areas, Object, methods, Targets, Lab flow, tool and object selection, results views, dashboard, reports, option lists, data model, endpoints, success criteria). `PROJECT.md` milestones M4.2–M4.6 replaced. `SITEMAP.md` routes, navigation, views, public data. `AGENT.md` approved dependency `cannon-es`, code structure. `DEPLOYMENT.md` seeder note and smoke test. No code changed. No data deleted.
**Decisions:** D39–D51 (see the decision table) — user. Agent proposals inside the rewrite, for review: "Loads in time" uses the median cold time to first frame; Crowd camera auto-frames each probe (the old Stress rule kept the camera fixed for all steps); one physics step of 1/60 s per frame for determinism; hull reduced to at most 32 points; physics box sizes and shake (1.5 Hz, 0.15 × box width); best markers only when spreads do not overlap ("Too close to call"); "Lightest to draw" marker (render work time) because frame interval is capped by the display; Crowd run expiry = estimate + 15 minutes; Tool summary uses the most recent valid result per asset on the selected device class.
**Checks:** none (documentation only). The old docs are kept in the session scratchpad for comparison.
**Issues:** open questions Q7, Q8, Q9. Crowd runs can be long (several assets × three motion scenarios × repetitions); the estimated time is shown before the run.
**Files:** `docs/BENCHMARK.md`, `docs/PRD.md`, `docs/PROJECT.md`, `docs/SITEMAP.md`, `docs/AGENT.md`, `docs/DEPLOYMENT.md`, `docs/LOG.md`.
**Next:** M4.2 Lab reset and foundations (not started; needs answers to Q7 and Q8 and explicit approval).

## 2026-10-07 — Fix: dates shown in the local zone (D38) — completed

**Scope:** User report: the default test name showed UTC time instead of local time (WIB, UTC+7).
**Done:** Cause: `config/app.php` uses `UTC` and every date was formatted in it. New config `lab.display_timezone` (`APP_DISPLAY_TIMEZONE`, default `Asia/Jakarta`) and a Carbon macro `display()` (copy in the display zone) in `AppServiceProvider`. Used for the default test name, "Completed …" on results, asset uploaded/deleted dates, asset test list, library dates, upload session start, last login, and report published dates. Storage stays UTC, so existing rows do not shift.
**Decisions:** D38 — one fixed display zone for every viewer — user.
**Checks:** `php artisan test`: 181 passed. New `test_times_are_shown_in_the_display_timezone` (20:30 UTC → default name "… — 2026-10-08 03:30"); last-login test now expects WIB (08:30 UTC → 15:30). Pint passed.
**Issues:** None.
**Files:** `config/lab.php`, `app/Providers/AppServiceProvider.php`, views under `resources/views/pages/{lab,results,assets,admin/users,public}` and `components/reports/report-card`, `.env.example`, `.env.verify.example`, `tests/Feature/{AppScreensTest,AdminUsersTest}.php`, `docs/DEPLOYMENT.md`.
**Next:** User review of M4.3 and M4.4; M4.5 not started.

## 2026-10-07 — Fix: Lab configuration kept across device confirmation — completed

**Scope:** User report: confirming the device class on a Lab configuration page cleared the selected assets.
**Done:** Cause: "Confirm or change" and the top-bar device chip open `/device`, which returns to a freshly loaded configuration page; the state lived only in Alpine. `features/lab-config.js` now saves a draft to `sessionStorage` (this tab only) when a link to `/device` is clicked, and restores it once on return: assets in their order, scenario, instance steps, test name, repetitions, timings, cap, scene presets, animation, and the budget (only when the user changed it; otherwise the preset suggested for the newly confirmed class applies). The draft is read once and removed, so a later reload starts empty as before. Works on Comparison, Stress, and Headroom.
**Decisions:** Draft in `sessionStorage`, not the server — a per-tab convenience, no data model change — agent-routine (bug fix).
**Checks:** Browser (verification environment): Headroom via "Confirm or change" and Stress via the top-bar chip — mixed scenario, Apel + Ayam, repetitions 2, cap 256, jank limit 7 → all restored after confirming "Desktop — Dedicated GPU"; a reload afterwards starts empty; no console errors. Build succeeded.
**Issues:** None.
**Files:** `resources/js/features/lab-config.js`.
**Next:** User review of M4.3 and M4.4; M4.5 not started.

## 2026-10-06 — M4.4 Headroom Test — completed

**Scope:** `BENCHMARK.md` §4.4 — doubling + binary search, pass criterion (p95 and jank), cap, 3-minute time limit per search, repeated and mixed scenarios, results with a headroom bar chart and the probe sequence chart. Built at the user's request before the M4.3 review.

**Done:**
- Search `three/methods/headroom-search.js` (`searchHeadroom`, `probePasses`), free of Three.js and the DOM so it runs under Node: probe at 1 (fail → 0); double up to the cap (a cap that is not a power of two is probed exactly); binary search until the gap ≤ max(1, 5% of the last pass); the time limit is checked before each probe after the first and keeps the last pass. Unit tests in `tests/js/headroom-search.test.mjs` (`npm run test:js`, Node's built-in test runner, no new dependency).
- Runner `three/methods/headroom.js`: one benchmark viewer for the whole run; per repetition and search (repeated: one per asset; mixed: one for the set, round-robin) a cold load, the Stress grid and clones, camera framed once on the grid at the cap count; each probe = GPU upload, 1 s warm-up, 4 s window. Each result stores the summary of the last passing probe, `headroom_instances`, the probe list (instances, pass, p95, jank), and the flags `cap_reached` / `time_limit_reached`. Progress strip names the probe count between windows.
- Server: `CreateTestRequest` — `configuration.cap` required for Headroom (integer 1–4096); warm-up and measurement inputs not used. `TestSessionService` stores the fixed probe protocol (`probe_warmup_s` 1, `probe_window_s` 4, `time_limit_s` 180, `instance_mode` clone) and rejects a budget without p95 or jank. `SubmitResultsRequest` / `RunResultService` validate and store probes (max 64) and flags; a Headroom result needs `headroom_instances`; `min_headroom_instances` is evaluated on the median.
- Configuration `/lab/headroom` (shared configure page): scenario switch, cap input (default 1,024), note on the probe protocol. Run page preflight describes the probe protocol for Headroom.
- Results (`pages/results/partials/headroom`, `TestResultPresenter::headroomGroups()/headroomChart()/budgetColumns()`): headroom per search as median (min–max) or "≥ cap", per-repetition values, "Stopped by" badges (cap reached, time limit, budget failed); budget table (mixed set = one column); uPlot bar chart with printed values in selection order; probe sequence chart per search (log-2 instance axis, filled circle = pass, cross = fail, plus a text key and a probe list with Pass/Fail badges — never color only).
- Sidebar, Lab page, and dashboard quick actions link to the Headroom Test.
- Fix (found during the M4.4 browser run): the asset selector's hover preview read `this.$root` after an `await`, which Alpine does not resolve there ("Cannot read properties of undefined (reading 'querySelector')"). It now reads `$root` before the await.

**Decisions:**
- Both pass-criterion budget items must be enabled with a limit; the probe uses `max_p95_frame_ms` as the budget frame time for jank — follows from `BENCHMARK.md` §4.4, written there — agent-routine.
- Probe protocol (1 s / 4 s / 3 min) is fixed, not editable in the form — as written in `BENCHMARK.md` §4.4 — agent-routine.
- Each result row keeps the frame summary of the last passing probe (or of probe 1 when it fails), so the page can show context metrics — agent-routine.
- JS unit tests use Node's built-in `node --test` (no package) — agent-routine.

**Checks:**
- `php artisan test`: 180 passed (897 assertions). New `HeadroomTest` (10): fixed probe protocol stored; cap required and ≤ 4096; jank item required; mixed needs one tool; probes and flags stored, median headroom evaluated (880/900/1024 → 900 pass; 30/32/34 → 32 fail), load items not evaluated; result without `headroom_instances` rejected; results page ("≥ 1,024", "Cap reached (1,024)", "Time limit (3 min)", "42 (40–44)", probe list, probe chart, selection order); mixed set = one search and one column; run page preflight; configuration page.
- `npm run test:js`: 7 passed (fail at 1 → 0, doubling + binary search gap, 5% gap, cap stop and flag, non-power-of-two cap, time-limit stop keeps the last pass, pass criterion).
- **End to end (verification environment, headless Chrome, Apple M2, 60 Hz):**
  - Run 318: Simple Triangle vs Apel, cap 64 → both "≥ 64", **"Cap reached (64)" recorded and shown**, `renderers_created: 1`.
  - Run 319: Apel, cap 4,096, 2 repetitions → 13 probes each, "≥ 4,096", cap flag recorded.
  - Run on Ayam with p95 ≤ 16 ms (below the 16.7 ms frame interval) → probe 1 fails, headroom 0, "Budget failed", Fail badge, cross marker on the probe chart.
- `/lab/headroom`, two results pages, and the Headroom run page at 1440/820/384: no horizontal page scroll, no console errors. Keyboard: scenario switch by arrow keys; Tab goes Repetitions → Cap; the probe list opens with Enter. Selector hover preview renders. Entry bundle has no Three.js; no `px` design values or raw colors; Pint passed; build succeeded.

**Issues:**
- Acceptance "a light fixture reaches a higher headroom than a heavy fixture" is **not shown**: the fixtures are too light (largest 112 triangles), and on this machine every fixture reaches the 4,096 cap. Needs a heavier asset (for example a textured model with tens of thousands of triangles) on the user's device.
- The time-limit stop was verified in unit and feature tests only, not in a real browser run (a 3-minute search did not happen with the fixtures).
- Verification run 312 was left `running` by a failed script attempt; it expires after 30 minutes (verification database only).
- Open from earlier: p95 seed limit 16.7 ms on 60 Hz displays (methodology decision); phone self-test (M4.1).

**Files:** `resources/js/three/methods/{headroom,headroom-search}.js`, `resources/js/features/{benchmark-run,lab-config,results,asset-selector}.js`, `app/Http/Requests/Lab/{CreateTestRequest,SubmitResultsRequest}.php`, `app/Services/{TestSessionService,RunResultService}.php`, `app/Support/TestResultPresenter.php`, `app/Http/Controllers/Lab/LabController.php`, `routes/web.php`, `resources/views/pages/lab/{configure,index,run}.blade.php`, `resources/views/pages/results/{show,partials/headroom}.blade.php`, `resources/views/pages/dashboard/index.blade.php`, `resources/views/components/navigation/sidebar.blade.php`, `tests/Feature/{HeadroomTest,AppScreensTest}.php`, `tests/js/headroom-search.test.mjs`, `package.json`, `docs/BENCHMARK.md`.

**Next:** M4.5 — Results, reports, publishing (not started).

## 2026-10-04 — M4.3 Stress Test — completed

**Scope:** `BENCHMARK.md` §4.3 — repeated and mixed scenarios, same-tool rule (UI + server), step presets with an editable list (max 500 instances), fixed camera for the largest step, results with chart and per-step budget.

**Done:**
- Migration `2026_10_04_000100_add_step_instances_to_budget_evaluations_table` (D37; run on `render_lab` and `render_lab_verify`, adds a nullable column and an index). `RunResultService::evaluations()` for Stress: p95 frame interval, jank, and draw calls at each step (median of repetitions per step); file size, texture memory, and instances at budget once (`step_instances` null). Submit rejects results for steps that are not in the test.
- `CreateTestRequest` / `TestSessionService`: `configuration.steps` required for Stress (1–12 counts, ascending, distinct, each 1–500); repeated = exactly one asset, mixed = 2–8 assets of one tool (tool constraint stored); `instance_mode: clone` stored.
- Runner `three/methods/stress.js`: **one benchmark viewer (one renderer, one scene) for the whole run**; per repetition the assets load once (cold) and are reused; per step a group of `object.clone()` instances (shared geometry and materials) on a deterministic square XZ grid, spacing 1.2 × the largest bounding-box diameter, round-robin in selection order for mixed; camera framed once on the grid of the largest step (`frameBox()`), then fixed; GPU upload, warm-up, measurement window per step; cool-down between repetitions only. Records `renderers_created`, `scenes_used`, `instance_mode`, steps, and refresh rate in the environment. Progress bar counts step measurements.
- Configuration (`pages/lab/configure`, shared with Comparison; `/lab/stress`): scenario switch (Repeated / Mixed), selector, instance-step preset + editable list with inline validation, settings, budget step (Stress items; frame items noted as per step), device. Run disabled with the reason, including "All assets in a mixed scenario must come from one authoring tool. Selected: …".
- Results (`pages/results/partials/stress`, `TestResultPresenter::stressGroups()/stressChart()`): budget items evaluated once; budget at each step (instances × p95/jank/draw calls with badges and values); metrics per step as median (min–max); uPlot line chart of p95 frame interval, render work p95, and GPU p95 (when exposed) against instance count with the budget frame time as a dashed line and label (lines differ by color, dash, and marker). Summary line shows instances at budget (0 when no step passes).
- Sidebar and Lab page link to the Stress Test.

**Decisions:**
- D37 — step column — user.
- "Meets all" summary is not shown for Stress; the page shows instances at budget instead (a larger step is expected to fail) — agent-routine.
- Load metrics are not recorded for Stress (TTFF items do not apply, `BENCHMARK.md` §6.1) — agent-routine.
- At most 12 steps per test — agent-routine.

**Checks:**
- `php artisan test`: 170 passed (842 assertions). New `StressTest` (7): API rejects a mixed test with two tools; scenario asset counts and step lists validated (order, > 500, empty); mixed one-tool test stores tool constraint, steps, and instance mode; per-step evaluation (p95 pass/pass/fail at 1/5/10) and instances at budget once (5); unknown step rejected; results page (mixed set label, budget at each step, "Instances at budget: 5", spread "15.5 (15–16)", chart, steps); configuration page.
- **Acceptance, end to end (verification environment):** mixed selection Apel (Blender) + Book (Spline) → **Run disabled** with "All assets in a mixed scenario must come from one authoring tool. Selected: Blender, Spline."; a **forced `POST /api/tests` returned 422** "A mixed scenario needs assets from one authoring tool."; repeated run of Apel at 1/5/10/25 instances × 2 repetitions completed (run 176) with **`renderers_created: 1`, `scenes_used: 1`, `instance_mode: clone`**; results page shows the per-step budget, metrics per step, and the **chart of p95 frame interval and render work p95 against instance count with the dashed budget line**. Rendered triangles grew 112 → 2,800 and draw calls 2 → 50 with the instance count.
- `/lab/stress`, `/results/{stress test}`, `/lab` at 1440/820/384: no horizontal scroll, no console errors; scenario switch works with arrow keys and shows a focus ring; step-list errors show inline. No `px` design values or raw colors. Pint passed. Build succeeded.

**Issues:**
- In this headless run the display refresh estimate was 30 Hz (the warning showed), so every step failed the 16.7 ms p95 limit and jank was high (intervals near 2 × 16.7 ms). On a real 60 Hz display the values will differ; the low-refresh warning covers this case.
- Open from M4.2: the p95 seed limit of 16.7 ms on 60 Hz displays (methodology decision); phone self-test (M4.1).

**Files:** `database/migrations/2026_10_04_000100_add_step_instances_to_budget_evaluations_table.php`, `app/Services/{RunResultService,TestSessionService}.php`, `app/Http/Requests/Lab/CreateTestRequest.php`, `app/Http/Controllers/Lab/LabController.php`, `app/Support/TestResultPresenter.php`, `app/Models/BudgetEvaluation.php`, `routes/web.php`, `resources/js/three/{viewer.js,methods/stress.js}`, `resources/js/features/{lab-config,benchmark-run,results}.js`, `resources/views/pages/lab/{configure,index,run}.blade.php`, `resources/views/pages/results/{show,partials/stress}.blade.php`, `resources/views/components/navigation/sidebar.blade.php`, `tests/Feature/{StressTest,LabRunTest,AppScreensTest}.php`, `docs/PRD.md`.

**Next:** M4.4 — Headroom Test (not started).

## 2026-10-04 — M4.2 addition: benchmark progress bar — completed

**Scope:** User request: a progress bar during the benchmark.
**Done:** The benchmark strip (`pages/lab/run`) gains "Measurement n of N" and a thin bar (`role="progressbar"`, `aria-valuenow`) for completed measurements. `features/benchmark-run.js` `progress(text, { done, total })` sets it; `methods/comparison.js` passes the counts before each asset; it reaches 100% at "Saving the results…". The bar has no transition and only changes between measurement windows (`BENCHMARK.md` §4.1 step 6, `AGENT.md` §7). Colors `neutral-0` on `neutral-600` (5.45:1). `DESIGN.md` §9.3 updated.
**Decisions:** The request — user. Step-wise bar (no continuous fill) to keep the no-DOM-updates-during-measurement rule — agent.
**Checks:** Verification environment, 2 assets × 1 repetition: the bar changed at 0.7 s (0%, before the first load), 14.0 s (50%, after the first asset's window and cool-down), and 20.2 s (100%, saving) — never inside a measurement window. Run completed and opened the results. `php artisan test`: 163 passed. Build succeeded.
**Issues:** none.
**Files:** `resources/views/pages/lab/run.blade.php`, `resources/js/features/benchmark-run.js`, `resources/js/three/methods/comparison.js`, `resources/css/components.css`, `docs/DESIGN.md`.
**Next:** M4.3 — Stress Test (not started).

## 2026-10-04 — M4.2 Comparison Test — completed (approved by the user)

**Scope:** Asset Selector modal (`PRD.md` §8.2); Comparison configuration; execution per `BENCHMARK.md` §4.2 (cold/warm load, warm-up, window, repetitions, shuffle, cool-down); results view.

**Done:**
- `GET /api/assets` (`Api\AssetSearchController`): name search, tool, author, format filters; registered, not deleted assets; at most 60 results; returns thumbnail and file URL for the preview.
- Asset Selector (`components/assets/asset-selector`, `features/asset-selector.js`): native modal dialog (focus trap, Escape), search and filters, multi-select kept across searches by id, "Select all visible", "Clear", max 8 with a message, thumbnail/name/tool/author/size/triangles per row, live 3D preview on hover or focus (shared viewer, preview mode, wide screens), selected-assets summary, `role="listbox"` with arrow keys and Space/Enter; dispatches the selection in selection order.
- Comparison configuration (`pages/lab/comparison`, `features/lab-config.js`, `Lab\LabController`): test name, selected assets with move up/down and remove, settings with real presets (resolution, camera, lighting, background; defaults preselected), animation toggle, budget step, device chip; Run disabled with the reason (no asset / too many / no device class). Run → `POST /api/tests` → `POST /api/tests/{test}/runs` with the collected context → benchmark page.
- Runner `three/methods/comparison.js`: seeded shuffle (mulberry32 + Fisher–Yates, new shuffle per repetition from one seed; seed and orders recorded), per asset a fresh benchmark viewer, renderer priming, refresh estimate once (warning when the display is slower than the budget frame rate), cold load (`reload`, unique URL) → dispose with memory check → warm load (same URL), optional first animation clip, warm-up, measurement window, renderer disposed, cool-down 3 s (desktop/laptop) or 10 s (phone/tablet) from the confirmed class. Jank uses the budget p95 limit, else 1000 / refresh rate.
- Benchmark page (`pages/lab/run`, `features/benchmark-run.js`): preflight checklist and Start, WebGL 2 check, screen wake lock when available, progress strip updated between windows only, Cancel, invalidation → reason + "Run again" (new run of the same test), completion → submit → `/results/{test}`.
- Results (`Results\ResultController`, `App\Support\TestResultPresenter`, `pages/results/show`, `features/results.js`): summary line ("n of m assets meet all enabled budget items"), tested assets, budget table from stored evaluations (assets in selection order), runtime metrics as median (min–max) with explanations and the Unstable note, load phases cold · warm, uPlot charts (stacked load-phase bars cold and warm; frame-interval histogram per asset over all repetitions with the budget p95 limit as a dashed reference line and label), configuration and environment; invalid/cancelled runs show their reason and no budget. Owner or admin only.
- `measure.js`: `coolDown()`, `warmUp()` with a per-frame hook, `guard.watchCanvas()`/`unwatchCanvas()`. `loaders.timedLoad()` also returns the animations.

**Decisions (agent-routine unless stated):**
- Preflight is a panel with a Start button, so the checklist (`BENCHMARK.md` §4.1 step 1) is read before measurement starts.
- Results show the latest completed run of a test (MVP: one run per test).
- Histogram with 0.5 ms bins (DESIGN.md §10 allows a histogram or box plot).
- Load-phase bars are stacked by drawing cumulative sums; phases are told apart by color, order, and legend label.
- "Run again" after an invalid run starts a new run of the same test (the configuration is not copied).

**Checks:**
- `php artisan test`: 163 passed (802 assertions). New `ComparisonTest` (9): selector search by name/tool/author/format, result fields and deleted assets excluded, guests 401, configuration page with selector and real presets, run config in selection order with file URLs and device category, results with median and spread ("16.5 (16–30)"), Unstable, load phases, budget columns in selection order, summary line, access (other user 403, admin 200), invalid run shows its reason and no budget. Unit test for number formatting.
- **Acceptance, end to end (verification environment, headless Chrome, 1440 wide):** device class confirmed; selector used only by search, tool filter, and keyboard (ArrowDown + Space) — selection kept across searches ("2 selected: Apel, Buku" → "3 selected: Apel, Buku, Ayam"); live preview canvas shown; no IDs typed; configuration with 3 repetitions, 1 s warm-up, 5 s window; benchmark page progress "Repetition 1 of 3 · Asset 1 of 3: Apel"; run completed (run 67) and redirected to `/results/216`. Results: budget columns Apel, Buku, Ayam (selection order); p95 medians with spread ("16.7 (16.7–16.8)"); load phases cold · warm (TTFF 63.1 · 15 ms, transfer 10,772 · 0 bytes); 5 charts; GPU p95 0.3–0.38 ms.
- Selector at 1440/820/384: arrow keys + Space select, "Select all visible" stops at 8, Escape closes, no horizontal scroll, no console errors. Results page at 1440/820/384: no horizontal scroll, no console errors.
- Entry bundle: no Three.js or uPlot code (only chunk file names in the preload map). No `px` design values or raw colors. Pint passed. Build succeeded.
- Fixed during the work: the selector closed with `this.$el` (the clicked button) instead of `this.$root`; disposing a viewer fired `webglcontextlost` (from `forceContextLoss()`) and invalidated the run — the guard now stops watching a canvas before an intentional dispose; `$refs` inside `x-for` rows; integer formatting dropped zeros ("5,800" showed as "5,8"); small file sizes showed as 0 MB; load chart y-axis did not start at 0.

**Issues:**
- On a 60 Hz display the frame interval is about 16.7 ms with jitter, so a p95 of 16.8 ms fails the seeded 16.7 ms limit (Ayam in the end-to-end run). This is the methodology working as written; the seed limit may be worth reviewing (for example 17.0 ms) — a methodology decision for the user.
- The M4.1 phone self-test is still to be run by the user.
- Test history (`/results`) and the report editor are still sample screens (M4.5).

**Files:** `app/Http/Controllers/Api/AssetSearchController.php`, `app/Http/Controllers/Lab/{LabController,RunController}.php`, `app/Http/Controllers/Results/ResultController.php`, `app/Support/TestResultPresenter.php`, `routes/web.php`, `resources/js/three/{measure,loaders}.js`, `resources/js/three/methods/comparison.js`, `resources/js/features/{asset-selector,lab-config,benchmark-run,results}.js`, `resources/js/app.js`, `resources/views/components/assets/asset-selector.blade.php`, `resources/views/pages/lab/{comparison,run}.blade.php`, `resources/views/pages/results/show.blade.php`, `resources/css/components.css`, `tests/Feature/{ComparisonTest,AppScreensTest}.php`, `tests/Unit/BudgetEvaluatorTest.php`, `docs/PRD.md`.

**Next:** M4.3 — Stress Test (not started). Data-model decision needed: per-step budget evaluations.

## 2026-10-03 — M4.1 Benchmark engine — completed (approved by the user on 2026-10-04; phone self-test still open)

**Scope:** Benchmark mode of the viewer and benchmark shell; `measure.js` (frame loop, two clocks, GPU timer, percentiles, jank, samples); `environment.js` (context, refresh-rate estimate, device class suggestion), `/device` confirmation and header chip; run lifecycle (create test, start run, invalidation, cancel, submit, expiry job); server result validation, aggregation, `BudgetEvaluator`, storage; shared budget step; chart library; local-only engine self-test page.

**Done:**
- `three/viewer.js`: `createViewer(…, { mode: 'benchmark' })` → `createBenchmarkViewer()` with the `BENCHMARK.md` §2 settings (antialias off, `powerPreference: 'high-performance'`, pixel ratio 1, fixed drawing buffer from the resolution preset scaled by CSS, `NoToneMapping`, no shadows, no controls, no gizmo, no overlays, no animation loop, `preserveDrawingBuffer: false`, camera auto-framed once and then fixed).
- `three/measure.js`: `measureWindow()` (frame interval from rAF timestamps, CPU render work time around `renderer.render`, GPU time from `EXT_disjoint_timer_query_webgl2` aligned per frame and `null` when unavailable or disjoint; per-frame `beforeFrame` hook for the later Camera Path; samples capped at 3,600 with `truncated`), `summarise()` (all §3.3 keys, nearest-rank percentiles, jank > 2 × budget frame time, 1% low), `estimateRefreshRate()`, `warmUp()`, `primeRenderer()`, `createRunGuard()` (hidden tab, focus lost > 1 s, WebGL context lost) and `InvalidRun`. No DOM access.
- `three/loaders.js`: `coldWarmUrl()`, `COLD_CACHE`/`WARM_CACHE`, `timedLoad()` (fetch, parse, GPU upload, first frame, TTFF) for the method runners.
- `three/environment.js`: `collectContext()` (§3.4 browser signals; `null` when not exposed) and `suggestDeviceClass()` (§7.1 rules: category, platform, iPhone → Standard, Android GPU family → tier, desktop/laptop GPU type; preselects only when exactly one class fits, never laptop vs desktop).
- `/device` (`Lab\DeviceController`, `ConfirmDeviceRequest`, `features/device.js`): live signals, suggestion badge, preselect from session → `localStorage` → single suggestion; confirmation stored in the session (`device_class_id`), `return_to` limited to app paths. Header chip via a view composer.
- API (`Api\TestRunController`): `POST /api/tests` (`CreateTestRequest`, `TestSessionService`: snapshots of resolution/camera/lighting/background/instance-step presets and the final budget; same-tool rule for mixed scenarios), `POST /api/tests/{test}/runs` (`StartRunRequest`: owner or admin, confirmed device class required), `POST /api/runs/{run}/results` (`SubmitResultsRequest`: schema, known keys only, ≤ 3,600 samples per series, creator only, running and not expired), `POST /api/runs/{run}/invalidate`, `POST /api/runs/{run}/cancel`.
- `RunResultService`: start, invalidate, cancel, submit (stores `test_results` with summary, load phases, and samples; evaluates the budget on the median of repetitions per asset or asset set; completes the run), `expireStale()`. `BudgetEvaluator` (pass/fail/not measured; disabled and non-applicable items produce no row; MB conversion; exact comparison, rounded storage). `App\Support\Aggregate` (median, min–max, "Unstable" when the p95 spread > 25% of the median). `ExpireTestRuns` job implemented (running runs older than 30 minutes → `invalid`, reason `expired`). Models `TestRun`, `TestResult`, `BudgetEvaluation`; `TestSession::assets()`/`runs()`.
- `/lab/runs/{run}` (`Lab\RunController`): benchmark shell for the run creator while running (410 otherwise), run config as JSON, Cancel through the API. The method runners plug in from M4.2.
- `components/performance/budget-step` + `features/budget-step.js`: preset select, items that apply to the method with enable flag, limit, unit, explanation; preset linked to the confirmed device class suggested, else the default. Used on `/lab/comparison` (`Lab\LabController`).
- uPlot (`uplot` 1.6.32) installed; `features/sample-chart.js` draws frame-interval and render-work samples after a run (dynamic import, token colors).
- Local-only `/_engine` (`dev/engine.blade.php`, `features/engine-self-test.js`): one fixture, refresh estimate, cold + warm load, 1 s warm-up, 5 s window, mutation observer during the window, memory check after dispose, raw summaries, sample chart. Nothing is sent to the server.

**Decisions:**
- D35 — cold `reload` + unique URL, warm same URL with the default cache — user (asked with the measured evidence: warm transfer size was 10,772 bytes, same as cold).
- D36 — uPlot — user.
- Expired runs are stored as `invalid` with reason `expired` (the status list has no `expired`) — agent-routine.
- `POST /api/runs/{run}/cancel` added to the JSON endpoints (the user can cancel a run, `PRD.md` §8.1) — agent-routine; `PRD.md` §13 updated.
- Jank in the self-test uses 16.7 ms as the budget frame time; real runs use the test's `max_p95_frame_ms` limit — agent-routine.
- For a mixed asset set the file size and texture memory budget items use the sum over the set's assets — agent; revisit in M4.3 if needed.
- `primeRenderer()` renders one physically based material before the memory baseline, because three r186 creates a shared DFG lookup texture on first use — agent, from source inspection.

**Checks:**
- `php artisan test`: 155 passed (761 assertions). New: `BudgetEvaluatorTest` (10 unit tests: pass, fail, ≥ rule, not measured, disabled, non-applicable method, MB conversion, meets-all, median/spread, unstable) and `LabRunTest` (15: snapshots and later preset edits, same-tool rule, deleted asset and unknown item rejected, device class required, device confirmation and header chip, class and return-path validation, budget preset suggestion, benchmark page creator-only and 410 after the run, submit with median evaluation (an outlier repetition does not fail p95), payload validation (3,601 samples, foreign asset, repetition, frames), creator-only submit and no submit after cancel, expired run, invalidation keeps the reason and stores no evaluations, expiry job, another user cannot start a run).
- **Engine self-test (desktop Chrome, Apple M2, Metal):** status completed; all §3.3 metrics present — frames 301, interval p50/p95/p99 16.7/16.7/16.8 ms, fps avg 60, 1% low 59.5, jank 0, work p50/p95 0.2/0.4 ms, **GPU p50/p95 0.29/0.35 ms** (extension available), draw calls 2, rendered triangles 112, programs 1, geometries 2, textures 1, JS heap 7.9 MB, refresh estimate 60 Hz; cold transfer 10,772 B / TTFF 77.7 ms, warm transfer 0 B / TTFF 16.4 ms; memory back to baseline after dispose; **0 DOM mutations during the window**; sample chart rendered; `data-benchmark` removed after the run.
- **Invalidation:** hiding the tab during the run → `invalid`, "The tab was hidden during measurement."
- Browser (verification environment, 1440/820/384 wide): `/device` shows signals and the suggestion; confirming updates the header chip and `localStorage`; the budget step suggests Desktop — Standard for Laptop — Integrated GPU and switching to Mobile — Mid-range changes p95 16.7 → 33.3; a real test + run created through the API (201/201), the benchmark page has `data-benchmark="running"`, Cancel returns to `/lab`, the run is `cancelled`, and the run page then returns 410. No horizontal scroll; no console errors (except the expected 410 check).
- Entry bundle: no Three.js and no uPlot (0 matches). No `px` design values or raw colors in `resources/`. Pint passed. `npm run build` succeeded.

**Issues:**
- **Phone check pending (acceptance):** the self-test must also run on one phone. Run `php artisan serve --host=0.0.0.0 --port=8000` on the Mac and open `http://<Mac LAN IP>:8000/_engine` on the phone (same Wi-Fi). While the server listens on the LAN, every page is reachable from that network (login still applies to app pages; `/_ui`, `/_viewer`, `/_engine`, `/_fixtures` are public in the local environment). Stop it afterwards.
- `budget_evaluations` has no step column, so Stress "evaluated at each step" (`BENCHMARK.md` §4.3) cannot be stored per step yet; ask at M4.3 (data-model decision).
- Warm GPU upload time varies (12–178 ms across runs) on this machine; it is reported as measured.

**Files:** `resources/js/three/{viewer,measure,environment,loaders}.js`, `resources/js/features/{device,budget-step,benchmark-run,engine-self-test,sample-chart}.js`, `resources/js/app.js`, `app/Services/{BudgetEvaluator,RunResultService,TestSessionService}.php`, `app/Support/Aggregate.php`, `app/Models/{TestRun,TestResult,BudgetEvaluation,TestSession}.php`, `app/Http/Controllers/Api/TestRunController.php`, `app/Http/Controllers/Lab/{DeviceController,RunController,LabController}.php`, `app/Http/Requests/Lab/*`, `app/Jobs/ExpireTestRuns.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php`, `resources/views/pages/{device/show,lab/run,lab/comparison}.blade.php`, `resources/views/components/performance/{budget-step,device-chip}.blade.php`, `resources/views/dev/engine.blade.php`, `resources/css/components.css`, `tests/Unit/BudgetEvaluatorTest.php`, `tests/Feature/{LabRunTest,AppScreensTest}.php`, `package.json`, `docs/{BENCHMARK,AGENT,PRD,DESIGN}.md`.

**Next:** M4.2 — Comparison Test (not started).

## 2026-10-03 — M3.3 addition: bulk delete — completed

**Scope:** User request after M3.3: a bulk delete in the asset library table, with a permanent option for admins that is greyed for assets used in tests (D34).
**Done:** "Bulk delete" toggle (`?bulk=1`, switches to the table view) adds a checkbox column, a "Tests" column ("Used in N tests" badge), select-all for the page, and a sticky bar with **Delete (n)** and, for admins, **Delete permanently (m)** where m counts only selected rows not used in tests (disabled at 0, with a note how many selected rows are used). Both open a confirm dialog that submits the form (`DELETE /assets/bulk`, `DELETE /assets/bulk/force`). Rows the user may not delete have a disabled checkbox. In the Deleted view only the permanent action is shown, and used rows cannot be selected. `AssetDeletionService` handles single and bulk soft/permanent delete with per-asset checks; `BulkAssetsRequest` (1–100 ids); `AssetPolicy::forceDeleteAny`; `resources/js/features/asset-bulk.js` (counts and dialogs).
**Decisions:** D34 — user. Bulk permanent delete may target assets that are not soft-deleted yet (the user asked for it in the normal table); the confirm dialog states that it cannot be undone — agent, from the request.
**Checks:** `php artisan test`: 131 passed (7 new: bulk mode markup for admin and user, owner bulk delete skips others with the exact message, admin bulk delete, permanent bulk delete skips an asset used in a test and removes the other's files, permanent bulk delete on deleted assets, 403 for users, selection validation). Browser (verification environment, 1440 and 384 wide): toggle → table with checkboxes; both buttons disabled until a selection; "Delete (2)" / "Delete permanently (2)"; dialog opens and Cancel closes it; confirm → "Deleted 2 assets. They can be restored."; both restored again; no console errors; no horizontal scroll. Fixed during the work: `withCount('testSessions')` must run after `AssetFilters::apply()`, because `apply()` sets `select('assets.*')`.
**Issues:** The verification database has no tests yet, so the greyed "Used in N tests" state is proven by the feature test, not seen in the browser.
**Files:** `app/Services/AssetDeletionService.php`, `app/Http/Requests/Assets/BulkAssetsRequest.php`, `app/Http/Controllers/Assets/AssetController.php`, `app/Policies/AssetPolicy.php`, `app/Support/AssetFilters.php`, `routes/web.php`, `resources/views/pages/assets/index.blade.php`, `resources/js/{app.js,features/asset-bulk.js}`, `tests/Feature/AssetLibraryTest.php`, `docs/{PRD,SITEMAP}.md`.
**Next:** M4.1 — Benchmark engine (not started; chart library decision Q4).

## 2026-10-03 — M3.3 Library and asset detail — completed (approved by the user)

**Scope:** `PRD.md` §7.2–7.4 — library grid/table, search, filters, sort, query-parameter state, deleted filter; asset detail with preview and metric sections; download; edit metadata; soft delete, restore, permanent delete rules.

**Done:**
- `Assets\AssetController`: `index`, `show`, `edit`, `update`, `destroy` (soft), `restore`, `forceDestroy`, `download`, `file`, `thumbnail`. Form Requests `UpdateAssetRequest`, `DeleteAssetRequest`, `RestoreAssetRequest`, `ForceDeleteAssetRequest` (all through `AssetPolicy`). Deleted assets stay reachable for detail, restore, permanent delete, files, and download (`withTrashed()` on those routes).
- `App\Support\AssetFilters`: `q` (name, author, description), `tool`, `author`, `format`, `uploaded_by`, `size_min`/`size_max` (MB), `from`/`to` (upload date), `sort` (newest/oldest, name, file size ↑↓, triangles ↑↓), `deleted`, `view` (grid/table). Invalid values are ignored; `LIKE` wildcards in user input are escaped. Pagination 24 per page keeps the query string.
- `App\Support\AssetFiles`: stored-name file lookup (no path segments), GLB download as is, GLTF download as a ZIP of the folder, file deletion on permanent delete.
- Library page (`pages/assets/index`): plain GET form, filter state in the URL, grid with thumbnails or table, empty states (no assets / no match), Previous/Next pagination. Asset detail (`pages/assets/show`): the shared viewer in preview mode loading the real model through `/assets/{asset}/files/{name}` (GLTF dependents resolve relative to it), metadata, asset metrics (`components/assets/asset-metrics`), tests that used the asset (own tests; admins see all; other users' tests counted), confirm dialogs for delete and permanent delete, deleted banner with Restore. Edit page: metadata only; Author editable for admins, read-only for users.
- `Asset` relations: `testSessions()`, `metrics()`, `testUsageCount()`. Upload session record rows link registered items to the asset thumbnail. `data-dialog-close` handler in `app.js` (no inline scripts). Sample asset data removed from `SampleScreens` (dashboard and the Comparison page still use sample assets until their milestones).

**Decisions:**
- D33 — permanent delete blocked when any test uses the asset — user (asked: the schema protects test results).
- GLTF download as a ZIP of the model folder — agent-routine (a GLTF needs its dependents).
- The "Deleted" filter shows deleted assets only (with Restore on the detail page) — agent-routine, following "show deleted assets with a Deleted filter".
- Permanent delete is offered on deleted assets (soft delete first) — agent-routine, to avoid one-click irreversible deletes.
- Viewer files are cached privately for one hour (`Cache-Control: private`) — agent-routine.

**Checks:**
- `php artisan test`: 124 passed. `AssetLibraryTest` (19, storage faked): search over name/author/description; filters by tool, format, uploader, size range, date range; sort; **invalid values ignored**; **form keeps the URL state (works without JavaScript)**; deleted assets hidden and listed under the Deleted filter; detail shows metadata, metrics, and the viewer source; detail lists only tests the viewer may see; **ownership**: owner edits metadata but a submitted Author is ignored, admin changes Author, another user gets 403 on edit/update/delete/restore, owner and admin soft-delete and restore, only an admin deletes permanently (files removed); **permanent delete blocked for an asset in a published report** (seeded test session + published report) and for an asset used by any test; GLB download and GLTF ZIP (2 files); viewer files by stored name only (traversal 404); guests redirected from files, download, thumbnail; thumbnail served.
- Browser (verification environment, port 8001; 1440 and 384 wide): library grid with thumbnails, GLTF filter + table view + sort by name; asset detail viewer reaches `ready` for a GLB and for the textured GLTF (its .bin and .png load through the file route); delete dialog opens and Cancel closes it; no console errors; no horizontal page scroll.
- Pint: passed. `npm run build`: success.

**Issues:**
- Fixture assets registered by `DevFixtureSeeder` have no thumbnail and no geometry metrics (the seeder cannot run the browser inspection); assets uploaded through the UI have both.
- The dashboard counts and the Comparison asset list are still sample data (M4.x).

**Files:** `app/Http/Controllers/Assets/AssetController.php`, `app/Http/Requests/Assets/*`, `app/Support/{AssetFilters,AssetFiles,UploadItemPresenter}.php`, `app/Models/Asset.php`, `routes/web.php`, `resources/views/pages/assets/{index,show,edit}.blade.php`, `resources/views/components/assets/{asset-card,asset-metrics}.blade.php`, `resources/js/app.js`, `tests/Feature/{AssetLibraryTest,AppScreensTest}.php`, `docs/{PRD,SITEMAP}.md`.

**Next:** M4.1 — Benchmark engine (not started). It needs a decision on the chart library (Q4).

## 2026-10-03 — M3.2 Bulk upload — completed (approved by the user)

**Scope:** `PRD.md` §7.1 complete — upload session, per-model upload with progress, server validation (content, size, hash), private staging, browser inspection and thumbnail, metadata table with default names, Author rules by role, bulk apply with edited-row protection, remove, description, GLTF + dependent grouping, register with per-row validation and partial success, session record page, staging cleanup job.

**Done:**
- Services: `UploadService` (content validation, size limit, SHA-256, staging under generated names, GLTF dependent grouping with URI rewriting, remove, duplicate check, default asset name) and `AssetRegistrationService` (per-row validation, Author by role, move to `assets/web/{uuid}/` + `assets/thumbnails/`, `asset_metrics` from validated browser metrics, partial success, session completion). `UploadRejected` exception for user-facing reasons.
- Validation by content: GLB needs magic `glTF`, version 2, declared length = file size, and a JSON chunk with `asset.version`; GLTF needs JSON with `asset.version` 2.x; textures must be PNG/JPEG/WebP/KTX2 by signature; buffers are stored as `.bin`. URIs with a scheme or `..` are rejected. Effective maximum = min(`ASSET_MAX_UPLOAD_MB`, PHP `upload_max_filesize`, `post_max_size`), shown on the page.
- JSON endpoints (`routes/web.php`, session auth + CSRF): `POST /api/uploads/sessions`, `POST /api/uploads/sessions/{session}/files`, `PATCH /api/uploads/items/{item}`, `DELETE /api/uploads/items/{item}`, `POST /api/uploads/sessions/{session}/register`. Form Requests for each; owner-only through `UploadSessionPolicy`.
- Pages: `GET /assets/upload` (new upload; the session is created on the first drop and the URL changes to the session so a reload resumes it), `GET /assets/upload/{session}` (record and resume; closed sessions hide the dropzone), staged thumbnail route (owner only).
- Front end: `resources/js/api.js` (fetch wrapper with CSRF and readable errors, XHR upload with progress); `resources/js/features/upload.js` (grouping, local inspection with `loadModelFromFiles` + `inspect.js`, upload one model at a time, autosave of row edits, bulk apply with edited-row protection and "Overwrite edited rows", remove, register, per-field errors); `three/loaders.js` gains `loadModelFromFiles()`. Models: `UploadItem`, `AssetMetric`; relations on `UploadSession` and `Asset`.
- `CleanupStagedUploads`: open sessions untouched for `STAGING_RETENTION_DAYS` lose their staged files; items become `removed`, the session `expired`; registered assets are untouched.
- `DevFixtureSeeder` uses the same `assets/web/{uuid}/` layout. Sample upload rows removed from `SampleScreens`.

**Decisions:**
- A .gltf and its dependents upload in one request (one request per model) — agent; needed to group and validate dependents on the server.
- GLTF dependents renamed to generated names and the stored .gltf rewritten (AGENT.md §8: never use client file names for storage) — agent.
- Sessions are owner-only, also for admins (`SITEMAP.md` §3.2) — as specified.
- Browser metrics are stored only when every count is a non-negative integer; otherwise the asset has no metrics row — agent-routine.
- Thumbnail limits: WebP/JPEG, ≤ 512 KB, ≤ 1024 × 1024 — agent-routine.
- Duplicate = same SHA-256 already registered by the same account (warning, not blocking); for GLTF the hash covers the .gltf and its dependents — agent, following `PRD.md` §7.1.

**Checks:**
- `php artisan test`: 105 passed (534 assertions). `BulkUploadTest` (19, storage faked): **five models in one session (3 fixture GLBs + GLTF/.bin + textured GLTF/.bin/.png) all register as assets** with files under `assets/web/` and metrics; **an invalid file fails with "Not a GLB or GLTF file…" and never becomes an asset**; **a user's submitted Author is ignored (asset gets the user's display name); an admin's is kept**; **a staged session is forbidden to another user and to an admin** (page, thumbnail, upload, edit, remove, register); missing .bin named in the error; dependents stored under generated names; path-escaping URI rejected; fake texture rejected; truncated GLB rejected; size limit; partial success; inactive tool rejected; duplicate warning; metadata saved for resume; remove deletes files; cleanup after retention and not before; page shows the maximum.
- End-to-end in the verification environment (`bin/verify-serve.sh`, port 8001, headless Chrome, 1440 wide): one drop of 9 files → 6 rows (two GLTFs grouped with 1 and 2 linked files), thumbnails for all models, `broken.glb` invalid with the reason, duplicate warnings for the fixture GLBs; bulk apply Tool + time to all; Register → "Registered 5 assets."; reload shows the session record. Stored metrics for the textured GLTF: 1 texture, 1 × 1, estimate 5 bytes. Phone width (384): no horizontal page scroll, remove works. No console errors.
- Pint: passed. `npm run build`: success.

**Issues:**
- Local PHP limits are `upload_max_filesize=2M`, `post_max_size=8M`, so the effective local maximum is 2 MB. To test bigger files locally, raise both in `/opt/homebrew/etc/php/8.5/php.ini` (not changed by the agent). Production limits: `DEPLOYMENT.md` §2.
- Registered rows on the session record page show a placeholder instead of the thumbnail; asset thumbnails are served from M3.3 (asset routes).
- `render_lab_verify` now holds 5 more assets from the end-to-end run (expected for the verification database; `bin/verify-db-reset.sh` resets it).

**Files:** `app/Services/{UploadService,AssetRegistrationService,UploadRejected}.php`, `app/Http/Controllers/Api/UploadController.php`, `app/Http/Controllers/Assets/UploadController.php`, `app/Http/Requests/Uploads/*`, `app/Support/UploadItemPresenter.php`, `app/Models/{UploadItem,AssetMetric,UploadSession,Asset}.php`, `app/Jobs/CleanupStagedUploads.php`, `routes/web.php`, `resources/js/{api.js,features/upload.js,three/loaders.js}`, `resources/views/pages/assets/upload.blade.php`, `resources/views/components/assets/{upload-dropzone,upload-table}.blade.php`, `resources/css/components.css`, `database/seeders/DevFixtureSeeder.php`, `tests/Feature/BulkUploadTest.php`, `docs/{PRD,SITEMAP}.md`.

**Next:** M3.3 — Library and asset detail (not started).

## 2026-10-03 — M3.1 Viewer foundation — completed (approved by the user)

**Follow-up (user question):** the user saw "No model to show." on `/assets/1`. That page is still the M1.3 sample screen and `render_lab` has no assets yet (fixtures are registered only in `render_lab_verify`). Placeholder texts now say when the real preview arrives (asset detail: M3.3; public reproduction: M4.5) and point to `/_viewer`.

**Scope:** Shared Three.js viewer in preview mode, load-phase timing hooks, asset metrics and thumbnail rendering, viewer Blade component, local-only page that previews each fixture.

**Done:**
- `resources/js/three/viewer.js`: `createViewer(container, config)` — GLB/GLTF load, auto-frame from the camera preset (bounding sphere, FOV, distance factor, elevation, azimuth), lighting preset (ambient + 1–3 directional lights, optional RoomEnvironment map), background from a design-token CSS variable, OrbitControls (orbit, pan, zoom, touch), XYZ gizmo (`ViewHelper`, top-right, click to snap), reset view, gizmo toggle, render on demand, full disposal (`dispose()` stops the loop, disposes controls, gizmo, scene resources, renderer, and forces context loss). `mountViewerElement()` wires a server-rendered viewer: control bar (reset, fullscreen, gizmo), loading/error/ready states, `viewer:state` events. Exported helpers: `frameCamera`, `addLights`, `tokenColor`, `direction`.
- `resources/js/three/loaders.js`: `loadModel(url, { cache })` returns the glTF plus `fetch_ms`, `parse_ms`, `transfer_size`; `uploadToGpu()` times `compileAsync` (for M4.1); `disposeObject()`, `eachMaterial()`, `eachTexture()`.
- `resources/js/three/inspect.js`: `inspectModel()` (vertices, triangles, meshes, materials, textures, texture dimensions, texture memory estimate = Σ w × h × 4 × 1.33 with mipmaps); `renderThumbnail()` (own renderer, default camera preset, 512 × 512, WebP 0.85 with JPEG fallback, renderer disposed afterwards).
- `components/performance/viewer.blade.php`: container with `data-src`, `data-mode`, JSON config block (`<script type="application/json">`), control bar, hint text (pointer and touch variants), state overlays; placeholder when there is no source. `App\Support\ViewerConfig::preview()` supplies the default camera, lighting, and background presets.
- `app.js` imports `three/viewer.js` dynamically only when the page has `[data-viewer][data-src]`.
- Local-only `/_viewer` (`resources/views/dev/viewer.blade.php`, `resources/js/features/viewer-lab.js`): every file in `fixtures/models/` in the viewer, with metrics, load timings, thumbnail, and a mount/unmount memory test. Local-only `/_fixtures/{path}` streams fixture files; the resolved path must stay inside `fixtures/models/` (traversal returns 404).

**Decisions:**
- Thumbnail: 512 × 512, WebP quality 0.85, JPEG fallback, default camera and lighting presets, `neutral-100` background — agent-routine (`PROJECT.md` asks for fixed camera, fixed size, JPEG/WebP).
- Preview renderer: antialias on, pixel ratio up to 2, render on demand. These are preview settings only; benchmark mode (M4.1) uses `BENCHMARK.md` §2 — agent-routine.
- Gizmo uses Three.js `ViewHelper` with its built-in axis colors (red/green/blue convention, inside the canvas) — agent-routine.
- Memory baseline is taken after the first load + clear, because three r186 uploads one shared DFG lookup texture (`getDFGLUT`) the first time a physically based material renders; it belongs to the renderer, not to the model — agent, from source inspection.

**Checks:**
- Fixtures (`blender/apel.glb`, `prisma3d/ayam.glb`, `spline/buku.glb`) all reach the `ready` state in headless Chrome; no console errors or warnings (after replacing the deprecated `THREE.Clock` with `THREE.Timer`).
- **Metrics vs manual check** (independent Python parse of each GLB's JSON: scene nodes → mesh primitives → accessor counts) — all three match exactly:
  | Fixture | Vertices | Triangles | Meshes | Materials | Textures |
  |---|---|---|---|---|---|
  | `blender/apel.glb` | 216 | 112 | 2 | 2 | 0 |
  | `prisma3d/ayam.glb` | 224 | 108 | 2 | 2 | 0 |
  | `spline/buku.glb` | 104 | 60 | 2 | 2 | 0 |
- Mouse: drag orbits, wheel zooms, Reset returns to the exact starting camera position, gizmo toggle sets `aria-pressed`. Touch (CDP touch events, 390 × 844): one-finger drag orbits, pinch zooms in, touch hint text shown.
- **Mount/unmount:** 20 mount → load → dispose cycles without WebGL context warnings. One viewer, 20 load + clear cycles: geometries return to the empty baseline (2); textures and programs show no growth from the warm baseline (5 textures, 2 programs) to the end.
- **Three.js only on viewer pages:** build manifest — the entry `app.js` has no static imports and no Three.js code (0 matches); network check — `viewer-*.js` loads only on `/_viewer`, not on `/`, `/reports`, report detail, dashboard, asset detail, comparison, or admin pages.
- `php artisan test`: 86 passed (407 assertions), including the viewer component (config as JSON data, controls with labels, placeholder without a source) and `/_viewer` + `/_fixtures` returning 404 outside local. Pint: passed. `npm run build`: success. No `px` design values or raw colors in `resources/`.

**Issues:**
- The fixture set has no textured model and no GLTF (+ .bin) model, so texture metrics, texture memory, and GLTF dependent loading are untested with real files. All three GLBs were exported with the Blender glTF exporter (generator `Khronos glTF Blender I/O`), even the files in `prisma3d/` and `spline/`. Add a textured GLB and a GLTF + .bin fixture before M3.2 testing.
- The viewer chunk is 649 kB (164 kB gzip); it loads only on viewer pages.
- Asset detail (`/assets/{asset}`) still shows the placeholder; it uses the real viewer when assets become real (M3.3).

**Files:** `resources/js/three/{viewer,loaders,inspect}.js`, `resources/js/features/viewer-lab.js`, `resources/js/app.js`, `resources/views/components/performance/viewer.blade.php`, `resources/views/dev/viewer.blade.php`, `app/Support/ViewerConfig.php`, `routes/web.php`, `resources/css/components.css`, `tests/Feature/ViewerTest.php`.

**Next:** M3.2 — Bulk upload (not started).

## 2026-10-03 — M2.2 Administration — completed (approved by the user)

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
