// Measurement loop (BENCHMARK.md §3.3). Runs only inside benchmark mode. It never touches the DOM:
// the caller updates the progress strip between windows only (BENCHMARK.md §4.1 step 6).

export const MAX_SAMPLES = 3600;

export class InvalidRun extends Error {
    constructor(reason) {
        super(reason);
        this.reason = reason;
    }
}

/**
 * Invalidation rules (BENCHMARK.md §4.1 step 5): the tab becomes hidden, the window loses focus for more
 * than 1 s, or the WebGL context is lost. `guard.reason` is set once; measurement checks it every frame.
 */
export function createRunGuard(canvas) {
    const guard = { reason: null };
    let blurTimer = null;
    const fail = (reason) => {
        guard.reason ??= reason;
    };
    const onVisibility = () => document.visibilityState === 'hidden' && fail('The tab was hidden during measurement.');
    const onBlur = () => {
        clearTimeout(blurTimer);
        blurTimer = setTimeout(() => fail('The window lost focus for more than 1 second.'), 1000);
    };
    const onFocus = () => clearTimeout(blurTimer);
    const onContextLost = () => fail('The WebGL context was lost.');

    const canvases = new Set();
    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('blur', onBlur);
    window.addEventListener('focus', onFocus);

    /** Watch the canvas of each new renderer (Side-by-Side creates one per measured asset). */
    guard.watchCanvas = (element) => {
        element.addEventListener('webglcontextlost', onContextLost);
        canvases.add(element);
    };
    /** Stop watching before an intentional dispose: forceContextLoss() fires webglcontextlost too. */
    guard.unwatchCanvas = (element) => {
        element.removeEventListener('webglcontextlost', onContextLost);
        canvases.delete(element);
    };
    if (canvas) guard.watchCanvas(canvas);

    guard.dispose = () => {
        clearTimeout(blurTimer);
        document.removeEventListener('visibilitychange', onVisibility);
        window.removeEventListener('blur', onBlur);
        window.removeEventListener('focus', onFocus);
        canvases.forEach((element) => element.removeEventListener('webglcontextlost', onContextLost));
    };

    return guard;
}

/** Nearest-rank percentile of a sorted list (BENCHMARK.md §3.3). */
export function percentile(sorted, p) {
    if (sorted.length === 0) return null;
    const rank = Math.ceil((p / 100) * sorted.length);

    return sorted[Math.min(Math.max(rank, 1), sorted.length) - 1];
}

const mean = (values) => values.reduce((sum, value) => sum + value, 0) / values.length;
const round = (value, digits = 3) => (value === null || value === undefined ? null : Math.round(value * 10 ** digits) / 10 ** digits);

/** GPU time per frame from EXT_disjoint_timer_query_webgl2, when available. Never estimated. */
function createGpuTimer(renderer) {
    const gl = renderer.getContext();
    const ext = gl.getExtension?.('EXT_disjoint_timer_query_webgl2');
    if (!ext || !(gl instanceof WebGL2RenderingContext)) return null;

    const pending = []; // { query, index }
    const results = new Map(); // frame index → ms
    let disjoint = false;

    return {
        begin(index) {
            const query = gl.createQuery();
            gl.beginQuery(ext.TIME_ELAPSED_EXT, query);
            pending.push({ query, index });
        },
        end() {
            gl.endQuery(ext.TIME_ELAPSED_EXT);
        },
        poll() {
            if (gl.getParameter(ext.GPU_DISJOINT_EXT)) disjoint = true;
            while (pending.length && gl.getQueryParameter(pending[0].query, gl.QUERY_RESULT_AVAILABLE)) {
                const { query, index } = pending.shift();
                if (!disjoint) results.set(index, gl.getQueryParameter(query, gl.QUERY_RESULT) / 1e6);
                gl.deleteQuery(query);
            }
        },
        get pendingCount() {
            return pending.length;
        },
        /** Samples aligned to frames; null for frames whose query was disjoint or never returned. */
        samples(frameCount) {
            pending.forEach(({ query }) => gl.deleteQuery(query));
            pending.length = 0;
            if (disjoint) return null;

            return Array.from({ length: frameCount }, (_, i) => (results.has(i) ? results.get(i) : null));
        },
    };
}

