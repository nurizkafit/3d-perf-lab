# PRD.md — 3D Render Test Dashboard

## 1. Product summary

**Product:** 3D Render Test Dashboard.

**Purpose:** A lightweight web application to manage 3D assets, run controlled browser-based rendering benchmarks, and keep the results as reports.

**Primary use case:** Compare the same object made with different authoring tools (for example, Blender, Prisma3D, Spline), and help a user decide which assets or tools meet the performance Target of a future project.

The key relationship is:

> Authoring Tool → Asset Characteristics → Web Loading/Rendering Performance → Scalability

The platform is not a 3D modeling tool. Users make assets in external tools and upload them as web assets.

Measurement methodology is defined in `BENCHMARK.md`. This file refers to it and does not repeat it.

---

## 2. Goals

### Primary goals

- Upload and manage 3D assets through a fast bulk upload flow.
- Keep editable asset metadata and automatically derived asset metrics.
- Preview assets interactively with Three.js.
- Run standardized benchmark tests in the browser on desktop and mobile devices.
- Show the assets together and interactively while they are tested.
- Evaluate results against simple project **Targets** (pass/fail per check) and mark the best asset per metric.
- Compare authoring tools with the same set of objects (Tool summary).
- Keep test configurations, environments, and raw measurements.
- Publish reports that answer a clear question, with an interactive public reproduction.
- Export raw data.
- Deploy on Hostinger shared hosting (see `DEPLOYMENT.md`).

### Decision goal

A user can answer this question with evidence:

> "Which of these assets (and which authoring tool) loads fast and stays smooth for my project's Target, on which device class?"

### Research goal

Investigate how authoring workflows and the resulting asset characteristics relate to web rendering performance, without treating the authoring tool as the only cause.

---

## 3. Non-goals

The MVP does not include:

- Online 3D modeling or editing.
- Marketplace or social features.
- Collaborative editing.
- Cloud GPU rendering.
- Statistical inference or automatic research conclusions.
- A composite score, a weighted score, or an overall ranking of assets or tools. The results mark the best asset per metric only (`BENCHMARK.md` §4.5).

The system shows measurements, Target pass/fail results, and best-per-metric markers. The report author writes the conclusion. The system does not generate it.

---

## 4. Users and roles

The MVP has exactly two roles: `admin` and `user`. There is no self-registration.

### Administrator

- Log in and log out.
- Create, edit, deactivate, and reactivate user accounts. Reset passwords. Change roles.
- Manage the option lists (see §11).
- Upload assets and set the **Author** to any value.
- Edit metadata of all assets, including Author.
- Delete and restore all assets. Permanently delete assets.
- Create and run tests. View all tests and reports.
- Publish and unpublish any report. Export any result.

### User

- Log in and log out.
- Upload assets. The system sets **Author** to the user's display name. The user cannot change it.
- Edit metadata of own assets, except Author.
- Delete and restore own assets.
- Create and run tests with any registered asset.
- View own tests and reports, and all published reports.
- Publish and unpublish own reports. Export own results.

A deactivated account cannot log in. Its assets, tests, and reports stay in the system.

---

## 5. Product areas

```text
3D Render Test Dashboard
├── Public experience
│   ├── Landing
│   ├── Report gallery
│   ├── Report detail (with interactive reproduction)
│   ├── About
│   └── Contact
└── Authenticated application
    ├── Login / Logout
    ├── Dashboard
    ├── Asset Management
    │   ├── Asset library
    │   ├── Bulk upload
    │   ├── Asset detail
    │   └── Edit asset
    ├── Performance Lab
    │   ├── Side-by-Side Test
    │   └── Crowd Test
    ├── Results
    │   ├── Test history
    │   ├── Test detail
    │   ├── Tool summary
    │   └── Report editor / publish
    └── Administration (admin only)
        ├── Users
        └── Option lists
```

Routes are defined in `SITEMAP.md`.

---

## 6. Core concepts

### 6.1 Asset

A registered 3D web asset (GLB or GLTF) with human-entered metadata and derived metrics.

