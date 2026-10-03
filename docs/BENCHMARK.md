# BENCHMARK.md — Measurement methodology

This file is the authority for how the product measures. A change to any definition here is a methodology change. The agent must ask the user before such a change (`AGENT.md` §3).

---

## 1. Principles

1. **The browser measures.** The server stores and evaluates. The server never calculates frame rates.
2. **Results are environment-specific.** Every result carries its device class and environment. The UI never implies that a result applies to other hardware.
3. **No ranking, no composite score.** The product shows measurements and budget pass/fail only.
4. **Repeat, then summarize.** A single run is noise. Each measurement repeats, and the report shows the median and the spread.
5. **Distributions over averages.** Average FPS hides stutter. Budgets use percentiles.
6. **Two clocks.** The frame interval (what the user sees) is capped by the display refresh rate. Render work time (cost) is not capped. Record both.
7. **Benchmark mode is not preview mode.** Benchmark mode has no user interaction, no auto-rotate unless configured, no overlays on the canvas, and no UI updates during a measurement window.

---

## 2. Standard environment

These settings are fixed for every benchmark. They are recorded in the run environment.

| Setting | Value |
|---|---|
| Renderer | `THREE.WebGLRenderer`, `antialias: false`, `powerPreference: 'high-performance'` |
| Pixel ratio | `1` (forced) |
| Drawing buffer | From the selected resolution preset (default 1280×720). The canvas is scaled by CSS to fit; the drawing buffer stays fixed |
| Color management | Three.js defaults for the installed version (record version) |
| Tone mapping | `NoToneMapping` unless the lighting preset says otherwise |
| Shadows | Off |
| Post-processing | None |
| Animation | Off by default. If an asset has animations and the user enables them, the run records `animation: on` and plays the first clip in a loop |
| Camera | From the camera preset. Auto-frame fits the bounding sphere of the scene at the start and does not move during measurement |
| Controls | Disabled |

WebGPU is out of scope for the MVP.

---

## 3. Metrics

Metrics are in four groups. The UI and the exports keep the groups separate.

### 3.1 Asset metrics (static, from the file)

| Key | Unit | Source |
|---|---|---|
| `file_size` | bytes | Server |
| `format` | — | Server |
| `vertices`, `triangles`, `meshes`, `materials`, `textures` | count | Browser parse at upload |
| `texture_dimensions` | list of w×h | Browser parse |
| `texture_memory_estimate` | bytes | Σ (w × h × 4 × 1.33 if mipmaps) — **label: estimate** |

### 3.2 Load metrics (per load)

Measured on a **cold** load and a **warm** load.

| Key | Definition |
|---|---|
| `transfer_size` | `PerformanceResourceTiming.transferSize` for the model request (and dependent files for GLTF). `0` on a warm cache hit |
| `fetch_ms` | Request start → last byte received |
| `parse_ms` | Start → end of `GLTFLoader.parseAsync` on the received buffer |
| `gpu_upload_ms` | Start of `renderer.compileAsync(scene, camera)` (or `compile` + texture init when async is not available) → completion |
| `first_frame_ms` | End of GPU upload → the next `requestAnimationFrame` callback after the first `renderer.render` |
| `ttff_ms` | **Time to first frame** = fetch + parse + GPU upload + first frame |

Cold load: request with `cache: 'no-store'` and a unique query parameter. Warm load: the same URL a second time with the default cache. Between loads, the scene, geometries, materials, and textures are disposed and `renderer.info.memory` is checked to be back to its baseline.

Network conditions are not simulated in the MVP. The run records `navigator.connection.effectiveType` when the browser exposes it.

### 3.3 Runtime metrics (per measurement window)

Each frame in the measurement window gives two values:

- **Frame interval** = difference between consecutive `requestAnimationFrame` timestamps.
- **Render work time (CPU)** = `performance.now()` after `renderer.render()` minus before it.
- **GPU time** (optional) = `EXT_disjoint_timer_query_webgl2` result when the extension exists and the query is not disjoint. Recorded as `null` otherwise. Never estimated.

Summary values:

