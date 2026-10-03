# PROJECT.md — Milestones and gates

This file is the authority for build order, milestone scope, and acceptance criteria. It also documents the **Later Versions**, which are part of this project but come after the MVP.

---

## 1. Rules

- One milestone (sub-milestone) at a time.
- Ask for approval before you start each sub-milestone (`AGENT.md` §2).
- Build only its scope. Test it. Update `LOG.md`. Report. **Stop.**
- A milestone is complete when its acceptance criteria pass, `LOG.md` is updated, and the user has seen the result.
- Do not reorder milestones without approval. Record any scope change here and in `LOG.md`.
- Mock data in early UI milestones must be clearly marked (`Sample data` badge) and must never be stored as real results.

---

## 2. Overview

| Phase                          | Sub-milestone                     | Outcome                                                                                            |
| ------------------------------ | --------------------------------- | -------------------------------------------------------------------------------------------------- |
| **M0 Project Initialization**  | M0.1 Stack and tooling            | Laravel, MariaDB, Vite, Tailwind, Basecoat, Alpine, Three.js, fonts, icons installed and proven    |
|                                | M0.2 Database and environments    | Full schema migrations, seeders, verification environment and scripts                              |
| **M1 Frontend Implementation** | M1.1 Design system and UI kit     | Tokens, Basecoat theme, brutalist overrides, Blade UI components, kitchen-sink page                |
|                                | M1.2 Public site                  | Landing, gallery, report detail, About, Contact (static/sample data)                               |
|                                | M1.3 Dashboard shell and screens  | App shell, sidebar, header, all app screens as static layouts with sample data                     |
| **M2 User Management**         | M2.1 Authentication and roles     | Login, logout, rate limit, roles, active status, policies, route protection                        |
|                                | M2.2 Administration               | User CRUD, option lists CRUD (all eight lists)                                                     |
| **M3 Asset Management**        | M3.1 Viewer foundation            | Shared Three.js viewer (preview mode), loaders, inspect, thumbnail                                 |
|                                | M3.2 Bulk upload                  | Dropzone, staging, metadata table, bulk apply, registration                                        |
|                                | M3.3 Library and asset detail     | Search/filter/sort, detail with preview, edit, delete/restore                                      |
| **M4 Performance Lab**         | M4.1 Benchmark engine             | Benchmark mode, measurement loop, environment and device class, budgets, run lifecycle, result API |
|                                | M4.2 Comparison Test              | Selector modal, configuration, execution, results view                                             |
|                                | M4.3 Stress Test                  | Repeated and Mixed scenarios, steps, results view                                                  |
|                                | M4.4 Headroom Test                | Search procedure, results view                                                                     |
|                                | M4.5 Results, reports, publishing | History, detail, report editor, publish, public reproduction, export                               |
| **M5 Finalization**            | M5.1 Hardening and QA             | Security, authorization, public boundary, accessibility, responsive, benchmark integrity           |
|                                | M5.2 Deployment                   | Production config, build, Hostinger deployment, smoke test, documentation                          |
| **Later Versions**             | L1–L5                             | Camera Path, Device Matrix, Visual Check, InstancedMesh mode, Markdown/PDF export                  |

---

## 3. MVP milestones

### M0.1 — Stack and tooling

**Scope**

- Check Laravel version support against the target PHP version (`DEPLOYMENT.md` §2). Install Laravel. The project root is not empty (`CLAUDE.md`, `docs/`, `fixtures/`), so install into a temporary folder and move the files into the root without overwriting existing files.
- Initialize git with a `.gitignore` that excludes `.env`, `vendor/`, `node_modules/`, `public/build/`, and `storage/verify/`. The deploy procedure supplies `public/build/` (see `DEPLOYMENT.md` §5).
- Configure MariaDB connection to `render_lab`.
- Install and configure: Tailwind CSS v4 with the Vite plugin, Basecoat, Alpine.js, Three.js, Fontsource packages for both fonts, the Phosphor Blade icon package.
- Check: Atkinson Hyperlegible Mono renders for numeric data (`DESIGN.md` §3). Check exact Phosphor icon names in the icon map.
- A temporary `/_health` page (local only) that shows: DB connection OK, fonts render, one Basecoat button, one Phosphor icon, one Alpine toggle, and a Three.js cube loaded with dynamic import.