const nextFrame = () => new Promise((resolve) => requestAnimationFrame(resolve));

/**
 * Render frames for `durationMs` and record frame interval, CPU render work time, physics time, and GPU time.
 * @param {{ renderer, scene, camera, durationMs: number, goalFrameMs: number, guard?: {reason: string|null},
 *           beforeFrame?: (elapsedMs: number) => number|null|void }} options
 *   goalFrameMs: 1000 / Target fps; a frame slower than twice this is stutter (jank).
 *   beforeFrame: the motion scenario update (motion.js); returns the physics time in ms, or null.
 * @returns {Promise<{ summary: object, samples: { interval: number[], work: number[], physics: number[]|null, gpu: (number|null)[]|null, truncated: boolean } }>}
 */
export async function measureWindow({ renderer, scene, camera, durationMs, goalFrameMs, guard = { reason: null }, beforeFrame = () => null }) {
    const gpu = createGpuTimer(renderer);
    const intervals = [];
    const work = [];
    const physics = [];
    let frames = 0;
    let truncated = false;
    let previous = null;
    let start = null;

    for (;;) {
        const timestamp = await nextFrame();
        if (guard.reason) throw new InvalidRun(guard.reason);
        start ??= timestamp;
        if (timestamp - start >= durationMs) break;

        if (previous !== null) {
            if (intervals.length < MAX_SAMPLES) intervals.push(timestamp - previous);
            else truncated = true;
        }
        previous = timestamp;

        const physicsMs = beforeFrame(timestamp - start);
        if (typeof physicsMs === 'number' && physics.length < MAX_SAMPLES) physics.push(physicsMs);
        gpu?.poll();
        const recordIndex = frames < MAX_SAMPLES ? frames : -1;
        if (recordIndex >= 0) gpu?.begin(recordIndex);
        const before = performance.now();
        renderer.render(scene, camera);
        const after = performance.now();
        if (recordIndex >= 0) gpu?.end();
        if (work.length < MAX_SAMPLES) work.push(after - before);
        frames++;
    }

    // Collect outstanding GPU queries for a few frames; they arrive asynchronously.
    for (let i = 0; i < 10 && gpu?.pendingCount; i++) {
        await nextFrame();
        gpu.poll();
    }
    const gpuSamples = gpu ? gpu.samples(Math.min(frames, MAX_SAMPLES)) : null;

    return {
        summary: summarise({ intervals, work, physics, gpu: gpuSamples, goalFrameMs, renderer, frames }),
        samples: {
            interval: intervals.map((v) => round(v)),
            work: work.map((v) => round(v)),
            physics: physics.length ? physics.map((v) => round(v)) : null,
            gpu: gpuSamples?.map((v) => round(v)) ?? null,
            truncated,
        },
    };
}