- **Author** — the person who made the model. Mandatory.
- **Uploaded By** — the account that uploaded it. Set by the system. Never changed.
- **Authoring Tool** — selected from the admin-managed tool list.
- **Object** — what the asset depicts (for example "Bird"), selected from the admin-managed object list. The research plan defines the objects; the same object is made in each tool. Used to match the same object across tools (decision D41).

Only a **registered** asset is available in the Performance Lab. A staged file is not an asset.

### 6.2 Test methods (MVP)

| Method            | Model                                                                       | Answers                                                    |
| ----------------- | --------------------------------------------------------------------------- | ---------------------------------------------------------- |
| Side-by-Side Test | 2–4 assets shown together in linked panes; each measured alone              | Which version loads faster and runs smoother?              |
| Crowd Test        | Copies of an asset, or of a tool's set of objects, fill one scene until it is no longer smooth | How many copies can this device show smoothly? |

Both methods measure smoothness in up to three motion scenarios: Static, Spin, and Physics (objects in a shaken box). The **Tool summary** view compares authoring tools from existing results.

A Crowd Test runs **Per asset** (1–8 assets, one crowd each) or **Per tool** (two or more tools; one crowd per tool with the same chosen objects). Per tool uses only objects that exist in every selected tool.

Full definitions, metrics, and run protocol: `BENCHMARK.md` §4.

### 6.3 Target

A simple performance goal for one kind of device, with three checks: **Loads in time**, **Runs smooth** (at a goal frame rate), and **File size**. The admin manages Targets. The user selects one Target per test and can adjust its values for that test. The test stores a copy of the final values. Later changes to the Target do not change old tests.

Each result shows **Pass**, **Fail**, or **Not measured** for each check. See `BENCHMARK.md` §6.

### 6.4 Device class

Each test run records the device class of the device that ran it. The dashboard detects the device and suggests a class. The user confirms it from the admin-managed list before the run. Free-text device labels are not allowed. See `BENCHMARK.md` §7.

### 6.5 Test session and test run

- **Test session** — the configuration: method, scenario, assets, motion scenarios, presets, Target.
- **Test run** — one execution of a session on one device. It stores environment, device class, and results.

In the MVP, a session has one run. The data model supports many runs per session for the later Device Matrix (see `PROJECT.md` Later Versions).

### 6.6 Report

A report is built from one completed test session. It answers one question. Reports are private by default. See §10.

---

## 7. Asset Management

### 7.1 Bulk upload (the only upload flow)

One upload flow handles one file or many files. There is no separate single-file form.

**Step 1 — Add files**

- A drag-and-drop area, with a click-to-browse fallback.
- Accepted formats: `.glb`, `.gltf`. A `.gltf` upload must include its `.bin` and texture files in the same drop; the system groups them by the references inside the `.gltf`. If a referenced file is missing, the row fails with a clear reason.
- Each model uploads on its own request with a progress indicator (a .gltf travels with its referenced .bin and texture files in the same request).
- The browser loads each file locally with Three.js to read its metrics and render a thumbnail. If WebGL is not available or the model does not load, the row shows a neutral placeholder and the server-side metrics only.

**Step 2 — Complete metadata in the table**

The table shows one row per file:

| Column          | Type                   | Rule                                                                                                   |
| --------------- | ---------------------- | ------------------------------------------------------------------------------------------------------ |
| Select          | Checkbox               | For "apply to selected"                                                                                |
| Thumbnail       | Image                  | Rendered in the browser; placeholder on failure                                                        |
| File name       | Read-only              | Original file name                                                                                     |
| Asset name      | Input                  | Default: file name without extension; `-` and `_` become spaces; first letter of each word in capitals |
| Author          | Input                  | Admin: editable. User: read-only, set to the user's display name                                       |
| Tool            | Select                 | From the authoring-tool list. Required                                                                 |
| Object          | Select                 | From the object list. Required                                                                         |
| Production time | Input (hours, decimal) | Required, ≥ 0                                                                                          |
| Format          | Read-only              | GLB or GLTF                                                                                            |
| File size       | Read-only              | Human-readable size                                                                                    |
| Status          | Badge                  | Uploading, Staged, Invalid, Registered, Failed, Removed                                                |
| Actions         | Buttons                | Expand (description), Remove                                                                           |

Description is optional and is in the expanded row.

**Bulk-apply bar** (above the table):