**Not in scope:** product screens, auth, schema.

**Acceptance**

- `php artisan serve` and `npm run dev` work. `npm run build` works.
- `/_health` shows all six checks.
- The entry bundle does not contain Three.js (check the build manifest).
- `LOG.md` records the Laravel, PHP, Node, Tailwind, Basecoat, Alpine, and Three.js versions, and the tabular-numerals result.

### M0.2 — Database and environments

**Scope**

- Migrations for the full logical model in `PRD.md` §12.
- Seeders: first admin (credentials from `.env`), authoring tools, option presets, device classes, budget presets (`BENCHMARK.md` §6.2, §7.2).
- Verification environment (`AGENT.md` §9): `render_lab_verify`, `storage/verify/`, `bin/verify-db-setup.sh`, `bin/verify-db-reset.sh`, `bin/verify-serve.sh`, test config.
- `DevFixtureSeeder` that reads `fixtures/models/README.md` table and registers those files in the verification environment only. It must skip missing files with a warning.
- Scheduler entry and the two scheduled jobs as empty shells (staging cleanup, expire runs).

**Not in scope:** models' business logic, UI.

**Acceptance**

- `migrate` succeeds on an empty `render_lab` (ask before you run it if the database is not empty).
- The verification scripts refuse to run against `render_lab`.
- Seeders run twice without duplicates.
- A feature test runs against `render_lab_verify`.

### M1.1 — Design system and UI kit

**Scope**

- `tokens.css` with all `DESIGN.md` tokens; Basecoat variable overrides.
- `components.css` with the brutalist overrides (`DESIGN.md` §1.4, §5.1).
- Motion rules, reduced-motion rules, and the `data-benchmark="running"` motion kill switch.
- Blade UI components (`SITEMAP.md` §6, `components/ui/`).
- A local-only kitchen-sink page `/_ui` that shows every component in every state (default, hover, focus, active, disabled, error, loading).

**Acceptance**

- Every component state is visible on `/_ui` and matches `DESIGN.md`.
- Keyboard focus is visible on all interactive components.
- Reduced motion removes movement.
- No `px` design values outside the allowed exceptions.

### M1.2 — Public site

**Scope**

- Public shell, header, footer.
- Landing, report gallery (search/filter UI with sample cards), report detail layout (with a viewer placeholder area and sample budget table), About (sidebar anchors), Contact.
- Empty, loading, and error states for the gallery and detail.

**Not in scope:** real reports, publishing, the real viewer.

**Ask before you start:** the About text and the contact methods. If the user does not supply them yet, use clearly marked placeholders.

**Acceptance**

- All public routes render without login on phone, tablet, and desktop.
- Sample data shows the `Sample data` badge.
- Lighthouse accessibility score ≥ 95 on Landing and Gallery (record the values).

### M1.3 — Dashboard shell and screens

**Scope**

- App shell: collapsible sidebar with groups, off-canvas on small screens, header with device class chip.
- Benchmark shell layout.
- Static layouts with sample data for: dashboard, asset library (grid and table), bulk upload (dropzone + table + bulk-apply bar, with Alpine behavior on sample rows: select, bulk apply, cell flash), asset detail, asset edit, lab method page, one lab configuration page (steps), benchmark mode, test history, test detail (budget table, chart placeholders), report editor, admin users, admin option list, device confirmation page.
- Temporary route group to view these screens without login (removed in M2.1).

**Not in scope:** auth, persistence, real data.

**Acceptance**

- The user reviews all screens and approves the layouts. Record the feedback in `LOG.md`.
- Bulk-apply interaction works on sample rows (apply to selected / all; edited-row protection).

### M2.1 — Authentication and roles

**Scope**

- Login, logout, login rate limit, "account deactivated" message.
- `role` and `is_active` enforcement middleware.
- Policies for assets, tests, reports, users, upload sessions (rules from `PRD.md` §4).
- Remove the temporary no-login route group from M1.3. Protect all app routes.

