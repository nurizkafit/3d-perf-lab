---
version: "1.0"
name: "3D Render Test Dashboard"
style: "Soft Modern Brutalism"
colors:
  primary-light: "#8183C4"
  primary: "#6366AF"
  primary-dark: "#4B4D8C"
  primary-tint: "#ECEDF7"
  neutral-0: "#FFFFFF"
  neutral-50: "#F8FAFC"
  neutral-100: "#F3F5F8"
  neutral-200: "#E7EAEE"
  neutral-300: "#D5DADF"
  neutral-400: "#A5ADB7"
  neutral-500: "#7D8591"
  neutral-600: "#636A76"
  neutral-700: "#525763"
  neutral-800: "#3E424C"
  neutral-900: "#2F323A"
  neutral-950: "#1F2128"
  success: "#15803D"
  warning: "#B45309"
  destructive: "#B91C1C"
  info: "#2563EB"
typography:
  heading-font: "Funnel Display"
  heading-weight: 700
  body-font: "Atkinson Hyperlegible Next"
  data-font: "Atkinson Hyperlegible Mono"
  display:
    {
      size: "clamp(2.5rem, 5vw, 3.5rem)",
      weight: 700,
      line-height: 1.05,
      tracking: "-0.02em",
    }
  h1:
    {
      size: "clamp(1.75rem, 3vw, 2.5rem)",
      weight: 700,
      line-height: 1.1,
      tracking: "-0.015em",
    }
  h2: { size: "clamp(1.375rem, 2.2vw, 1.75rem)", weight: 700, line-height: 1.2 }
  h3:
    { size: "clamp(1.125rem, 1.6vw, 1.25rem)", weight: 600, line-height: 1.25 }
  body: { size: "0.9375rem", weight: 400, line-height: 1.5 }
  body-sm: { size: "0.8125rem", weight: 400, line-height: 1.45 }
  label: { size: "0.8125rem", weight: 600, line-height: 1.25 }
  data: { size: "0.8125rem", weight: 500, line-height: 1.3 }
  metric:
    {
      size: "clamp(1.375rem, 2.4vw, 1.75rem)",
      weight: 700,
      line-height: 1.1,
      font: "heading",
    }
radius:
  none: "0rem"
  sm: "0.25rem"
  md: "0.375rem"
  lg: "0.5rem"
  xl: "0.75rem"
border:
  hairline: "0.0625rem"
  strong: "0.09375rem"
shadow:
  hard-sm: "0.125rem 0.125rem 0 0 {colors.neutral-800}"
  hard-md: "0.1875rem 0.1875rem 0 0 {colors.neutral-800}"
  hard-lg: "0.3125rem 0.3125rem 0 0 {colors.neutral-800}"
  soft: "0 0.0625rem 0.125rem 0 rgb(31 33 40 / 0.06)"
spacing:
  1: "0.25rem"
  2: "0.5rem"
  3: "0.75rem"
  4: "1rem"
  5: "1.25rem"
  6: "1.5rem"
  8: "2rem"
  10: "2.5rem"
layout:
  page-padding-inline: "clamp(1rem, 3vw, 2rem)"
  page-padding-block: "clamp(1rem, 2.5vw, 2rem)"
  content-max-width: "90rem"
  readable-width: "65ch"
  sidebar-width: "clamp(13rem, 16vw, 16rem)"
  sidebar-collapsed-width: "3.5rem"
  header-height: "3.5rem"
  viewer-min-height: "clamp(18rem, 45vh, 34rem)"
  control-height: "2.25rem"
  control-height-sm: "1.875rem"
  table-row-height: "2.5rem"
  thumb-size: "2.5rem"
  icon-sm: "1rem"
  icon-md: "1.25rem"
motion:
  fast: "120ms"
  base: "180ms"
  slow: "260ms"
  ease-out: "cubic-bezier(0.2, 0.8, 0.2, 1)"
  ease-in-out: "cubic-bezier(0.4, 0, 0.2, 1)"
---

# DESIGN.md — Visual system