- Author (admin only), Tool, Object, and Production time fields.
- Production time in the bar is the **average production time**; it fills every target row with the same value. The user can then edit single rows.
- Buttons: **Apply to selected** and **Apply to all**.
- Bulk apply never changes a row that the user edited by hand after the last bulk apply, unless the user confirms "Overwrite edited rows".

**Step 3 — Register**

- One **Register assets** action submits all staged rows.
- The server validates each row on its own. Valid rows become assets. Invalid rows stay staged with the error next to the field.
- The session page keeps a record of each file: registered, failed (with reason), or removed.
- Abandoned staged files are deleted by a scheduled job after the retention period (default 7 days).

**Validation**

- File type by content, not by extension only (GLB magic bytes `glTF`; GLTF must parse as JSON with an `asset.version` field).
- Maximum file size per file: set in config (default 50 MB). Must not exceed the hosting PHP limits (see `DEPLOYMENT.md`).
- Duplicate detection: the server stores a SHA-256 hash. An identical file already registered by the same account shows a warning. It does not block.

### 7.2 Asset library

- Grid view and table view, with a toggle.
- Search by asset name, author, and description.
- Filters: authoring tool, object, author, format, uploaded by, file size range, upload date range.
- Sort: name, file size, triangle count, upload date.
- Filters and search use query parameters, so a filtered view has a shareable URL and works without JavaScript.
- Show deleted assets with a "Deleted" filter. Deleted assets can be restored.
- **Bulk delete** (table view, "Bulk delete" toggle): select rows on the page, then **Delete** (soft delete; owners for their own assets, admins for any) or, for admins, **Delete permanently**. Rows used in a test are marked "Used in N tests" and are not counted for permanent delete, which is disabled when none of the selected rows qualifies. Both actions ask for confirmation; the server checks each asset again and reports skipped ones. In the Deleted view only the permanent action is offered (admins).

### 7.3 Asset detail

- Interactive preview (orbit, pan, zoom, reset view, XYZ orientation gizmo, fullscreen).
- Metadata: name, tool, author, uploaded by, production time, description.
- Asset metrics (derived): format, file size, vertices, triangles, meshes, materials, textures, texture dimensions, estimated texture memory.
- Asset metrics and runtime metrics are always in separate sections.
- Download the web asset (authorized users only): a GLB as is; a GLTF as a ZIP with its .bin and texture files.
- List of tests that used this asset.

### 7.4 Edit and delete

- Edit is metadata only. The model file is never replaced, because a changed file makes old results invalid. To change the file, upload a new asset.
- Delete is a soft delete. A deleted asset cannot be selected for new tests. Old results keep their reference.
- Permanent delete is admin only and is blocked when any test uses the asset (this includes every asset in a published report). Test results always keep their assets; such an asset can stay soft-deleted. Permanent delete removes the asset record and its files.

### 7.5 Derived asset metrics

Derived in the browser at upload and stored. The server checks file size, format, and hash on its own.

- File size, format, vertices, triangles, meshes, materials, textures, texture dimensions, estimated texture memory.

---

## 8. Performance Lab

### 8.1 Common flow for all methods

1. **Select assets** with the Asset Selector modal (§8.2), or, for a Crowd Test Per tool, select tools and objects (§8.3).
2. **Look**: the assets appear in the interactive viewer. The user can orbit, zoom, and switch motion scenarios (Static / Spin / Physics).
3. **Configure**: motion scenarios (all selected by default), presets, and method settings. The estimated run time is shown.
4. **Target**: select a Target; adjust values if necessary.
5. **Device check**: confirm the detected device class.
6. **Measure**: the dashboard enters benchmark mode. Non-essential UI is hidden. The viewer stays visible and shows what is being measured. A progress strip shows the step and the time remaining (updated between measurement windows only). The user can cancel.
7. **Results**: shown on the same viewer (labels on each pane or crowd) and in tables below. The viewer stays interactive on the results page.

A run is **invalid** and is not saved as a result when the tab becomes hidden, the window loses focus for longer than 1 s, or WebGL context is lost. The user sees the reason and can run again.

### 8.2 Asset Selector modal

Shared by all methods.