**Acceptance**

- Feature tests: guests are redirected; deactivated users cannot log in; a user cannot open admin routes (403); policies match `PRD.md` §4 for each action.

### M2.2 — Administration

**Scope**

- User list, create, edit, role change, deactivate/activate, reset password. Guard rules (no self-deactivation, at least one active admin).
- Option list management for all eight lists (`PRD.md` §11): create, edit, deactivate, reorder, set default. Structured forms for preset values (no raw JSON input for the user).
- Budget preset form with the items in `BENCHMARK.md` §6.1 (enable flag + limit per item).

**Acceptance**

- An admin can create a user who can then log in.
- An item that a record uses cannot be deleted, only deactivated (test with a seeded reference).
- Sidebar Option lists group links to each list.

### M3.1 — Viewer foundation

**Scope**

- `three/viewer.js` with preview mode: GLB/GLTF load, auto-frame, lighting and background presets, OrbitControls (orbit, pan, zoom), XYZ gizmo, reset view, fullscreen, touch support, loading/error/empty states, full disposal.
- `three/loaders.js` with load-phase timing hooks (used later by the benchmark).
- `three/inspect.js`: asset metrics and thumbnail rendering (fixed camera, fixed size, JPEG/WebP).
- Viewer Blade component with `data-*` config.
- A local-only page that previews each file in `fixtures/models/`.

**Acceptance**

- All fixture models load and can be inspected with mouse and touch.
- Metrics from `inspect.js` match a manual check for at least two fixtures (record in `LOG.md`).
- Mounting and unmounting the viewer 20 times returns `renderer.info.memory` to baseline.
- Three.js loads only on viewer pages.

### M3.2 — Bulk upload

**Scope** — `PRD.md` §7.1 complete:

- Upload session, per-file upload with progress, server validation (content, size, hash), staging in private storage.
- Browser inspection and thumbnail per row; placeholder on failure.
- Metadata table, default asset name from file name, Author rules by role, bulk-apply bar, edited-row protection, row remove, expanded description.
- GLTF + dependent files grouping.
- Register action with per-row validation; partial success; session record page.
- Staging cleanup job (retention from config).

**Acceptance**

- Upload 5 fixtures in one drop; complete metadata with bulk apply; register; all 5 become assets.
- An invalid file fails with a clear reason and never becomes an asset.
- A user cannot set Author (server ignores/rejects a submitted value); an admin can.
- A staged file is not visible anywhere outside its upload session.

### M3.3 — Library and asset detail

**Scope** — `PRD.md` §7.2–7.4: library grid/table, search, filters, sort, query-parameter state, deleted filter; asset detail with preview and metric sections; download; edit metadata; soft delete, restore, permanent delete rules.

**Acceptance**

- Filters work without JavaScript and keep state in the URL.
- Ownership rules pass feature tests.
- Permanent delete is blocked for an asset used in a published report (test with seeded data).

### M4.1 — Benchmark engine

**Scope**

- Benchmark mode of the viewer (`BENCHMARK.md` §2) and benchmark shell.
- `measure.js`: frame loop, two clocks, GPU timer, percentiles, jank, sample capture.
- `environment.js`: context collection, refresh-rate estimate, device class suggestion; `/device` confirmation flow; header chip.
- Run lifecycle: create session, start run, invalidation rules, cancel, submit results, expiry job.
- Server: result validation, aggregation, `BudgetEvaluator`, storage.
- Budget step UI (preset select + adjust) shared by all methods.
- **Ask:** chart library choice (`DESIGN.md` §10).
- A local-only "engine self-test" page that runs one fixture for 5 s and shows raw summaries.

**Acceptance**

- Self-test produces all metrics in `BENCHMARK.md` §3.3 (GPU fields `null` where unsupported) on desktop Chrome and on one phone.
- Hiding the tab during a window makes the run invalid.
- No DOM changes during a window (verify with a mutation observer in the self-test).
- Budget evaluation unit tests cover Pass, Fail, Not measured, disabled items.

### M4.2 — Comparison Test