This file is the authority for visual behavior. The YAML tokens above are normative. Do not add colors, font sizes, radii, shadows, or durations that are not in the tokens. To add a token, ask first (`AGENT.md` §3).

---

## 1. Style: Soft Modern Brutalism

The interface is a **technical instrument with a brutalist accent**. It is calm and readable first. The brutalist character comes from a small set of strong signals, not from raw or harsh surfaces.

### 1.1 The brutalist signals (use these)

| Signal                               | Rule                                                                                                                                                 |
| ------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| Hard offset shadow                   | No blur. `hard-md` on buttons, clickable cards, and the active nav item. `hard-lg` on modals and popovers. Static containers use no shadow or `soft` |
| Dark outlines on interactive modules | `strong` border in `neutral-800` on buttons, clickable cards, dropzone, modals. Static content uses `hairline` `neutral-200`                         |
| Bold display headings                | Funnel Display 700, tight tracking                                                                                                                   |
| Visible structure                    | Section labels in small caps style (label token, uppercase, `0.06em` tracking), dividers, numbered steps                                             |
| Press feedback                       | On press, the element moves by the shadow offset and the shadow goes to 0. This is the main micro-interaction                                        |
| Flat color blocks                    | Solid fills for status and selection. No gradients                                                                                                   |

### 1.2 The soft side (keep these)

- Moderate radius (`md`/`lg`). Never 0 on controls, never larger than `xl`.
- Light surfaces: page `neutral-100`, cards `neutral-0`/`neutral-50`.
- Comfortable but compact density.
- Small shadow offsets (2–5 px equivalent). Never large offsets.
- Indigo only as an accent.

### 1.3 Avoid

Glassmorphism, gradients, glow, neon, blur shadows on interactive modules, full-black backgrounds in the app, rotated or overlapping "chaotic" layouts, all-caps body text, decorative animation in or near the benchmark canvas.

### 1.4 Quick reference

```text
Button (primary):  bg primary · text neutral-0 · border strong neutral-800 · radius md · shadow hard-md
  hover:           shadow hard-lg, translate(-0.0625rem,-0.0625rem)
  active:          shadow none, translate(0.1875rem,0.1875rem)
Card (static):     bg neutral-0 · border hairline neutral-200 · radius lg · no shadow
Card (clickable):  bg neutral-0 · border strong neutral-800 · radius lg · shadow hard-sm → hard-md on hover
Input:             bg neutral-0 · border hairline neutral-300 · radius md · height control-height
  focus:           border neutral-800 · ring 0.125rem primary at 35% opacity
Modal:             border strong neutral-800 · radius xl · shadow hard-lg
```

---

## 2. Color

### 2.1 Usage

| Token               | Use                                                               |
| ------------------- | ----------------------------------------------------------------- |
| `primary`           | Primary buttons, active nav, links, focus ring, main chart series |
| `primary-dark`      | Pressed state, text on `primary-tint`                             |
| `primary-light`     | Secondary chart series, hover tint on dark surfaces               |
| `primary-tint`      | Selected rows, selected cards, active filter chips                |
| `neutral-0`/`50`    | Card and input surfaces                                           |
| `neutral-100`       | Page background in the app                                        |
| `neutral-200`/`300` | Hairline borders, dividers, input borders                         |
| `neutral-400`       | Disabled icons                                                    |
| `neutral-500`       | Decorative only (for example, inactive chart grid). Not for text: 3.4:1 on `neutral-100` |
| `neutral-600`       | Secondary text, placeholders                                      |
| `neutral-700`       | Dark fills (`Sample data` badge, step-number squares, skip link)  |
| `neutral-800`       | Strong borders, hard shadows, benchmark-mode background, dialog backdrop (50%), dark chart series |
| `neutral-900`       | Reserved; not used for components                                 |
| `neutral-950`       | Primary text only                                                 |
| Semantic            | Status only: Pass (success), Fail (destructive), Warning, Info    |

### 2.2 Rules