/** Summary values of one window (BENCHMARK.md §3.3 table). */
export function summarise({ intervals, work, physics = [], gpu, goalFrameMs, renderer, frames }) {
    const sortedIntervals = [...intervals].sort((a, b) => a - b);
    const sortedWork = [...work].sort((a, b) => a - b);
    const sortedPhysics = [...physics].sort((a, b) => a - b);
    const gpuValues = gpu?.filter((v) => v !== null).sort((a, b) => a - b) ?? [];
    const slowest = sortedIntervals.slice(-Math.max(1, Math.ceil(sortedIntervals.length * 0.01)));
    const jankCount = intervals.filter((interval) => interval > 2 * goalFrameMs).length;
    const info = renderer.info;
    const heap = performance.memory?.usedJSHeapSize ?? null; // Chromium only: browser-reported, JS heap only

    return {
        frames,
        interval_p50_ms: round(percentile(sortedIntervals, 50)),
        interval_p95_ms: round(percentile(sortedIntervals, 95)),
        interval_p99_ms: round(percentile(sortedIntervals, 99)),
        fps_avg: intervals.length ? round(1000 / mean(intervals), 2) : null,
        fps_1pct_low: slowest.length ? round(1000 / mean(slowest), 2) : null,
        jank_count: jankCount,
        jank_pct: intervals.length ? round((jankCount / intervals.length) * 100, 3) : null,
        work_p50_ms: round(percentile(sortedWork, 50)),
        work_p95_ms: round(percentile(sortedWork, 95)),
        physics_p50_ms: sortedPhysics.length ? round(percentile(sortedPhysics, 50)) : null,
        physics_p95_ms: sortedPhysics.length ? round(percentile(sortedPhysics, 95)) : null,
        gpu_p50_ms: gpuValues.length ? round(percentile(gpuValues, 50)) : null,
        gpu_p95_ms: gpuValues.length ? round(percentile(gpuValues, 95)) : null,
        draw_calls: info.render.calls,
        rendered_triangles: info.render.triangles,
        programs: info.programs?.length ?? null,
        geometries: info.memory.geometries,
        textures_in_memory: info.memory.textures,
        js_heap_used: heap,
    };
}

/**
 * Refresh-rate estimate (BENCHMARK.md §3.3): median frame interval while rendering an empty scene.
 * @returns {Promise<number>} Hz, rounded
 */
export async function estimateRefreshRate(renderer, camera, durationMs = 1000) {
    const { Scene } = await import('three');
    const empty = new Scene();
    const intervals = [];
    let previous = null;
    const start = performance.now();
    while (performance.now() - start < durationMs) {
        const timestamp = await nextFrame();
        if (previous !== null) intervals.push(timestamp - previous);
        previous = timestamp;
        renderer.render(empty, camera);
    }
    const median = percentile([...intervals].sort((a, b) => a - b), 50);

    return median ? Math.round(1000 / median) : null;
}

/**
 * Render one physically based material once, so renderer-level caches exist before a memory baseline is taken.
 * three r186 uploads one shared DFG lookup texture the first time such a material renders; it belongs to the
 * renderer, not to any model, and would otherwise look like a leak after dispose.
 */
export async function primeRenderer(renderer, camera) {
    const THREE = await import('three');
    const scene = new THREE.Scene();
    const mesh = new THREE.Mesh(new THREE.PlaneGeometry(), new THREE.MeshStandardMaterial());
    scene.add(mesh);
    renderer.render(scene, camera);
    mesh.geometry.dispose();
    mesh.material.dispose();
}

/**
 * Warm-up: render without recording (BENCHMARK.md §4.3, §4.6). Checks the guard every frame.
 * `until` extends the warm-up until it is true (Physics: until the drop has settled, BENCHMARK.md §4.2).
 */
export async function warmUp({ renderer, scene, camera, durationMs, guard = { reason: null }, beforeFrame = () => null, until = () => true }) {
    const start = performance.now();
    while (performance.now() - start < durationMs || !until()) {
        await nextFrame();
        if (guard.reason) throw new InvalidRun(guard.reason);
        beforeFrame(performance.now() - start);
        renderer.render(scene, camera);
    }
}

/** Idle pause between measurements (BENCHMARK.md §4.1 step 4). Checks the guard every 100 ms. */
export async function coolDown(durationMs, guard = { reason: null }) {
    const end = performance.now() + durationMs;
    while (performance.now() < end) {
        if (guard.reason) throw new InvalidRun(guard.reason);
        await new Promise((resolve) => setTimeout(resolve, Math.min(100, end - performance.now())));
    }
    if (guard.reason) throw new InvalidRun(guard.reason);
}
