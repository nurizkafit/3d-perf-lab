# SITEMAP.md — Information architecture and routes

This file is the authority for routes, shells, navigation, and the public/private boundary.

---

## 1. Structure

```text
PUBLIC (no login)                     AUTHENTICATED
├── /                Landing          ├── /dashboard
├── /reports         Report gallery   ├── Assets
├── /reports/{slug}  Report detail    │   ├── /assets
├── /about           About            │   ├── /assets/upload
├── /contact         Contact          │   ├── /assets/upload/{session}
└── /login           Login            │   ├── /assets/{asset}
                                      │   └── /assets/{asset}/edit
                                      ├── Performance Lab
                                      │   ├── /lab
                                      │   ├── /lab/side-by-side
                                      │   ├── /lab/crowd
                                      │   └── /lab/runs/{run}        (benchmark mode)
                                      ├── Results
                                      │   ├── /results
                                      │   ├── /results/tools         (Tool summary)
                                      │   ├── /results/{test}
                                      │   └── /results/{test}/report
                                      ├── /device                    (confirm device class)
                                      └── Administration (admin)
                                          ├── /admin/users
                                          └── /admin/options/{list}
```

---

## 2. Public routes

Public pages never show drafts, private assets, staged files, internal IDs, user emails, or admin controls.

| Route                                | Page              | Content                                                                                                                                                 |
| ------------------------------------ | ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /`                              | Landing           | One viewport: name, description, three points, links to Reports and About                                                                               |
| `GET /reports`                       | Report gallery    | Published reports only. Search (title, question, description) and filters (method, device class) by query parameters. Invalid filter values are ignored |
| `GET /reports/{slug}`                | Report detail     | Question, Target result, interactive reproduction, evidence, configuration, environment, limitations, conclusion. 404 when not published                |
| `GET /reports/{slug}/assets/{token}` | Public model file | Streams one approved model file. 404 when the report is not published, the token is revoked, or the token is wrong (all three look identical)           |
| `GET /about`                         | About             | Section-anchor sidebar + readable content                                                                                                               |
| `GET /contact`                       | Contact           | Owner-supplied contact methods only                                                                                                                     |
| `GET /login`, `POST /login`          | Login             | Email + password. Rate limited                                                                                                                          |

There is no registration route. An administrator creates accounts. The first administrator comes from a seeder with credentials from `.env`.

---

## 3. Authenticated routes

All routes below require login and an active account.

### 3.1 General

| Route            | Page                                                                              |
| ---------------- | --------------------------------------------------------------------------------- |
| `POST /logout`   | Log out                                                                           |
| `GET /dashboard` | Dashboard (`PRD.md` §9)                                                           |
| `GET /device`    | Device class confirmation: detected signals, suggested class, list to choose from |
| `POST /device`   | Store the confirmed class in the session                                          |

### 3.2 Assets

| Route                          | Page                                                         | Access         |
| ------------------------------ | ------------------------------------------------------------ | -------------- |
| `GET /assets`                  | Library: grid/table, search, filters, sort, `?deleted=1`, `?bulk=1` (bulk delete mode) | All users |
| `DELETE /assets/bulk`          | Bulk soft delete of the selected assets (skips assets the user does not own) | All users |
| `DELETE /assets/bulk/force`    | Bulk permanent delete (skips assets used by any test)        | Admin          |
| `GET /assets/upload`           | Bulk upload: dropzone + metadata table                       | All users      |
| `GET /assets/upload/{session}` | Upload session record and resume                             | Session owner  |
| `GET /assets/upload/{session}/items/{item}/thumbnail` | Staged thumbnail                     | Session owner  |
| `GET /assets/{asset}`          | Detail: preview, metadata, asset metrics, tests that used it | All users      |
| `GET /assets/{asset}/download` | Stream the web asset (GLB, or ZIP for GLTF)                  | All users      |
| `GET /assets/{asset}/files/{name}` | Model file or GLTF dependent for the viewer (stored names only) | All users  |
| `GET /assets/{asset}/thumbnail` | Thumbnail image                                             | All users      |
| `GET /assets/{asset}/edit`     | Edit metadata                                                | Owner or admin |
| `PUT /assets/{asset}`          | Save metadata                                                | Owner or admin |
| `DELETE /assets/{asset}`       | Soft delete                                                  | Owner or admin |
| `POST /assets/{asset}/restore` | Restore                                                      | Owner or admin |
| `DELETE /assets/{asset}/force` | Permanent delete (blocked if any test uses the asset)        | Admin          |

### 3.3 Performance Lab

| Route                 | Page                                                                                    |
| --------------------- | --------------------------------------------------------------------------------------- |
| `GET /lab`               | Method selection: two cards (Side-by-Side, Crowd) with one-line descriptions, and a link to the Tool summary |
| `GET /lab/side-by-side`  | Look phase (linked panes) and configuration steps (`DESIGN.md` §9.2)                     |
| `GET /lab/crowd`         | Scenario switch (Per asset / Per tool), selection, look phase, and configuration steps   |
| `GET /lab/runs/{run}`    | Benchmark mode with the viewer visible. Only the run creator, only while the run is `running` |

### 3.4 Results

| Route                                     | Page                                                                             | Access                    |
| ----------------------------------------- | -------------------------------------------------------------------------------- | ------------------------- |
| `GET /results`                            | Test history                                                                     | Own tests; admin sees all |
| `GET /results/tools`                      | Tool summary: device class, tools, common objects (query parameters)             | All users (own results; admin sees all) |
| `GET /results/{test}`                     | Test detail: results on the viewer, Target checks, best markers, metrics, charts, environment, raw data | Owner or admin |
| `GET /results/{test}/export.json`, `.csv` | Export                                                                           | Owner or admin            |
| `GET /results/{test}/report`              | Report editor: question, limitations, conclusion, public asset approval, preview | Owner or admin            |
| `PUT /results/{test}/report`              | Save draft                                                                       | Owner or admin            |
| `POST /results/{test}/report/publish`     | Publish                                                                          | Owner or admin            |
| `POST /results/{test}/report/unpublish`   | Unpublish                                                                        | Owner or admin            |
| `DELETE /results/{test}`                  | Delete test (blocked while the report is published)                              | Owner or admin            |

### 3.5 Administration (admin only)

| Route                                                                              | Page                                                                                                                                        |
| ---------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /admin/users`                                                                 | User list: name, email, role, status, last login                                                                                            |
| `GET /admin/users/create`, `POST /admin/users`                                     | Create user (name, email, role, temporary password)                                                                                         |
| `GET /admin/users/{user}/edit`, `PUT /admin/users/{user}`                          | Edit name, email, role                                                                                                                      |
| `POST /admin/users/{user}/deactivate`, `/activate`                                 | Change status. An admin cannot deactivate their own account or remove the last active admin                                                 |
| `POST /admin/users/{user}/reset-password`                                          | Set a new temporary password                                                                                                                |
| `GET /admin/options/{list}`                                                        | Manage one option list. `{list}` ∈ `tools`, `objects`, `camera`, `lighting`, `background`, `resolution`, `device-classes`, `targets` |
| `GET …/create`, `POST`, `GET …/{item}/edit`, `PUT …/{item}`, `POST …/{item}/deactivate`, `…/activate`, `…/default`, `…/reorder`, `DELETE …/{item}` under `/admin/options/{list}` | Create, edit, deactivate, activate, set default, reorder, delete (unused only) items                                                                                                     |

