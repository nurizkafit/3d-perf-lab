# BENCHMARK.md — Measurement methodology

This file is the authority for how the product measures. A change to any definition here is a methodology change. The agent must ask the user before such a change (`AGENT.md` §3).

The test methods were redesigned on 2026-10-07 (decisions D39–D51 in `LOG.md`). The old Comparison, Stress, and Headroom tests no longer exist.

---

## 1. Principles

1. **Look, then measure.** Every test shows the assets in an interactive viewer first. The user can orbit, zoom, and switch motion scenarios. Measurement is a separate, controlled phase. The results are shown on the same viewer.
2. **The browser measures.** The server stores and evaluates. The server never calculates frame rates.
3. **Results are environment-specific.** Every result carries its device class and environment. The UI never implies that a result applies to other hardware.
4. **Best per metric, never an overall score.** The results mark the best asset for each metric (§4.5). There is no composite score, no weighted score, and no overall ranking.
5. **Repeat, then summarize.** A single run is noise. Each measurement repeats, and the result shows the median and the spread.
6. **Distributions over averages.** Average FPS hides stutter. Smoothness uses percentiles.
7. **Two clocks.** The frame interval (what the user sees) is capped by the display refresh rate. Render work time (cost) is not capped. Record both.
8. **Measurement is not interaction.** During a measurement window there is no user input, no camera control, no overlay on the canvas, and no DOM update.

---

## 2. Standard environment

These settings are fixed for every measurement. They are recorded in the run environment.

| Setting | Value |
|---|---|
| Renderer | `THREE.WebGLRenderer`, `antialias: false`, `powerPreference: 'high-performance'` |
| Pixel ratio | `1` (forced) |
| Drawing buffer | From the selected resolution preset (default 1280×720). The canvas is scaled by CSS to fit; the drawing buffer stays fixed |
| Color management | Three.js defaults for the installed version (record version) |
| Tone mapping | `NoToneMapping` unless the lighting preset says otherwise |
| Shadows | Off |
| Post-processing | None |
| Asset animation | Off by default. If an asset has animations and the user enables them, the run records `animation: on` and plays the first clip in a loop |
| Camera | From the camera preset. Side-by-Side: auto-frame at the start of each measurement, then fixed. Crowd: auto-frame the crowd of the current probe, then fixed for that probe. The same framing rule applies to every asset |
| Controls | Disabled during measurement |

WebGPU is out of scope for the MVP.

---

## 3. Metrics

Metrics are in four groups. The UI and the exports keep the groups separate. In the UI, the plain labels (§4.4, §6) come first; the technical metrics are in a collapsed **Technical details** section.

### 3.1 Asset metrics (static, from the file)

| Key | Unit | Source |
|---|---|---|
| `file_size` | bytes | Server |
| `format` | — | Server |
| `vertices`, `triangles`, `meshes`, `materials`, `textures` | count | Browser parse at upload |
| `texture_dimensions` | list of w×h | Browser parse |
| `texture_memory_estimate` | bytes | Σ (w × h × 4 × 1.33 if mipmaps) — **label: estimate** |

### 3.2 Load metrics (per load)

Measured on a **cold** load and a **warm** load. Side-by-Side only.

| Key | Definition |
|---|---|
| `transfer_size` | `PerformanceResourceTiming.transferSize` for the model request (and dependent files for GLTF). `0` on a warm cache hit |
| `fetch_ms` | Request start → last byte received |
| `parse_ms` | Start → end of `GLTFLoader.parseAsync` on the received buffer |
| `gpu_upload_ms` | Start of `renderer.compileAsync(scene, camera)` (or `compile` + texture init when async is not available) → completion |
| `first_frame_ms` | End of GPU upload → the next `requestAnimationFrame` callback after the first `renderer.render` |
| `ttff_ms` | **Time to first frame** = fetch + parse + GPU upload + first frame |

Cold load: request with a unique query parameter and `cache: 'reload'` (always downloads; the response is stored). Warm load: the same URL a second time with the default cache, so it is served from the browser cache (`transfer_size` 0 when the cache hit). Between loads, the scene, geometries, materials, and textures are disposed and `renderer.info.memory` is checked to be back to its baseline.

Loads are always measured one asset at a time, never in parallel. Network conditions are not simulated in the MVP. The run records `navigator.connection.effectiveType` when the browser exposes it.

### 3.3 Runtime metrics (per measurement window)

Each frame in the measurement window gives these values:

- **Frame interval** = difference between consecutive `requestAnimationFrame` timestamps.
- **Render work time (CPU)** = `performance.now()` after `renderer.render()` minus before it.
- **Physics time (CPU)** = `performance.now()` after the physics step minus before it. Physics scenario only; `null` otherwise.
- **GPU time** (optional) = `EXT_disjoint_timer_query_webgl2` result when the extension exists and the query is not disjoint. Recorded as `null` otherwise. Never estimated.

Summary values:

| Key | Definition |
|---|---|
| `frames` | Number of frames in the window |
| `interval_p50_ms`, `interval_p95_ms`, `interval_p99_ms` | Percentiles of frame interval (nearest-rank method) |
| `fps_avg` | `1000 / mean(frame interval)` — shown for familiarity only |
| `fps_1pct_low` | `1000 / mean(slowest 1% of frame intervals)` |
| `jank_count`, `jank_pct` | Frames with interval > 2 × goal frame time (§4.4); count and percentage |
| `work_p50_ms`, `work_p95_ms` | Percentiles of CPU render work time |
| `physics_p50_ms`, `physics_p95_ms` | Percentiles of physics time, or `null` |
| `gpu_p50_ms`, `gpu_p95_ms` | Percentiles of GPU time, or `null` |
| `draw_calls`, `rendered_triangles` | `renderer.info.render` for the last frame |
| `programs` | `renderer.info.programs.length` |
| `geometries`, `textures_in_memory` | `renderer.info.memory` |
| `js_heap_used` | `performance.memory.usedJSHeapSize` when exposed (Chromium only) — **label: browser-reported, JS heap only** |

**What p95 means (for the UI help text):** sort all frame intervals from fast to slow. p95 is the value that 95% of frames do not exceed. The slowest 5% are above it. It shows stutter that the average hides.

**Refresh rate:** during the first warm-up, the median frame interval of an empty scene gives the estimated display refresh rate. Recorded as `refresh_hz_estimate`. If the display runs below the Target frame rate (for example, a 30 Hz power-save mode against a 60 fps Target), the run shows a warning before measurement.

Raw samples (frame interval, work time, physics time, GPU time) are stored per window in `samples_json`. Maximum 3,600 samples per window; above that, keep the first 3,600 and record `samples_truncated: true`.

### 3.4 Context (per run)

Device class (confirmed), user agent, `userAgentData` (when exposed), platform, `hardwareConcurrency`, `deviceMemory` (when exposed), screen size, `devicePixelRatio` (native, for information), WebGL version, unmasked renderer/vendor (`WEBGL_debug_renderer_info`, when exposed), max texture size, refresh rate estimate, Three.js version, cannon-es version, resolution preset, battery charging state (when exposed), timestamp, test configuration snapshot, Target snapshot.

---

## 4. Test methods (MVP)

| Method | Model | Answers |
|---|---|---|
| **Side-by-Side** | 2–4 assets shown together; each measured alone | Which version loads faster and runs smoother? |
| **Crowd** | Copies of an asset (or of a set of objects) fill one scene until it stops being smooth | How many copies can this device show smoothly? |

### 4.1 Common run protocol

1. **Preflight**
   - Check that WebGL 2 is available. If not, stop with a clear message.
   - Ask the user to confirm the device class (§7).
   - Show the checklist: close other tabs and apps, keep the screen on, connect the charger (mobile).
   - Show the estimated run time, calculated from the configuration.
   - Request a screen wake lock (`navigator.wakeLock`) when available.
2. **Order**: shuffle the asset (or tool set) order with a seeded random generator. Record the seed and the final order. Each repetition uses a new shuffle from the same seed sequence.
3. **Repetitions**: default 3, range 1–5.
4. **Cool-down**: an idle pause between measured assets (Side-by-Side) and between searches (Crowd). Default 3 s on desktop/laptop, 10 s on phone/tablet (from the device category).
5. **Invalidation**: the run is invalid when `visibilitychange` reports hidden, the window loses focus > 1 s, or the WebGL context is lost. The run stops and is saved with status `invalid` and the reason. Invalid runs never produce results or Target checks.
6. **No DOM updates during a window**: the progress strip updates only between windows. The canvas keeps rendering the scene (that is the measurement).
7. **Aggregation**: for each metric, the reported value is the **median of the repetitions**. The spread is shown as min–max. If the spread of `interval_p95_ms` is more than 25% of the median, the result shows an "Unstable" note.

### 4.2 Motion scenarios