- Search by name. Filter by tool, object, author, format.
- Multi-select. "Select all visible" and "Clear".
- Each result shows the stored thumbnail, name, tool, object, author, file size, triangles.
- A hover/focus action opens a live 3D preview of one asset in the modal.
- The selection stays when the user changes search or filters.
- A selected-assets summary before confirmation.
- Keyboard access: focus trap, Escape closes, arrow keys move in the list, Space selects.
- Method-specific validation runs after confirmation (for example, Side-by-Side needs 2–4 assets).

### 8.3 Tool and object selection (Crowd Per tool, Tool summary)

- Select two or more authoring tools.
- The page lists the objects that exist in **every** selected tool (registered, not deleted assets). Each object is selected by default; the user can untick objects (Crowd: at most 30).
- When a tool has more than one asset for an object, the user picks which asset to use (default: the most recently registered).
- Objects that are missing in any selected tool are listed separately with the tools that lack them. They are never included.

### 8.4 Method-specific configuration

The settings and defaults for each method are in `BENCHMARK.md` §4. The selectable presets come from the option lists (§11).

### 8.5 Results views

- **Side-by-Side:** the panes with Target checks, smoothness label per motion scenario, load time, file size, and best markers; a value table; load-phase bars; Technical details.
- **Crowd:** "Smooth up to N copies" per asset or tool set and per motion scenario, with the Most copies marker; the probe sequence chart; an explore viewer with a copy-count slider; Technical details.
- **Tool summary:** one column per tool with medians, Target check counts, copies, and best markers (`BENCHMARK.md` §4.7).

All views show the tested assets with their metadata (name, tool, object, author, format, file size, vertices, triangles, production time), the device class, the environment summary, and the repetition spread. Checks and metrics show a short explanation next to their label. The default order is the selection order; best markers never re-sort the results.

---

## 9. Dashboard

- Detected device class of the current device, with a link to confirm it.
- Counts: registered assets, tests, published reports.
- Recent tests (5) and recent uploads (5).
- Quick actions: Upload assets, New Side-by-Side Test, New Crowd Test, Tool summary.

---

## 10. Results and reports

### 10.1 Test history

List with: name, method, scenario, date and time, asset count, device class, Target, status, Target summary (for example, "2 of 3 assets meet all Target checks"). Filters: method, device class, date range.

### 10.2 Report purpose

A report answers **one question** with evidence. Example: "Which Bird variant stays smooth in a crowd on a Mid-range Android phone?" or "Do Blender assets load faster than Spline assets for objects A–T?"

### 10.3 Report structure

1. **Question** — written by the author. Required before publish.
2. **Target result** — pass/fail for each asset and each Target check, with the device class, and the best markers.
3. **Evidence** — key metrics and charts from `BENCHMARK.md` for the method.
4. **Asset characteristics** — derived metrics, tool, author.
5. **Method and configuration** — method, scenario, motion scenarios, presets, repetitions, Target values.
6. **Environment** — device class, browser, OS, GPU (when exposed), refresh rate, Three.js version.
7. **Limitations** — the system adds the standard limitations (environment-specific results, metrics the browser did not expose). The author can add more.
8. **Conclusion** — written by the author. Optional. The system never writes it.
9. **Interactive reproduction** — public reports only (§10.5).
10. **Raw data** — JSON and CSV export links, authorized users only.

### 10.4 Publishing

- Reports are private by default.
- Publish requires: a question, at least one asset approved for public delivery, and a completed test run.
- On publish, the author selects which assets the public may download for the reproduction. The default for each asset is **not approved**.
- A published report gets a stable slug, generated from the title and a short random suffix.
- Unpublish removes public access at once and revokes all public asset tokens. The report stays for authorized users. Republish keeps the same slug and creates new tokens.

### 10.5 Public reproduction

- The public page rebuilds the test scene with the shared Three.js viewer: Side-by-Side as linked panes; Crowd at each asset's or tool set's `smooth_copies`; the same motion scenarios, camera preset, and lighting preset. Visitors can orbit and switch motion scenarios.
- The viewer is labelled **Interactive reproduction**. It does not measure the visitor. It shows the recorded results only.
- If an asset is not approved for public delivery, the page shows its metrics and a placeholder in place of the model.

### 10.6 Export