- Most of the interface is neutral.
- The neutral scale (50–950) is one OKLCH ramp from `#F8FAFC` to `#1F2128`.
- Dark non-text elements stay in the `neutral-700`–`neutral-800` range: shadows and strong borders `neutral-800`, dark fills `neutral-700`. `neutral-900` and `neutral-950` are not used for components; `neutral-950` is the primary text color. Icons use `currentColor`.
- Pass/Fail badges always include an icon and a word ("Pass", "Fail"). Never color only.
- Never color asset columns green/red to imply better or worse. Target check badges (Pass, Fail, Not measured) are the only colored result cells. Smoothness labels and best markers are neutral (§9.4).
- Light theme only in the MVP. Define colors as CSS variables so a dark theme can be added later.

---

## 3. Typography

| Role                          | Font                                                                 | Notes                                                                                                                                 |
| ----------------------------- | -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| Display, H1–H3, metric values | **Funnel Display**                                                   | Weight 700 (H3: 600)                                                                                                                  |
| Body, UI, labels, tables      | **Atkinson Hyperlegible Next**                                       | Variable weight. Body 400, labels 600                                                                                                 |
| Numeric data                  | **Atkinson Hyperlegible Mono**                                       | Numbers in tables and data lists. Monospaced, so digits align. Metric card values stay in Funnel Display |

Loading:

- Self-host the fonts through npm packages (Fontsource) and Vite. Do not load Google Fonts at runtime.
- Load only the weights in use. Use `font-display: swap`.
- Fallback stack: `system-ui, -apple-system, "Segoe UI", sans-serif`.

Rules:

- Body text base is `0.9375rem` (compact). Never below `0.75rem`.
- Numbers in tables are right-aligned with units in the header, not in each cell, where possible (`Load (ms)`).
- Metric cards: label (label token) above value (metric token) above context (body-sm, neutral-600).

---

## 4. Units and responsive rules

- Use `rem` for type, spacing, radius, control sizes, and shadow offsets.
- Use `%`, `vw`, `vh`, `ch`, `min()`, `max()`, `clamp()` for fluid layout.
- Do not use `px` for design dimensions. Exceptions: Tailwind's default `border` hairline (1 px) is allowed; canvas drawing-buffer sizes and WebGL values are pixels by definition. Document any other exception in code.
- Breakpoints (Tailwind defaults are allowed): `sm 40rem`, `md 48rem`, `lg 64rem`, `xl 80rem`. Prefer fluid layout first.

---

## 5. Component library: Basecoat

Use **Basecoat** (shadcn/ui-style components as plain HTML classes on Tailwind CSS). Do not copy shadcn React components.

- Theme Basecoat by overriding its CSS variables in `resources/css/tokens.css` with the tokens in this file. Do not edit the Basecoat package source.
- Add the brutalist signals (§1.1) in `resources/css/components.css` as small, named overrides on Basecoat classes (for example, `.btn`, `.card`, `.dialog`, `.input`). Keep the overrides in one place.
- Use Basecoat's JavaScript only for the components that need it (dropdown menu, popover, select, tabs, toast, sidebar). Use Alpine.js for page behavior.
- Wrap repeated Basecoat markup in Blade components under `components/ui/` (`button`, `badge`, `card`, `input`, `select`, `dialog`, `dropdown`, `table`, `tabs`, `toast`, `empty-state`, `icon`).

### 5.1 Component rules

| Component                 | Rules                                                                                                                                                                                                                                              |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Button                    | Variants: primary, secondary (neutral-0 bg), outline, ghost, destructive. Sizes: sm (`control-height-sm`), default (`control-height`), icon. Always a text label, or an `aria-label` for icon-only buttons. Loading state: spinner icon + disabled |
| Input / Select / Textarea | Same height, border, radius, and focus. Label above. Error text below in `destructive` with an icon. Select reserves space for the indicator                                                                                                       |
| Card                      | Static or clickable (§1.4). Padding `4` (compact) or `6`                                                                                                                                                                                           |
| Table                     | Row height `table-row-height`. Sticky header. Hairline row dividers. Horizontal scroll on narrow screens; never truncate numbers. Selected row: `primary-tint`                                                                                     |
| Badge                     | Flat fill, `sm` radius, label token. Status badges include an icon                                                                                                                                                                                 |
| Dialog                    | Focus trap, Escape closes, return focus to the trigger. `hard-lg` shadow                                                                                                                                                                           |
| Toast                     | Bottom-right (bottom-center on phones). Auto-close 4 s; errors stay until closed                                                                                                                                                                   |
| Empty state               | Icon, one sentence, one primary action                                                                                                                                                                                                             |
| Dropzone                  | Dashed `strong` border `neutral-400`. On drag-over: solid `primary` border, `primary-tint` fill, `hard-md` shadow                                                                                                                                  |
| Metric card               | Static card; never shows a rank or an overall score. It can show a best marker badge (§9.4)                                                                                                                                                                                                   |