Each test measures smoothness in one or more motion scenarios. All three are selected by default. The user can untick any of them, but at least one stays selected. The run measures the selected scenarios in this fixed order: Static, Spin, Physics.

| Scenario | What moves | Camera |
|---|---|---|
| **Static** | Nothing | Fixed |
| **Spin** | Each object (each instance in a crowd) turns around its own vertical axis at 180°/s. Rotation = speed × elapsed time. All instances start at the same angle | Fixed |
| **Physics** | The objects fall into an open box under gravity, then the box shakes | Fixed |

The motion is identical for every asset: same speed, same drop positions (seeded), same shake path, same physics settings. The assets themselves are never rescaled.

**Physics settings** (`cannon-es`, loaded with dynamic `import()` on test pages only):

- **Scale unit** `d` = the largest bounding-sphere diameter of the assets in the scene. Gravity = 9.82 × `d` per s² downward, so the motion looks the same for small and large objects.
- **Fixed time step**: one physics step of 1/60 s per rendered frame (no sub-stepping, no catch-up). The simulation is the same sequence of states on every device; a slow device shows fewer frames in the window. Solver iterations: 10. Sleeping: off.
- **Collider**: a simplified convex hull of each asset (decision D50). The hull is computed from the asset's vertices (Three.js `ConvexHull`, part of `three/addons`) and reduced to at most 32 hull points with the same reduction rule for every asset. It follows the shape closely but not exactly; concave parts (for example the inside of a cup) are filled. The hull is computed once per asset before measurement; `collider_build_ms` is recorded but is not part of any window. Instances of one asset share the hull shape.
- **Box**: a kinematic, open-top box. Inner floor: Side-by-Side 3`d` × 3`d`; Crowd ⌈√N⌉ × 1.2`d` per side for N instances. Wall height: 2`d` (Side-by-Side) or 3`d` (Crowd). The box is drawn as its 12 edges (one line object, the same for every asset).
- **Drop**: instances start in a grid of layers above the box (1.2`d` spacing) with a seeded rotation and a seeded small offset (≤ 0.1`d`).
- **Shake**: after the warm-up, during the whole window, the box moves along X: `x(t) = 0.15 × box width × sin(2π × 1.5 Hz × t)`.
- **Warm-up for Physics**: at least 2 s and at least 120 physics steps (drop and settle), so the shake always starts at the same simulation step on every device. The shake starts at step 120.
- The frame interval includes physics time. `physics_p95_ms` and `work_p95_ms` are shown separately, so the user can see whether physics or drawing made a frame slow.

### 4.3 Side-by-Side test

> 2–4 assets in a grid of panes. Look at them together; measure them one at a time.

**Look phase (not measured)**

- One pane per asset, in selection order. Each pane shows the asset name, tool, object, file size, and triangles.
- One shared camera: orbiting, panning, or zooming in one pane moves all panes.
- A motion switch (Static / Spin / Physics) applies to all panes. In Physics, a "Shake" action shakes the boxes again.
- Reset view, fullscreen, keyboard and touch support as in preview mode.

**Measure phase** — for each repetition, for each asset (shuffled order):

1. The other panes stop rendering and keep their last frame. The measured pane is marked before the window starts.
2. Create a fresh renderer and scene for the measured asset (standard environment, presets).
3. Cold load → record load metrics. Dispose; warm load → record load metrics.
4. For each selected motion scenario: warm-up (default 2 s), measurement window (default 10 s).
5. Dispose renderer; cool-down.

Settings: assets (2–4), motion scenarios, repetitions, warm-up (1–10 s), measurement (5–30 s), resolution, camera, lighting, background, animation on/off, Target.

**Results** (shown on the same panes, and in a table below):

- Per pane: the Target checks (§6) with icon and word, the smoothness label per motion scenario (§4.4), cold load time, file size, and the best markers it earned (§4.5).
- The look phase works again on the results page (not measured).
- Below: a table with the plain values per asset (selection order), a load-phase stacked bar per asset (fetch, parse, GPU upload, first frame), and the collapsed Technical details (all §3 metrics per motion).

### 4.4 Smoothness label

The Target sets the goal frame rate (`target_fps`). Goal frame time `T = 1000 / target_fps` ms. Each measurement window gets one label (decision D44):

| Label | Rule |
|---|---|
| **Smooth** | `interval_p95_ms ≤ 1.25 × T` **and** `jank_pct ≤ 1%` |
| **Mostly smooth** | Not Smooth, and `interval_p95_ms ≤ 2 × T` |
| **Choppy** | Otherwise |