Authorization is enforced by middleware (`role:admin`) and policies. Hiding a menu item is presentation only.

### 3.6 JSON endpoints

See `PRD.md` §13. All under `/api`, session-authenticated, CSRF-protected.

---

## 4. Shells

| Shell     | File                          | Used by                                                  |
| --------- | ----------------------------- | -------------------------------------------------------- |
| Public    | `layouts/public.blade.php`    | Public routes                                            |
| Auth      | `layouts/auth.blade.php`      | Login                                                    |
| App       | `layouts/app.blade.php`       | Authenticated routes                                     |
| Benchmark | `layouts/benchmark.blade.php` | `/lab/runs/{run}` only: no sidebar, no header, no toasts |

---

## 5. Navigation

Public header: `Home · Reports · About · Contact` and a `Log in` link (or `Dashboard` when logged in).

App sidebar:

```text
Dashboard
ASSETS
  Library
  Upload
PERFORMANCE LAB
  Side-by-Side Test
  Crowd Test
RESULTS
  Test history
  Tool summary
─────────────
ADMINISTRATION      (admin only)
  Users
OPTION LISTS        (admin only)
    Authoring tools
    Objects
    Camera presets
    Lighting presets
    Background presets
    Resolution presets
    Device classes
    Targets
```

- Groups are headings, not collapsible menus: every item is always visible, each with its own icon (`DESIGN.md` §6).
- A route that does not exist yet in the current milestone shows as a disabled item with the label "Coming in M#". It never links to a 404.
- The public site never shows app navigation.

---

## 6. View folders

```text
resources/views/
├── layouts/        public, auth, app, benchmark
├── pages/
│   ├── public/     landing, reports, report-show, about, contact
│   ├── auth/       login
│   ├── dashboard/  index
│   ├── device/     show
│   ├── assets/     index, upload, upload-session, show, edit
│   ├── lab/        index, side-by-side, crowd, run
│   ├── results/    index, show, tools, report
│   └── admin/      users/{index,create,edit}, options/{index,form}
└── components/
    ├── ui/         button, badge, card, input, textarea, select, field-messages, dialog,
    │               dropdown, table, tabs, toast, empty-state, icon, section-label
    ├── navigation/ sidebar, header, public-header, public-footer
    ├── assets/     asset-card, asset-metrics, upload-dropzone, upload-table, asset-selector
    ├── performance/viewer, metric-card, target-checks, step-card, device-chip
    ├── reports/    report-card, report-section
    └── admin/      option-form-fields
```

---

## 7. Public report data boundary

```text
Report + Test + Run + Assets
        ↓  PublicReportPresenter (server)
Sanitized public data
        ↓
pages/public/report-show + components/performance/viewer (data-mode="reproduction")
        ↓
resources/js/features/public-report.js → shared viewer
```

Allowed in public data: title, question, conclusion, limitations, method, scenario, motion scenarios, copy counts, preset values, Target values, aggregated results, smoothness labels, Target outcomes, best markers, asset names, tools, objects, authors, asset metrics, device class, environment summary (browser, OS, GPU string when exposed), Three.js version, token URLs for approved assets.

Never in public data: database IDs, user emails, uploader account, storage paths, unapproved asset URLs, raw samples, other reports, draft content.