---

## 6. Icons: Phosphor (Bold weight)

- Use the **Phosphor** icon set, **Bold** weight only. Do not mix sets or weights.
- Render as inline SVG through a Blade component (`<x-ui.icon name="upload-simple" />`) that reads from the Phosphor package. Only the used icons are sent.
- Sizes: `icon-sm` (1rem) in buttons, tables, and badges; `icon-md` (1.25rem) in navigation and empty states.
- Color: `currentColor`. Decorative icons get `aria-hidden="true"`.

Standard icon map (use these names consistently):

| Concept         | Icon                 | Concept           | Icon                        |
| --------------- | -------------------- | ----------------- | --------------------------- |
| Dashboard       | `squares-four`       | Upload            | `upload-simple`             |
| Assets          | `cube`               | Search            | `magnifying-glass`          |
| Performance Lab | `flask`              | Filter            | `funnel-simple`             |
| Side-by-Side Test | `columns`          | Delete            | `trash`                     |
| Crowd Test      | `users-three`        | Restore           | `arrow-counter-clockwise`   |
| Tool summary    | `scales`             | Edit              | `pencil-simple`             |
| Results         | `chart-line`         | Download / export | `download-simple`           |
| Reports         | `article`            | Publish           | `globe-simple`              |
| Users           | `users`              | Unpublish         | `eye-slash`                 |
| Option lists    | `sliders-horizontal` | Device class      | `device-mobile` / `desktop` |
| Pass            | `check-circle`       | Fail              | `x-circle`                  |
| Not measured    | `minus-circle`       | Warning           | `warning`                   |
| Reset view      | `arrows-clockwise`   | Fullscreen        | `corners-out`               |
| Library         | `cube`               | Test history      | `chart-line`                |
| Authoring tools | `wrench`             | Camera presets    | `camera`                    |
| Lighting presets| `lightbulb`          | Background presets| `paint-bucket`              |
| Resolution presets | `frame-corners`   | Objects           | `shapes`                    |
| Device classes  | `devices`            | Targets           | `target`                    |
| Best marker     | `trophy`             | Too close to call | `equals`                    |
| Shake (Physics) | `arrows-out-cardinal`| Estimated time    | `timer`                     |

Check exact icon names against the installed Phosphor version at M0.

---

## 7. Motion and micro-interactions

Use CSS transitions first. Use Alpine.js (with `x-transition`) for show/hide. Do not add an animation library.

| Interaction                   | Behavior                                                  | Duration   |
| ----------------------------- | --------------------------------------------------------- | ---------- |
| Button / clickable card hover | Shadow `hard-md` → `hard-lg`, move up-left by `0.0625rem` | fast       |
| Button / clickable card press | Move by the shadow offset, shadow to none                 | fast       |
| Focus                         | Ring appears without delay                                | 0          |
| Dropzone drag-over            | Border, fill, and shadow change                           | fast       |
| Upload row                    | Progress bar fills; status badge cross-fades              | base       |
| Bulk apply                    | Changed cells flash `primary-tint` once                   | slow       |
| Dialog / popover              | Fade + scale from 0.98                                    | base       |
| Toast                         | Slide in 0.5rem + fade                                    | base       |
| Sidebar collapse              | Width transition                                          | base       |
| Skeleton                      | Static `neutral-200` blocks with a slow opacity pulse     | 1.2 s loop |

Rules:

- Respect `prefers-reduced-motion: reduce`: remove movement, keep instant state changes.
- **Benchmark mode has no motion.** The app adds `data-benchmark="running"` to `<html>`; CSS disables all transitions and animations under it, and the progress strip updates only between measurement windows.
- Animate only `transform` and `opacity` (and `box-shadow` on small elements).

---

## 8. Layout

### 8.1 Application shell

```text
┌──────────────┬───────────────────────────────────────────────┐
│ Logo         │ Header: page title · device class chip · user │
├──────────────┼───────────────────────────────────────────────┤
│ Dashboard    │                                               │
│ Assets     ▸ │  Page content (max content-max-width)         │
│ Perf. Lab  ▸ │                                               │
│ Results      │                                               │
│ ──────────── │                                               │
│ ADMIN        │                                               │
│ Users        │                                               │
│ Option lists │                                               │
└──────────────┴───────────────────────────────────────────────┘
```

- Sidebar is a small Alpine component (`resources/js/features/app-shell.js`) because Basecoat's sidebar only opens and closes. Collapsible to icons (desktop, state remembered in the browser); off-canvas drawer on screens below `lg`.
- Sidebar groups (Assets, Performance Lab, Results, Administration, Option lists) are section headings; no item is hidden in a collapsible menu. In the icon rail, a heading becomes a thin divider and each item shows its own icon.
- The header always shows the confirmed device class chip ("Android Phone — Mid-range ▾") or a warning chip "Device class not set".

### 8.2 Page structure

Page header (title, short description, primary action on the right) → optional filter bar → content. Use section labels (§1.1) to separate blocks.

### 8.3 Grids

- Asset grid: `repeat(auto-fill, minmax(min(100%, 14rem), 1fr))`.
- Side-by-Side panes (look phase, benchmark mode, results): 2 panes side by side; 3–4 panes in a 2 × 2 grid. Below `md`, the panes stack in one column.

---

## 9. Key screens

### 9.1 Bulk upload

```text
UPLOAD ASSETS
┌──────────────────────────────────────────────────────────┐
│  [upload icon]  Drop GLB or GLTF files here, or Browse   │  ← dropzone
└──────────────────────────────────────────────────────────┘
APPLY TO ROWS
[Author ____] [Tool ▾] [Avg. production time __ h]  (Apply to selected) (Apply to all)
┌──┬─────┬───────────┬────────────┬────────┬────────┬──────┬──────┬────────┬────────┐
│☐ │thumb│ File name │ Asset name │ Author │ Tool ▾ │ Time │ Fmt  │ Size   │ Status │
├──┼─────┼───────────┼────────────┼────────┼────────┼──────┼──────┼────────┼────────┤
│☐ │ ▣   │ bird.glb  │ Bird       │ Nurizka│Blender │ 2.5  │ GLB  │ 2.1 MB │ Staged │
└──┴─────┴───────────┴────────────┴────────┴────────┴──────┴──────┴────────┴────────┘
                                         3 staged · 1 invalid   [Register 3 assets]
```

- Sticky table header and sticky bottom action bar.
- Invalid cells: `destructive` border and the message under the cell.
- On phones the table scrolls horizontally; the thumbnail and asset name columns are sticky on the left.

### 9.2 Performance Lab configuration

Numbered steps in one page: **1 Assets → 2 Settings → 3 Target → 4 Device → Run**. Each step is a static card with a step number in a filled `neutral-700` square. The Run button is disabled until all steps are valid, with the reason shown next to it.
T
### 9.3 Benchmark mode

- Full-screen view, `neutral-800` background, canvas centered at its fixed drawing-buffer aspect ratio.
- One thin progress strip above the canvas: method, asset, repetition, step, time left, "Measurement n of N", and a thin progress bar (`neutral-0` on `neutral-600`, 5.45:1) for completed measurements. Updated between windows only; the bar jumps and is never animated.
- A Cancel button outside the canvas area.
- No other UI.
- Side-by-Side: the panes keep their grid positions and sizes (§8.3). The measured pane has a `primary` strong border and the label "Measuring", both set before the window starts. The other panes show their last frame as a static image.