Jank threshold: an interval > 2 × `T`. The label of an aggregated result uses the median `interval_p95_ms` and the median `jank_pct` of the repetitions. The label always shows with the measured FPS-equivalent (`1000 / interval_p95_ms`) and, in Technical details, the values it came from.

### 4.5 Best markers

The results mark the best asset (or tool set) for each metric (decision D39). No marker combines metrics.

| Marker | Metric | Better | Method |
|---|---|---|---|
| Fastest load | median cold `ttff_ms` | lower | Side-by-Side |
| Smallest file | `file_size` | lower | Side-by-Side, Tool summary |
| Lightest to draw | median `work_p95_ms`, per motion scenario | lower | Side-by-Side |
| Most copies | median `smooth_copies`, per motion scenario | higher | Crowd, Tool summary |

Rules:

- A marker needs at least two compared items.
- For repeated measurements, the best item gets the marker only when its min–max spread does not overlap the spread of any other item. Otherwise the row shows "Too close to call" and no item gets the marker.
- For static values (file size), the lowest value wins; equal values share the marker.
- "Lightest to draw" exists because frame interval is capped by the display: two assets can both be Smooth while one costs much less to draw.
- The default order of assets is still the selection order. Markers never re-sort the results.

### 4.6 Crowd test

> Copies fill one scene, step by step, until the scene stops being Smooth. The user watches it happen.

Scenarios:

- **Per asset** — 1–8 assets. Each asset gets its own crowd and its own search.
- **Per tool** — 2 or more authoring tools, each with a set of objects. Only objects that exist in **every** selected tool can be chosen; the user picks which of those objects to include (up to 30), and which asset when a tool has more than one asset for an object. Objects that are missing in any selected tool are listed and excluded (decision D48). Each tool's set gets its own crowd and its own search. Instances are assigned round-robin in object order, so the count is **copies of each object** (1× = one instance of every chosen object).

Instance mode: `clone` (MVP) — `object.clone()`, geometry and materials shared, one mesh per instance. The run records `instance_mode`.

Placement (Static and Spin): a deterministic square grid on the XZ plane, spacing 1.2`d`. Physics: the drop grid of §4.2.

Search procedure (per asset or per tool set, per selected motion scenario):

1. Probe at 1 copy. If it is not Smooth, the result is `0`.
2. Double the copies (2, 4, 8, …) until a probe is not Smooth or the total instance count reaches the cap (default 1,024 instances, max 4,096). For Per tool, the cap in copies is ⌊cap ÷ number of objects⌋.
3. If a probe is not Smooth, binary search between the last Smooth and the first not-Smooth count until the gap ≤ max(1, 5% of the last Smooth count).
4. Each probe: build the instances, auto-frame the crowd, warm-up 1 s (Physics: 2 s), measurement window 4 s.
5. Result: `smooth_copies` = the last Smooth count, and `smooth_instances` = copies × objects. If the cap is Smooth, the result is `≥ cap`.
6. Time limit: each search stops after 3 minutes and records `time_limit_reached: true` with the last Smooth count.

The pass criterion of a probe is the Smooth label (§4.4) at the Target's `target_fps`. The Crowd test has no other Target check (decision D46).

During the run the canvas shows the crowd. Between probes the progress strip shows the last probe, for example "64 copies — Smooth".

Settings: scenario, assets or tools + objects, motion scenarios, cap, repetitions, resolution, camera, lighting, background, Target.

**Results:**

- A bar per asset or tool set, per motion scenario, with the value printed ("Smooth up to 64 copies"), in selection order, with the Most copies marker.
- The probe sequence chart (probe number on X, copies on Y; Smooth / Mostly smooth / Choppy marked by shape and label, not color only).
- An explore viewer (not measured): choose an asset or tool set, a motion scenario, and a copy count with a slider (default: its `smooth_copies`); orbit and zoom.
- Collapsed Technical details per probe.

### 4.7 Tool summary

A view, not a test. It compares authoring tools for one device class with the results that already exist.

- The user selects a device class and two or more tools. Only objects that exist in every selected tool are used; the user can untick objects. Missing objects are listed.
- For each asset, the summary uses the most recent completed, valid result on that device class (Side-by-Side for load and smoothness; Crowd Per asset for copies). Tool crowd results (Crowd Per tool) are shown as they were measured.
- Per tool it shows: number of objects compared; median file size; median triangles; median cold load time; how many assets pass each Target check (for example "18 of 20 load in time"), with the Target of each underlying test; median `smooth_copies` per motion scenario; and the Tool crowd results when they exist.
- Best markers (§4.5) apply per row. The median across objects is a summary of measured values, not a score.
- Every value links to the tests it came from. An asset without a result on that device class shows "Not tested".

