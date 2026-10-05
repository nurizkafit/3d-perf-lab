// Comparison Test (BENCHMARK.md §4.2): N assets → N independent viewers, run one at a time.
// For each repetition, for each asset in a seeded shuffled order: fresh renderer and scene, cold load,
// dispose, warm load, warm-up, measurement window, dispose renderer, cool-down.
import { AnimationMixer, REVISION } from 'three';
import { createViewer } from '../viewer.js';
import { COLD_CACHE, coldWarmUrl, timedLoad, WARM_CACHE } from '../loaders.js';
import { coolDown, estimateRefreshRate, measureWindow, primeRenderer, warmUp } from '../measure.js';

/** Small seeded generator (mulberry32), so the order can be reproduced from the recorded seed. */
export function seededRandom(seed) {
    let state = seed >>> 0;

    return () => {
        state = (state + 0x6d2b79f5) >>> 0;
        let t = state;
        t = Math.imul(t ^ (t >>> 15), t | 1);
        t ^= t + Math.imul(t ^ (t >>> 7), t | 61);

        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

export function shuffle(items, random) {
    const copy = [...items];
    for (let i = copy.length - 1; i > 0; i--) {
        const j = Math.floor(random() * (i + 1));
        [copy[i], copy[j]] = [copy[j], copy[i]];
    }

    return copy;
}

const LOAD_KEYS = ['transfer_size', 'fetch_ms', 'parse_ms', 'gpu_upload_ms', 'first_frame_ms', 'ttff_ms'];
const pick = (load) => Object.fromEntries(LOAD_KEYS.map((key) => [key, load[key] ?? null]));

/**
 * @param {object} config run config from pages/lab/run (configuration snapshot, budget, assets, device_category)
 * @param {{ stage: HTMLElement, guard: object, progress: (text: string) => void }} ui
 * @returns {Promise<{ results: object[], environment: object }>}
 */
export async function runComparison(config, { stage, guard, progress }) {
    const c = config.configuration;
    const repetitions = c.repetitions;
    const warmupMs = c.warmup_s * 1000;
    const windowMs = c.measurement_s * 1000;
    const cooldownMs = ['phone', 'tablet'].includes(config.device_category) ? 10000 : 3000;
    const viewerConfig = {
        mode: 'benchmark',
        resolution: c.resolution?.values,
        camera: c.camera?.values,
        lighting: c.lighting?.values,
        background: c.background?.values,
    };
    const p95Item = config.budget.items?.max_p95_frame_ms;
    const seed = crypto.getRandomValues(new Uint32Array(1))[0];
    const random = seededRandom(seed);
    const orders = [];
    const results = [];
    let refreshHz = null;
    let budgetFrameMs = p95Item?.enabled && p95Item.limit ? p95Item.limit : null;
    const total = repetitions * config.assets.length;
    let done = 0;
    const perAssetMs = warmupMs + windowMs + cooldownMs + 2000;

    for (let repetition = 1; repetition <= repetitions; repetition++) {
        const order = shuffle(config.assets, random);
        orders.push(order.map((asset) => asset.id));

        for (const [position, asset] of order.entries()) {
            const remaining = Math.round(((total - done) * perAssetMs) / 1000);
            progress(`Repetition ${repetition} of ${repetitions} · Asset ${position + 1} of ${order.length}: ${asset.name} · about ${Math.ceil(remaining / 60)} min left`, { done, total });

            const viewer = createViewer(stage, viewerConfig);
            guard.watchCanvas(viewer.renderer.domElement);
            try {
                await primeRenderer(viewer.renderer, viewer.camera);
                if (refreshHz === null) {
                    refreshHz = await estimateRefreshRate(viewer.renderer, viewer.camera);
                    budgetFrameMs ??= 1000 / refreshHz;
                    if (refreshHz && refreshHz < (1000 / budgetFrameMs) * 0.95) {
                        progress(`Warning: the display runs at about ${refreshHz} Hz, below the budget frame rate. Results will show it.`);
                        await coolDown(3000, guard);
                    }
                }

                const baseline = { ...viewer.renderer.info.memory };
                const url = coldWarmUrl(asset.url);
                const cold = await timedLoad(viewer, url, COLD_CACHE);
                viewer.clear();
                const afterDispose = viewer.renderer.info.memory;
                const memoryClean = afterDispose.geometries === baseline.geometries && afterDispose.textures === baseline.textures;
                const warm = await timedLoad(viewer, url, WARM_CACHE);

                let mixer = null;
                if (c.animation && warm.animations?.length) {
                    mixer = new AnimationMixer(viewer.model);
                    mixer.clipAction(warm.animations[0]).play();
                }
                let last = 0;
                const beforeFrame = (elapsed) => {
                    mixer?.update((elapsed - last) / 1000);
                    last = elapsed;
                };

                await warmUp({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, durationMs: warmupMs, guard, beforeFrame });
                last = 0;
                const measured = await measureWindow({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, durationMs: windowMs, budgetFrameMs, guard, beforeFrame });

                results.push({
                    asset_id: asset.id,
                    repetition,
                    metrics: { ...measured.summary, refresh_hz_estimate: refreshHz },
                    load: { cold: pick(cold), warm: pick(warm), memory_clean_after_dispose: memoryClean },
                    samples: measured.samples,
                    animation: mixer ? 'on' : 'off',
                });
            } finally {
                guard.unwatchCanvas(viewer.renderer.domElement);
                viewer.dispose();
            }

            done++;
            if (done < total) await coolDown(cooldownMs, guard);
        }
    }

    return {
        results: results.map(({ animation, ...result }) => result),
        environment: {
            refresh_hz_estimate: refreshHz,
            budget_frame_ms: budgetFrameMs,
            shuffle_seed: seed,
            orders,
            animation: results.some((r) => r.animation === 'on') ? 'on' : 'off',
            resolution: viewerConfig.resolution ?? { width: 1280, height: 720 },
            cooldown_ms: cooldownMs,
            three_revision: REVISION,
        },
    };
}