- MVP: JSON (full, including raw samples) and CSV (one row per asset or tool set, per motion scenario, per probe, per repetition).
- Later version: Markdown and PDF.

---

## 11. Option lists (Administration)

The admin manages the values that appear in dropdowns. Each list item has a name, an active flag, a sort order, and (for some lists) values.

| List                  | Used in               | Values                                                                            |
| --------------------- | --------------------- | --------------------------------------------------------------------------------- |
| Authoring tools       | Upload, filters       | Name. Seed: Blender, Prisma3D, Spline                                             |
| Objects               | Upload, filters, Lab  | Name. The research plan's object list (decision D41). No seed values              |
| Camera presets        | Lab configuration     | FOV, position mode (auto-frame / fixed), distance factor, elevation, azimuth. Seed: Auto-frame (FOV 45, distance factor 1.5, elevation 20°, azimuth 45°) |
| Lighting presets      | Lab configuration     | Ambient intensity, directional light(s), environment map on/off. Seed: Studio (ambient 0.6, one directional light 2.0 at 45°/45°, environment map off) |
| Background presets    | Lab configuration     | Solid color (from design tokens). Seed: Neutral (`neutral-100`)                   |
| Resolution presets    | Lab configuration     | Drawing-buffer width × height. Seed: 1280×720 (default), 1920×1080, 720×1280      |
| Device classes        | Device check, filters | Name, category (desktop/laptop/tablet/phone), platform. Seed in `BENCHMARK.md` §7 |
| Targets               | Lab Target step       | Device class links (one or more), load time, goal frame rate, file size. Seed in `BENCHMARK.md` §6.2 |

Rules:

- An item that a record uses cannot be deleted. It can be deactivated. A deactivated item does not show in new selections but stays visible on old records.
- Tests store a snapshot of preset values, so a later edit does not change an old result.
- Presets, resolution, and Targets have exactly one default item. The default cannot be deactivated or deleted; make another item the default first. Only an active item can become the default.
- "In use" means a stored record refers to the item: a tool or an object used by an asset; a device class used by a test run or linked to a Target. Presets are copied into tests, so they are never "in use".
- Admins edit values through structured fields (numbers, selects, checkboxes).

---

## 12. Data model (logical)

```text
users                id, name, email, password, role (admin|user), is_active, last_login_at, timestamps
tools                id, name, is_active, sort_order
objects              id, name, is_active, sort_order
option_presets       id, type (camera|lighting|background|resolution),
                     name, values_json, is_default, is_active, sort_order
device_classes       id, name, category, platform, is_active, sort_order
targets              id, name, max_load_s, target_fps, max_file_size_mb,
                     is_default, is_active, sort_order
device_class_target  target_id, device_class_id   (a Target serves many classes)

assets               id, name, tool_id, object_id, author, uploaded_by, description, production_time_hours,
                     format, web_file_path, thumbnail_path, file_size, sha256,
                     deleted_at, timestamps
asset_metrics        asset_id, vertices, triangles, meshes, materials, textures,
                     texture_dimensions_json, texture_memory_estimate_bytes

upload_sessions      id, created_by, status, timestamps, completed_at
upload_items         id, upload_session_id, asset_id (nullable), original_filename,
                     staged_path, thumbnail_path, file_size, sha256, format,
                     metrics_json, metadata_json, status, error_json, timestamps

test_sessions        id, name, method (side_by_side|crowd), scenario (per_asset|per_tool|null),
                     configuration_json (motions, presets, settings, chosen objects),
                     target_json (snapshot), created_by, timestamps
test_session_assets  test_session_id, asset_id, position, group_tool_id (nullable; Crowd Per tool)
test_runs            id, test_session_id, device_class_id, environment_json, status
                     (running|completed|invalid|cancelled), invalid_reason,
                     started_at, completed_at, created_by
test_results         id, test_run_id, asset_id (nullable for a tool set), group_tool_id (nullable),
                     motion (static|spin|physics|null for load rows), probe_copies (nullable; Crowd),
                     repetition, metrics_json, samples_json, created_at
target_checks        id, test_run_id, asset_id (nullable), group_tool_id (nullable),
                     motion (nullable), check_key (load|smooth|file_size|smooth_copies),
                     limit_value, measured_value, label (smooth|mostly_smooth|choppy|null),
                     outcome (pass|fail|not_measured|null)

reports              id, test_session_id, title, question, conclusion, limitations,
                     status (draft|published), public_slug (unique), published_at,
                     published_by, created_by, timestamps
report_public_assets report_id, asset_id, token (unique), created_at
```

