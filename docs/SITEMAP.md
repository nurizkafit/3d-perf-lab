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
                                      │   ├── /lab/comparison
                                      │   ├── /lab/stress
                                      │   ├── /lab/headroom
                                      │   └── /lab/runs/{run}        (benchmark mode)
                                      ├── Results
                                      │   ├── /results
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
| `GET /reports/{slug}`                | Report detail     | Question, budget result, interactive reproduction, evidence, configuration, environment, limitations, conclusion. 404 when not published                |
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
| `GET /assets`                  | Library: grid/table, search, filters, sort, `?deleted=1`     | All users      |
| `GET /assets/upload`           | Bulk upload: dropzone + metadata table                       | All users      |
| `GET /assets/upload/{session}` | Upload session record and resume                             | Session owner  |
| `GET /assets/{asset}`          | Detail: preview, metadata, asset metrics, tests that used it | All users      |
| `GET /assets/{asset}/download` | Stream the web asset                                         | All users      |
| `GET /assets/{asset}/edit`     | Edit metadata                                                | Owner or admin |
| `PUT /assets/{asset}`          | Save metadata                                                | Owner or admin |
| `DELETE /assets/{asset}`       | Soft delete                                                  | Owner or admin |
| `POST /assets/{asset}/restore` | Restore                                                      | Owner or admin |
| `DELETE /assets/{asset}/force` | Permanent delete (blocked if used in a published report)     | Admin          |

### 3.3 Performance Lab

| Route                 | Page                                                                                    |
| --------------------- | --------------------------------------------------------------------------------------- |
| `GET /lab`            | Method selection: three cards (Comparison, Stress, Headroom) with one-line descriptions |
| `GET /lab/comparison` | Configuration steps (`DESIGN.md` §9.2)                                                  |
| `GET /lab/stress`     | Configuration steps; scenario switch (Repeated / Mixed)                                 |
| `GET /lab/headroom`   | Configuration steps; scenario switch                                                    |
| `GET /lab/runs/{run}` | Benchmark mode. Only the run creator, only while the run is `running`                   |

### 3.4 Results

| Route                                     | Page                                                                             | Access                    |
| ----------------------------------------- | -------------------------------------------------------------------------------- | ------------------------- |
| `GET /results`                            | Test history                                                                     | Own tests; admin sees all |
| `GET /results/{test}`                     | Test detail: budget result, metrics, charts, environment, raw data               | Owner or admin            |
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
| `GET /admin/options/{list}`                                                        | Manage one option list. `{list}` ∈ `tools`, `camera`, `lighting`, `background`, `resolution`, `instance-steps`, `device-classes`, `budgets` |
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
  Comparison Test
  Stress Test
  Headroom Test
RESULTS
  Test history
─────────────
ADMINISTRATION      (admin only)
  Users
OPTION LISTS        (admin only)
    Authoring tools
    Camera presets
    Lighting presets
    Background presets
    Resolution presets
    Instance steps
    Device classes
    Budget presets
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
│   ├── lab/        index, comparison, stress, headroom, run
│   ├── results/    index, show, report
│   └── admin/      users/{index,create,edit}, options/{index,form}
└── components/
    ├── ui/         button, badge, card, input, textarea, select, field-messages, dialog,
    │               dropdown, table, tabs, toast, empty-state, icon, section-label
    ├── navigation/ sidebar, header, public-header, public-footer
    ├── assets/     asset-card, asset-metrics, upload-dropzone, upload-table, asset-selector
    ├── performance/viewer, metric-card, budget-table, step-card, device-chip
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

Allowed in public data: title, question, conclusion, limitations, method, scenario, instance counts, preset values, budget values, aggregated results, budget outcomes, asset names, tools, authors, asset metrics, device class, environment summary (browser, OS, GPU string when exposed), Three.js version, token URLs for approved assets.

Never in public data: database IDs, user emails, uploader account, storage paths, unapproved asset URLs, raw samples, other reports, draft content.
