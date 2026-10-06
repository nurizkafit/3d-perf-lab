# AGENT.md — How the coding agent works

This file is the authority for the agent's workflow, decision gates, stack rules, and data safety. `CLAUDE.md` at the project root imports this file.

---

## 1. Mission

Build the 3D Render Test Dashboard as specified in `docs/`, one milestone at a time, with the user's approval at each gate.

---

## 2. Milestone gate (mandatory)

Before a milestone:

1. Read `PROJECT.md` (current milestone) and `LOG.md` (current status, decisions).
2. Read the parts of `PRD.md`, `BENCHMARK.md`, `DESIGN.md`, `SITEMAP.md`, and `DEPLOYMENT.md` that apply.
3. Inspect the existing code.
4. Tell the user:
   - The milestone you will start.
   - What you will build.
   - What you will **not** build in this milestone.
   - The open questions and decisions.
5. Wait for explicit approval.

After a milestone:

1. Run the checks in the milestone's acceptance criteria.
2. Update `LOG.md` (§12 format).
3. Report with the format in `PROJECT.md` §5.
4. **Stop.** Do not start the next milestone without explicit approval.

---

## 3. Ask before you decide

Ask the user before any decision on product behavior, UX, information architecture, data model relationships, methodology (`BENCHMARK.md`), visual system (`DESIGN.md`), new dependencies, or hosting. Examples:

- A new feature, page, metric, budget item, or test method.
- A change to the meaning of a metric or a stored result.
- A new required metadata field.
- A new color, font size, radius, shadow, or layout pattern.
- A new package (PHP or npm) that is not in §5.2.
- A requirement that seems wrong, missing, or in conflict with another file.

How to ask: state the decision, the options, the consequence of each, and a recommendation if useful. Then wait. Record the answer in `LOG.md` and update the file with the authority.

Do not ask about routine details: names of variables and internal helpers, file placement inside the defined structure, refactoring that keeps behavior, test code structure, CSS that uses existing tokens.

If you are not sure which type of decision it is, ask.

---

## 4. Scope discipline

- Build only the approved milestone. Do not build a future feature because the code is near.
- If a future item blocks the current milestone, explain the dependency and ask whether to pull it forward. Record the scope change in `LOG.md` and `PROJECT.md`.
- Do not add analytics, tracking, rankings, or scores.
- Keep the code small. No speculative abstractions.

---

## 5. Stack

### 5.1 Fixed stack

| Layer | Technology |
|---|---|
| Language / runtime | PHP 8.2 or later (match the host; see `DEPLOYMENT.md`) |
| Framework | Laravel — the newest major release that supports the host's PHP version. Confirm the version at M0 |
| Database | MariaDB (MySQL driver, `pdo_mysql`) |
| Views | Blade |
| CSS | Tailwind CSS (v4) + **Basecoat** |
| Interaction | **Alpine.js** for micro-interactions and page state; vanilla JS modules for features |
| 3D | Three.js (`GLTFLoader`, `OrbitControls`, `ViewHelper` or equivalent gizmo) |
| Icons | **Phosphor**, Bold weight, inline SVG through a Blade component |
| Fonts | Funnel Display, Atkinson Hyperlegible Next, Atkinson Hyperlegible Mono (numeric data) — self-hosted through Fontsource npm packages |
| Build | Vite (Laravel plugin). Built on the developer machine or CI, never on the server |
| Auth | Laravel session authentication (hand-written controllers or a minimal starter without a JS framework). No Sanctum tokens, no Breeze/Jetstream with Livewire/Inertia |
| Tests | Pest or PHPUnit (feature tests); browser checks for UI flows |

### 5.2 Approved dependencies

Only these packages are approved without a question. Anything else needs approval.

- Composer: `laravel/framework`, a Phosphor Blade icon package (for example `codeat3/blade-phosphor-icons`, confirm at M0), dev tools that ship with Laravel (Pint, Pest/PHPUnit, Collision).
- npm: `tailwindcss`, `@tailwindcss/vite`, `basecoat-css`, `alpinejs`, `three`, `@fontsource/funnel-display` (or variable variant), `@fontsource-variable/atkinson-hyperlegible-next` (or static variant), `@fontsource-variable/atkinson-hyperlegible-mono` (or static variant), `laravel-vite-plugin`, `vite`.
- Chart library: `uplot` (approved at M4.1). Loaded with dynamic `import()` on result pages only.

### 5.3 Not allowed without approval

React, Vue, Svelte, Next.js, Livewire, Inertia, htmx, jQuery, any CSS framework other than Tailwind + Basecoat, any animation library, any service that needs a long-running process on the server (Node server, queue worker daemon, WebSocket server, Redis).

### 5.4 Shared-hosting constraints

- No long-running processes. Queues use the `sync` or `database` driver processed by the scheduler (`schedule:run` from cron).
- Scheduled jobs (staging cleanup, expired runs) run through one cron entry.
- File storage is the local disk in private storage.
- See `DEPLOYMENT.md` for limits.

---

## 6. Code structure

### 6.1 Backend