The agent can change physical details (indexes, column types) when the logical relationships stay the same. A change to a relationship is a decision (ask first).

---

## 13. Architecture and stack

The stack and its limits are defined in `AGENT.md` §5 and `DEPLOYMENT.md`. Summary:

- Backend: PHP 8.2+, Laravel, MariaDB, Blade, Eloquent, server-side sessions.
- Frontend: Tailwind CSS + Basecoat (shadcn/ui-style components), Alpine.js for micro-interactions, vanilla JavaScript modules, Three.js, Phosphor icons (Bold).
- Build: Vite, run locally or in CI. The production server does not run Node.js.

The browser does all measurement. Laravel stores configuration, results, and evaluations. Laravel never calculates frame rates.

### HTTP surface

- Pages: Blade routes in `routes/web.php`.
- JSON endpoints: under the `/api` prefix in `routes/web.php`, with session authentication and CSRF. No token authentication in the MVP.

Main JSON endpoints:

```text
POST   /api/uploads/sessions
POST   /api/uploads/sessions/{session}/files
PATCH  /api/uploads/items/{item}
DELETE /api/uploads/items/{item}
POST   /api/uploads/sessions/{session}/register

GET    /api/assets                    (selector search: q, tool, author, format; registered, not deleted; max 60)
GET    /api/objects/common            (objects present in every given tool, with candidate assets)
POST   /api/tests                     (create session)
POST   /api/tests/{test}/runs         (start run, returns run id)
POST   /api/runs/{run}/results        (submit results; server evaluates Target checks)
POST   /api/runs/{run}/invalidate
POST   /api/runs/{run}/cancel
```

---

## 14. Storage and security

- Uploads go to private storage: `storage/app/private/staging/{session}/{uuid}/` while staged, then `storage/app/private/assets/web/{uuid}/` (the model and, for GLTF, its dependents) and `storage/app/private/assets/thumbnails/{uuid}.webp|jpg`.
- GLTF dependents are stored under generated names (`{uuid}-{n}.{ext}`) and the stored .gltf is rewritten to reference them; URIs that leave the folder or use a scheme are rejected.
- Thumbnails: rendered in the browser, 512 × 512, WebP (JPEG fallback), at most 512 KB.
- Generated file names (UUID). Never use client file names or paths for storage.
- Authorized downloads stream through a controller with a policy check.
- Public reproduction files stream through `/reports/{slug}/assets/{token}`. The token is random, per report and per asset, and valid only while the report is published and the asset is approved.
- Passwords hashed with Laravel's default hasher. Login rate limiting.
- CSRF on all state changes. Escape all user content.
- Every mutation has a server-side policy check. UI hiding is presentation only.

---

## 15. Success criteria (MVP)

An authenticated user can:

1. Drop several GLB/GLTF files, complete metadata with bulk apply, and register them in one action.
2. Search and filter the asset library.
3. Edit metadata within the Author and Uploaded By rules.
4. Preview an asset with orbit, pan, zoom, reset, and the XYZ gizmo.
5. Run a Side-by-Side Test: look at 2–4 assets together, measure them in the selected motion scenarios, and see Target checks, smoothness labels, and best markers on the panes.
6. Run a Crowd Test Per asset and Per tool, watch the crowd grow, and see "Smooth up to N copies" per motion scenario.
7. Open the Tool summary and compare authoring tools on the objects that exist in every tool.
8. Run tests on a desktop and on a phone, with the device class recorded for each.
9. Write a report with a question, publish it, and open it without login with an interactive reproduction.
10. Export raw data as JSON and CSV.

An administrator can also:

11. Create users and manage roles and account status.
12. Manage all option lists, including objects, device classes, and Targets.

The application deploys to Hostinger shared hosting with the procedure in `DEPLOYMENT.md`.

---

## 16. Guiding principle

> **The Library shows the asset. The Lab shows the assets in motion and measures them against a Target. The Report answers a question with what was measured.**