### 9.4 Results and reports

- Target checks table first: rows = Target checks (`BENCHMARK.md` §6.1), columns = assets (selection order), cells = Pass/Fail/Not measured badge + measured value + Target value. Below the table: "Meets all Target checks: Yes / No" per asset.
- Then metric tables and charts.
- **Smoothness label** (`BENCHMARK.md` §4.4): a `neutral` badge with the word and the FPS-equivalent, for example "Smooth · 59 fps". No icon, no status color. The label word carries the meaning.
- **Best marker** (`BENCHMARK.md` §4.5): an `outline` badge (neutral) with the `trophy` icon and the marker name, for example "Fastest load". "Too close to call" is an `outline` badge with the `equals` icon, shown on the table row, not on a pane. Markers never re-sort the results.
- Every Target check and metric row shows its label with the unit, and a one-line explanation under it (`body-sm`, `neutral-600`). The text lives in `App\Support\MetricDefinitions`; definitions stay in `BENCHMARK.md`.
- Every asset column header names the asset with its tool, format, and author. Test detail and the report editor start with a "Tested assets" table (selection order, metadata, file metrics).
- "Unstable" note uses the warning badge with a tooltip that explains the spread rule.

---

## 10. Charts

- Library: uPlot (canvas, approved at M4.1). Do not use a large charting framework.
- Allowed: line (metric vs instance count), bar (per-asset values, selection order), stacked bar (load phases), box plot or histogram (frame intervals), probe sequence (Crowd: copies per probe).
- Not allowed: pie, gauge, 3D charts, gradients, any composite score.
- Series colors: `primary`, `neutral-800`, `primary-light`, `neutral-600`, then dashed variants. Distinguish series by line style and marker shape as well as color.
- A Target value is a dashed `destructive` reference line with a text label.
- Charts render outside the benchmark canvas and only after the run.

---

## 11. 3D viewer appearance

| Mode                | Appearance                                                                                                                                                                                                            |
| ------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Preview             | Neutral background (`neutral-100`), floating control bar bottom-left (reset, fullscreen, gizmo toggle), XYZ gizmo top-right, hint text "Drag to orbit · Right-drag to pan · Scroll to zoom" (touch variant on phones) |
| Benchmark           | No controls, no gizmo, no hint. Background from the background preset                                                                                                                                                 |
| Public reproduction | As preview, plus the label **Interactive reproduction** top-left, and a note under the viewer that the visitor's device is not measured                                                                               |

---

## 12. Public site

- Same tokens and fonts as the app, with more white space.
- Landing: one viewport (`100svh`): platform name (display), one-sentence description, three short points (what it does), buttons to Reports and About. No metrics, no dashboard cards.
- Report gallery: card grid; each card shows title, method badge, device class, published date, asset count, the question (two lines). No best markers on cards.
- Report detail: question as the page lead; Target checks table; interactive reproduction; evidence; configuration; environment; limitations; conclusion.
- About: sidebar with section anchors + readable content (`readable-width`).
- Contact: the contact methods that the owner supplies. Never invent contact data.

---

## 13. Accessibility

- WCAG 2.2 AA contrast. Check `primary` text on light surfaces; use `primary-dark` for small text where needed.
- Visible focus on every interactive element.
- Keyboard access for all functions, including the selector modal, the upload table, and viewer reset.
- Labels for all controls. Errors linked with `aria-describedby`.
- Status is never color only.
- Hit targets at least `2.25rem` on touch screens.

---

## 14. Blade composition

```text
Layout (layouts/public|app|auth)
  └─ Page view (pages/...)
       ├─ Feature components (components/assets|performance|reports|admin)
       └─ UI components (components/ui)
```

- A page view reads as a description of the screen.
- Make a component only when it is reused or is a real domain concept. Do not make a component for each wrapper.
- No queries, authorization logic, or calculations in Blade. No Three.js code in Blade; the viewer component only renders a container with `data-*` configuration.
- No large inline `<script>` blocks. Alpine expressions in markup stay short; move logic into `Alpine.data()` modules in `resources/js/`.