| Key | Definition |
|---|---|
| `frames` | Number of frames in the window |
| `interval_p50_ms`, `interval_p95_ms`, `interval_p99_ms` | Percentiles of frame interval (nearest-rank method) |
| `fps_avg` | `1000 / mean(frame interval)` — shown for familiarity, never used for a budget |
| `fps_1pct_low` | `1000 / mean(slowest 1% of frame intervals)` |
| `jank_count`, `jank_pct` | Frames with interval > 2 × budget frame time; count and percentage |
| `work_p50_ms`, `work_p95_ms` | Percentiles of CPU render work time |
| `gpu_p50_ms`, `gpu_p95_ms` | Percentiles of GPU time, or `null` |
| `draw_calls`, `rendered_triangles` | `renderer.info.render` for the last frame (stable scene) |
| `programs` | `renderer.info.programs.length` |
| `geometries`, `textures_in_memory` | `renderer.info.memory` |
| `js_heap_used` | `performance.memory.usedJSHeapSize` when exposed (Chromium only) — **label: browser-reported, JS heap only** |

**What p95 means (for the UI help text):** sort all frame intervals from fast to slow. p95 is the value that 95% of frames do not exceed. The slowest 5% are above it. It shows stutter that the average hides.

**Refresh rate:** during the first warm-up, the median frame interval of an empty scene gives the estimated display refresh rate. Recorded as `refresh_hz_estimate`. If the display runs below the budget frame rate (for example, a 30 Hz power-save mode), the run shows a warning before measurement.

Raw samples (frame interval, work time, GPU time) are stored per window in `samples_json`. Maximum 3,600 samples per window; above that, keep the first 3,600 and record `samples_truncated: true`.

### 3.4 Context (per run)

Device class (confirmed), user agent, `userAgentData` (when exposed), platform, `hardwareConcurrency`, `deviceMemory` (when exposed), screen size, `devicePixelRatio` (native, for information), WebGL version, unmasked renderer/vendor (`WEBGL_debug_renderer_info`, when exposed), max texture size, refresh rate estimate, Three.js version, resolution preset, battery charging state (when exposed), timestamp, test configuration snapshot, budget snapshot.

---

## 4. Test methods (MVP)

### 4.1 Common run protocol

1. **Preflight**
   - Check that WebGL 2 is available. If not, stop with a clear message.
   - Ask the user to confirm the device class (§7).
   - Show the checklist: close other tabs and apps, keep the screen on, connect the charger (mobile).
   - Request a screen wake lock (`navigator.wakeLock`) when available.
2. **Order**: shuffle the asset order with a seeded random generator. Record the seed and the final order. Each repetition uses a new shuffle from the same seed sequence.
3. **Repetitions**: default 3, range 1–5.
4. **Cool-down**: an idle pause between measurements. Default 3 s on desktop/laptop, 10 s on phone/tablet (from the device category).
5. **Invalidation**: the run is invalid when `visibilitychange` reports hidden, the window loses focus > 1 s, or the WebGL context is lost. The run stops and is saved with status `invalid` and the reason. Invalid runs never produce budget results.
6. **No UI during a window**: the progress strip updates only between windows.
7. **Aggregation**: for each metric, the reported value is the **median of the repetitions**. The spread is shown as min–max. If the spread of `interval_p95_ms` is more than 25% of the median, the result shows an "Unstable" note.

### 4.2 Comparison Test

> N assets → N independent viewers, run one at a time.

Each viewer is a fresh renderer and scene. Only one viewer renders at a time. Other viewers are not created until their turn.

For each repetition, for each asset (shuffled order):

1. Create renderer and scene (standard environment, presets).
2. Cold load → record load metrics.
3. Dispose; warm load → record load metrics.
4. Warm-up (default 2 s).
5. Measurement window (default 10 s).
6. Dispose renderer; cool-down.

Settings: assets (1–8), repetitions, warm-up (1–10 s), measurement (5–30 s), resolution, camera, lighting, background, animation on/off.

Results UI: a budget table; a metric table (columns = assets in selection order); a load-phase stacked bar per asset (fetch, parse, GPU upload, first frame); a frame-interval distribution chart per asset (histogram or box plot).

### 4.3 Stress Test

> N instances → 1 scene, at fixed steps.

Scenarios:

- **Repeated asset** — one asset, cloned N times.
- **Mixed asset** — 2–8 assets with the same authoring tool; instances assigned round-robin in selection order.

Instance mode: `clone` (MVP) — `object.clone()`, geometry and materials shared, one mesh per instance. `InstancedMesh` mode is a later option (`PROJECT.md` Later Versions). The run records `instance_mode`.

Placement: a deterministic square grid on the XZ plane. Spacing = 1.2 × the largest bounding-box diameter of the selected assets. The camera frames the grid of the **largest** step and stays fixed for all steps, so camera distance does not change between steps.