**Scope** — Asset Selector modal (`PRD.md` §8.2); comparison configuration; execution per `BENCHMARK.md` §4.2 (cold/warm load, warm-up, window, repetitions, shuffle, cool-down); results view.

**Acceptance**

- Configure without typing IDs; selection persists across search and filter.
- Three Bird fixtures run with 3 repetitions; results show median and spread, load phases, budget table.
- Default order of results is the selection order.

### M4.3 — Stress Test

**Scope** — `BENCHMARK.md` §4.3: repeated and mixed scenarios, same-tool rule (UI + server), step presets, fixed camera for largest step, results with chart and per-step budget.

**Acceptance**

- Mixed selection with two tools: Run is disabled with a message, and the API rejects a forced request.
- The scene uses one renderer and one scene for all steps (verify in the engine).
- The chart shows p95 interval and work time against instance count with the budget line.

### M4.4 — Headroom Test

**Scope** — `BENCHMARK.md` §4.4: doubling + binary search, pass criterion, cap, time limit, repeated and mixed scenarios, results with probe chart.

**Acceptance**

- On desktop, a light fixture reaches a higher headroom value than a heavy fixture (sanity check, not a rule).
- The cap and time limit stop the search and record the flags.
- The probe chart marks pass/fail with shape and label, not color only.

### M4.5 — Results, reports, publishing

**Scope** — `PRD.md` §10: history with filters; test detail; JSON/CSV export; report editor (question, limitations, conclusion, public asset approval); publish/unpublish with slug and tokens; public gallery and detail with real data; public reproduction (`DESIGN.md` §11); `PublicReportPresenter`.

**Acceptance**

- Reports are private by default; draft URLs return 404 publicly.
- Publish is blocked without a question or without one approved asset.
- Unpublish revokes tokens at once (old token URL returns 404).
- Public HTML and JSON contain none of the forbidden fields (`SITEMAP.md` §7) — automated test.
- The reproduction never sends measurement data.

### M5.1 — Hardening and QA

**Scope** — Security review (uploads, authorization, CSRF, XSS, public boundary); error pages (403/404/419/500) in the design system; accessibility pass; responsive pass on real phone and tablet; benchmark-integrity review against `BENCHMARK.md`; index review; performance check of app pages (bundle sizes, Three.js only where needed).

**Acceptance** — No open critical issue. All feature tests pass. Results of each review are in `LOG.md`.

### M5.2 — Deployment

**Scope** — `DEPLOYMENT.md` procedure: production `.env` template, build, upload or git deployment, document root, storage permissions, cron, PHP limits, HTTPS; smoke test on the live site; `README.md` at the root for developers.

**Acceptance** — The live site passes the smoke test in `DEPLOYMENT.md` §8. The user confirms.

---

## 4. Later Versions (same project, after the MVP)

These items are planned. Do not start them before M5.2 is complete and the user approves. The MVP must not block them (see `BENCHMARK.md` §8).

| ID  | Item                    | Summary                                                                                      | Depends on |
| --- | ----------------------- | -------------------------------------------------------------------------------------------- | ---------- |
| L1  | Camera Path Test        | Scripted orbit and dolly; metrics as a time series along the path (`BENCHMARK.md` §8.1)      | M4.1       |
| L2  | Device Matrix           | One session, runs from several devices; report grouped by device class (`BENCHMARK.md` §8.2) | M4.5       |
| L3  | Visual Check            | Fixed-angle screenshots side by side in reports (`BENCHMARK.md` §8.3)                        | M4.2, M4.5 |
| L4  | InstancedMesh mode      | Second instance mode for Stress and Headroom (`BENCHMARK.md` §8.4)                           | M4.3, M4.4 |
| L5  | Markdown and PDF export | Report export formats                                                                        | M4.5       |

Each Later Version item gets its own gate, scope, and acceptance criteria written in this file before it starts.

---

## 5. Milestone report format

At the end of each sub-milestone, report:

- Milestone completed.
- Features built.
- Checks run and results.
- Known issues.
- Decisions made or needed.
- Main files changed.
- Next milestone (not started).

Then stop.