```text
app/
├── Http/
│   ├── Controllers/        (Public/, Assets/, Lab/, Results/, Admin/, Api/)
│   ├── Middleware/         (EnsureRole, EnsureActiveUser)
│   └── Requests/           (Form Requests for every mutation)
├── Models/
├── Policies/               (AssetPolicy, TestSessionPolicy, ReportPolicy, UserPolicy, UploadSessionPolicy)
├── Services/               (UploadService, AssetRegistrationService, RunResultService,
│                            BudgetEvaluator, PublicReportPresenter)
└── Support/                (small value objects, for example BudgetItems)
```

Make a Service for a multi-step workflow (several models, storage, or rules). Do not make a Service that only wraps one query.

### 6.2 Frontend

```text
resources/
├── css/
│   ├── app.css             (Tailwind + Basecoat imports)
│   ├── tokens.css          (DESIGN.md tokens as CSS variables; Basecoat variable overrides)
│   └── components.css      (brutalist overrides on Basecoat classes)
└── js/
    ├── app.js              (Alpine start, Basecoat JS, global listeners)
    ├── api.js              (fetch wrapper with CSRF and JSON errors)
    ├── three/
    │   ├── viewer.js       (shared viewer: scene, camera, controls, gizmo, modes)
    │   ├── loaders.js      (GLTF load with timing; dispose helpers)
    │   ├── inspect.js      (asset metrics, thumbnail render)
    │   ├── environment.js  (context collection, device class suggestion)
    │   ├── measure.js      (frame loop, samples, percentiles, GPU timer)
    │   └── methods/        (comparison.js, stress.js, headroom.js)
    └── features/           (upload.js, asset-selector.js, lab-config.js, results.js, public-report.js)
```

Rules:

- Load Three.js only on pages that need it (dynamic `import()`). The main entry bundle must not contain Three.js. Check the build output at each milestone.
- One shared viewer. Preview, thumbnail, benchmark, and public reproduction are **modes** of the same viewer, not copies.
- Blade passes configuration through `data-*` attributes or a JSON `<script type="application/json">` block. No large inline scripts.

---

## 7. Benchmark integrity

`BENCHMARK.md` is the methodology. In code:

- Do not change a metric definition, a default, or the run protocol without approval.
- Benchmark mode disables controls, gizmo, auto-rotate, overlays, and CSS motion.
- No DOM updates during a measurement window.
- Label estimates as estimates and browser-reported values as browser-reported.
- The public reproduction never measures and never writes results.

---

## 8. Security

- Validate every upload by content and size. Never trust client file names or paths. Store with generated names in private storage.
- Every mutation: Form Request + policy check. UI hiding is not authorization.
- CSRF on all state changes, including `/api` JSON endpoints.
- Escape all user content (Blade `{{ }}` only; never `{!! !!}` for user data).
- Hash passwords. Rate-limit login.
- Public asset files only through the token route (`SITEMAP.md` §2).
- Never put private paths, IDs, or emails in public markup.

---

## 9. Local data safety

The user's local working data is production data to the agent.

- Working database: `render_lab`. Working files: `storage/app/private/`.
- **Never** run `migrate:fresh`, `migrate:refresh`, `db:wipe`, `TRUNCATE`, or mass `DELETE` against the working database, and never delete files in `storage/app/private/`, without asking first. Say exactly what would be removed.
- Run automated tests and browser verification against the **isolated verification environment**:
  - Database `render_lab_verify`, storage root `storage/verify/`, port `8001`.
  - Environment file `.env.verify` (not committed; template `.env.verify.example`), loaded with `--env=verify`.
  - Scripts (created in M0): `bin/verify-db-setup.sh`, `bin/verify-db-reset.sh`, `bin/verify-serve.sh`. The shared guard is `bin/_verify-guard.sh`.
  - Each script refuses to run when the configured database is not `render_lab_verify`, and clears the config cache before it runs.
  - `phpunit.xml` / Pest config points to `render_lab_verify`.
- The verification seeder (`DevFixtureSeeder`) imports models from `fixtures/models/` into the verification environment only.

---

## 10. Checks before a milestone is complete

- Feature tests pass for every new route, policy, and validation rule.
- `npm run build` succeeds; check that the entry bundle does not contain Three.js.
- `npm run test:js` passes (unit tests for pure benchmark logic in `tests/js/`, Node built-in test runner).
- Browser check of each new screen at phone (≈ 24rem wide), tablet, and desktop widths.
- Keyboard check of each new interactive component.
- No `px` design values outside the exceptions in `DESIGN.md` §4.
- No new color, size, or shadow outside the tokens.
- `LOG.md` updated.

---

## 11. Communication

- Short, practical updates.
- Ask decisions as described in §3. One decision per question; do not hide a decision inside a large change.
- Report meaningful changes, not routine internal steps.
- Optional: the user may install the `JuliusBrussee/caveman` skill (https://github.com/JuliusBrussee/caveman) to reduce token output. If it is active, it must never shorten code, commands, paths, exact errors, security warnings, or confirmation questions.

---

## 12. LOG.md entry format

```markdown
## YYYY-MM-DD — M#.# <name> — <status: started | completed | blocked>

**Scope:** what was in this milestone.
**Done:** list of features.
**Decisions:** decision — reason — who decided (user/agent-routine).
**Checks:** tests and checks run, with results.
**Issues:** known issues and follow-ups.
**Files:** main files added or changed.
**Next:** next milestone (not started).
```

---

## 13. Documentation updates

When an approved change affects behavior, update the file with the authority (`docs/README.md`) and record it in `LOG.md`. Write each rule in one file only.