For each repetition: load assets once (cold, then reuse), then for each step in ascending order: build the instance set, warm-up, measurement window, record. Cool-down between steps is not used; cool-down is used between repetitions.

Settings: scenario, assets, instance step preset (editable list, max 500 instances), repetitions, warm-up, measurement, resolution, camera, lighting, background.

Results UI: budget table (evaluated at each step); table per step; line chart with instance count on the X axis and `interval_p95_ms` and `work_p95_ms` (and `gpu_p95_ms` when present) on the Y axis, with the budget frame time as a reference line.

### 4.4 Headroom Test

> Instances increase automatically until the frame budget fails.

Goal: one comparable number for each asset on each device — the largest instance count that stays inside the frame budget. This value shows differences even when frame rate is capped at the display refresh rate for small scenes.

Pass criterion for one probe: `interval_p95_ms ≤ budget.max_p95_frame_ms` **and** `jank_pct ≤ budget.max_jank_pct`.

Search procedure (per asset, or per asset set for mixed):

1. Probe at 1. If it fails, the result is `0` (fails at one instance).
2. Double the count (2, 4, 8, …) until a probe fails or the count reaches the cap (default 1,024, max 4,096).
3. If a probe fails, binary search between the last pass and the first fail until the gap ≤ max(1, 5% of the last pass).
4. Each probe: warm-up 1 s, measurement window 4 s.
5. Result: `headroom_instances` = last passing count. If the cap passes, the result is `≥ cap`.

Scenarios: repeated asset (each selected asset gets its own search) and mixed asset (same-tool rule; one search for the set, round-robin assignment).

Repetitions: default 3. The reported value is the median of the repetitions; spread min–max.

Placement and instance mode: as the Stress Test. The camera frames the grid at the cap count and stays fixed.

Time limit: each search stops after 3 minutes and records `time_limit_reached: true` with the last passing count.

Results UI: the `headroom_instances` per asset (a bar per asset with the value printed, in selection order — no sorting); the probe sequence chart (probe number on X, instance count on Y, pass/fail marked by shape and label, not color only).

---

## 5. What the server does with results

1. Validate the payload schema and the run state (`running`, owned by the user, not expired — a run expires 30 minutes after start).
2. Store `test_results` rows (per asset, per step, per repetition) with summaries and raw samples.
3. Calculate the aggregated values (median, min, max) from the submitted summaries. This is arithmetic on submitted numbers, not measurement.
4. Evaluate the budget snapshot and store `budget_evaluations`.
5. Mark the run `completed`.

---

## 6. Performance budgets

### 6.1 Budget items

Each budget preset has these items. Each item has an `enabled` flag and a limit.

| Key | Compares | Rule | Applies to |
|---|---|---|---|
| `max_file_size_mb` | `file_size` | ≤ | All methods |
| `max_ttff_cold_ms` | median cold `ttff_ms` | ≤ | Comparison |
| `max_ttff_warm_ms` | median warm `ttff_ms` | ≤ | Comparison |
| `max_p95_frame_ms` | median `interval_p95_ms` | ≤ | All methods (Stress: at each step; Headroom: pass criterion) |
| `max_jank_pct` | median `jank_pct` | ≤ | All methods |
| `max_draw_calls` | `draw_calls` | ≤ | Comparison, Stress |
| `max_texture_memory_mb` | `texture_memory_estimate` | ≤ | All methods |
| `min_headroom_instances` | median `headroom_instances` | ≥ | Headroom |
| `min_instances_at_budget` | the largest step that passes `max_p95_frame_ms` | ≥ | Stress |

Outcomes: **Pass**, **Fail**, **Not measured** (metric not available, for example GPU time). A disabled item is not shown.

Summary per asset: "Meets all enabled budget items: Yes / No". This is not a score and the UI never sorts by it.

### 6.2 Seed budget presets

Starting values. The admin can change them.

| Preset | Device class link | File size | Cold TTFF | Warm TTFF | p95 frame | Jank | Draw calls | Texture mem | Headroom |
|---|---|---|---|---|---|---|---|---|---|
| Desktop — Standard | Desktop/Laptop classes | 20 MB | 3,000 ms | 1,000 ms | 16.7 ms | 1% | 500 | 512 MB | 50 |
| Mobile — Mid-range | Android Mid-range, iPhone Standard | 8 MB | 4,000 ms | 1,500 ms | 33.3 ms | 2% | 200 | 256 MB | 20 |
| Mobile — Entry | Android Entry | 5 MB | 6,000 ms | 2,500 ms | 33.3 ms | 3% | 100 | 128 MB | 10 |
| Mobile — Flagship | Android Flagship, iPhone Pro | 12 MB | 3,000 ms | 1,000 ms | 16.7 ms | 1% | 300 | 384 MB | 40 |