---

## 5. What the server does with results

1. Validate the payload schema and the run state (`running`, owned by the user, not expired — a run expires 30 minutes after start; a Crowd run with a long estimate gets the estimate plus 15 minutes).
2. Store `test_results` rows (per asset or tool set, per motion scenario, per probe, per repetition) with summaries and raw samples.
3. Calculate the aggregated values (median, min, max) from the submitted summaries. This is arithmetic on submitted numbers, not measurement.
4. Calculate the smoothness labels (§4.4) and the Target checks (§6) from the aggregates and the Target snapshot, and store them.
5. Mark the run `completed`.

Best markers (§4.5) are calculated when results are shown, from the stored aggregates.

---

## 6. Targets

A Target is a simple performance goal for one kind of device (decisions D12, D44). It replaces the old 9-item budget presets.

### 6.1 Target checks

| Check | Compares | Passes when | Applies to |
|---|---|---|---|
| **Loads in time** | median cold `ttff_ms` | ≤ `max_load_s` × 1000 | Side-by-Side |
| **Runs smooth** | smoothness label per motion scenario | label is Smooth at `target_fps` | Side-by-Side (one check per scenario); Crowd uses `target_fps` as its pass criterion |
| **File size** | `file_size` | ≤ `max_file_size_mb` | Side-by-Side |

Outcomes: **Pass**, **Fail**, **Not measured**. Every result shows each check with an icon and a word, never color only.

Summary per asset: "Meets all Target checks: Yes / No". This is not a score and never sorts the results.

The old metrics (p95 frame, jank, draw calls, texture memory, and the others in §3) are still measured and stored. They are shown in Technical details, not as checks.

### 6.2 Seed Targets

Starting values (accepted by the user on 2026-10-07; the admin can change them):

| Target | Device class link | Loads in | Smooth at | File size |
|---|---|---|---|---|
| Desktop | Desktop and Laptop classes | ≤ 3 s | 60 fps | ≤ 20 MB |
| Phone — Flagship | Android Phone — Flagship, iPhone — Pro / Flagship | ≤ 3 s | 60 fps | ≤ 12 MB |
| Phone — Mid-range | Android Phone — Mid-range, iPhone — Standard | ≤ 4 s | 30 fps | ≤ 8 MB |
| Phone — Entry | Android Phone — Entry | ≤ 6 s | 30 fps | ≤ 5 MB |

The user selects one Target per test. The dashboard suggests the Target linked to the confirmed device class. The user can change the values for that test; the test stores the final values.

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

### 8.1 Camera Path scenario

A fourth motion scenario: a deterministic scripted camera movement (orbit 360°, then dolly in to 0.3 × the framing distance, then out) over a fixed duration. Records the runtime metrics as a time series aligned to the path position. Shows close-up and overdraw costs that a fixed camera hides. Engine requirement for the MVP: the measurement loop accepts a per-frame update function (used by Spin and Physics in the MVP).

### 8.2 Device Matrix

One test session runs on several devices (one run per device, each user logs in on that device). The results group by device class and show the Target checks per class. Engine requirement for the MVP: `test_runs` is a separate table; reports read results through runs, never directly through the session.

### 8.3 Visual Check

At the end of a Side-by-Side run, the engine captures screenshots at fixed angles (front, side, top, three-quarter) at the same resolution. Reports show them side by side for human judgement. No image score. Engine requirement for the MVP: the renderer uses `preserveDrawingBuffer: false` in measurement; capture uses a separate render after measurement.

### 8.4 InstancedMesh mode

Crowd option that uses `THREE.InstancedMesh` per mesh primitive. Results are labelled with `instance_mode: instanced` and are never mixed with `clone` results in one chart.

---

## 9. Known limitations (shown in every report)

- Results apply to the recorded device and browser only.
- Browsers limit timer precision; very short intervals have coarse resolution.
- Frame interval is capped by the display refresh rate.
- GPU time is available on some browsers only.
- Mobile devices can reduce performance when hot or on battery saver.
- Texture memory is an estimate, not a measured GPU value.
- JS heap is browser-reported and excludes GPU memory.
- Physics uses a simplified convex hull per asset, not the exact mesh; concave parts are filled. Physics time depends partly on the hull, not only on the asset.