`min_instances_at_budget` has no seed value: it is seeded disabled (no limit) in all four presets.

The user selects one preset per test. The dashboard suggests the preset linked to the confirmed device class. The user can change any value for that test; the test stores the final values.

---

## 7. Device classes

### 7.1 Detection (suggestion only)

The dashboard collects the context signals in §3.4 and suggests a class with these rules, in order:

1. Category from `userAgentData.mobile`, user agent, touch points, and screen size: phone, tablet, laptop/desktop.
2. Platform: iOS/iPadOS, Android, Windows, macOS, Linux, ChromeOS.
3. Tier hint (phones):
   - iPhone: the GPU string is usually masked as "Apple GPU". The dashboard suggests **iPhone Standard** and asks the user to choose.
   - Android: unmasked GPU string when exposed, plus `deviceMemory` and `hardwareConcurrency`. A small mapping table in code maps known GPU families to tiers (for example, Adreno 7xx / Mali-G7xx Immortalis → Flagship; Adreno 6xx / Mali-G6xx → Mid-range; older → Entry). If no rule matches, no tier is preselected.
4. Desktop/laptop: an unmasked GPU string that contains a discrete GPU family (NVIDIA GeForce/RTX, AMD Radeon RX) → "Dedicated GPU"; else "Integrated GPU". Laptop versus desktop cannot be detected reliably; the user chooses.

The suggestion is never stored as the class. The user **must** confirm a class from the list before the first run on that device. The confirmed class is remembered in the session (and in `localStorage` as a convenience) and shown in the app header with a "Change" action.

### 7.2 Seed device classes

| Name | Category | Platform |
|---|---|---|
| Desktop — Integrated GPU | desktop | any |
| Desktop — Dedicated GPU | desktop | any |
| Laptop — Integrated GPU | laptop | any |
| Laptop — Dedicated GPU | laptop | any |
| Android Phone — Entry | phone | android |
| Android Phone — Mid-range | phone | android |
| Android Phone — Flagship | phone | android |
| iPhone — Standard | phone | ios |
| iPhone — Pro / Flagship | phone | ios |
| Android Tablet | tablet | android |
| iPad | tablet | ios |

The admin can add, rename, deactivate, and reorder classes.

---

## 8. Methods for later versions

These methods are part of the project plan (`PROJECT.md` Later Versions). They are documented here so the MVP data model and engine do not block them. Do not implement them in the MVP.

### 8.1 Camera Path Test

A deterministic scripted camera movement (orbit 360°, then dolly in to 0.3 × the framing distance, then out) over a fixed duration. Records the runtime metrics as a time series aligned to the path position. Shows close-up and overdraw costs that a fixed camera hides. Engine requirement for the MVP: the measurement loop accepts a per-frame camera update function (no-op in MVP).

### 8.2 Device Matrix

One test session runs on several devices (one run per device, each user logs in on that device). The report groups results by device class and shows the budget result per class. Engine requirement for the MVP: `test_runs` is a separate table; reports read results through runs, never directly through the session.

### 8.3 Visual Check

At the end of a Comparison run, the engine captures screenshots at fixed angles (front, side, top, three-quarter) at the same resolution. The report shows them side by side for human judgement. No image score. Engine requirement for the MVP: the renderer uses `preserveDrawingBuffer: false` in measurement; capture uses a separate render after measurement.

### 8.4 InstancedMesh mode

Stress and Headroom option that uses `THREE.InstancedMesh` per mesh primitive. Results are labelled with `instance_mode: instanced` and are never mixed with `clone` results in one chart.

---

## 9. Known limitations (shown in every report)

- Results apply to the recorded device and browser only.
- Browsers limit timer precision; very short intervals have coarse resolution.
- Frame interval is capped by the display refresh rate.
- GPU time is available on some browsers only.
- Mobile devices can reduce performance when hot or on battery saver.
- Texture memory is an estimate, not a measured GPU value.
- JS heap is browser-reported and excludes GPU memory.
