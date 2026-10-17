// Side-by-Side Test, measure phase (BENCHMARK.md §4.3). For each repetition, for each asset in a seeded shuffled
// order: the other panes keep their last frame; a fresh renderer and scene in the measured pane; cold load, dispose,
// warm load; then for each selected motion scenario a warm-up and a measurement window; dispose; cool-down.
//
// The pane DOM changes only between windows (marking, frozen images, progress). A mutation observer counts any DOM
// change during a window; the count is recorded in the run environment.
import * as THREE from 'three';
import { createViewer, tokenColor } from '../viewer.js';
import { COLD_CACHE, coldWarmUrl, timedLoad, WARM_CACHE } from '../loaders.js';
import { coolDown, estimateRefreshRate, measureWindow, primeRenderer, warmUp } from '../measure.js';
import { buildHull, centredPivot, createMotion, PHYSICS, PHYSICS_SEED, scaleUnit } from '../motion.js';
import { seededRandom, shuffle } from '../random.js';
import { goalFrameMs } from '../smoothness.js';

const LOAD_KEYS = ['transfer_size', 'fetch_ms', 'parse_ms', 'gpu_upload_ms', 'first_frame_ms', 'ttff_ms'];
const pick = (load) => Object.fromEntries(LOAD_KEYS.map((key) => [key, load[key] ?? null]));
const MOTION_LABELS = { static: 'Static', spin: 'Spin', physics: 'Physics' };

/**
 * @param {object} config run config from pages/lab/run (configuration snapshot, target, assets, cooldown_s)
 * @param {{ panes: Map<number, { element: HTMLElement, stage: HTMLElement }>, guard: object,
 *           progress: (text: string, step?: {done: number, total: number}) => void }} ui
 * @returns {Promise<{ results: object[], environment: object }>}
 */
export async function runSideBySide(config, { panes, guard, progress }) {
    const c = config.configuration;
    const motions = c.motions;
    const warmupMs = c.warmup_s * 1000;
    const windowMs = c.measurement_s * 1000;
    const cooldownMs = config.cooldown_s * 1000;
    const goal = goalFrameMs(config.target.target_fps);
    const viewerConfig = { mode: 'benchmark', resolution: c.resolution?.values, camera: c.camera?.values, lighting: c.lighting?.values, background: c.background?.values };
    const seed = crypto.getRandomValues(new Uint32Array(1))[0];
    const random = seededRandom(seed);
    const orders = [];
    const results = [];
    let refreshHz = null;
    let mutations = 0;
    let maxCanvases = 0;
    const observer = new MutationObserver((records) => (mutations += records.length));
    const total = c.repetitions * config.assets.length * motions.length;
    let done = 0;
    const secondsLeft = () => Math.round(((total - done) * (warmupMs + windowMs) + (total / motions.length) * cooldownMs) / 1000);

    const markMeasuring = (assetId) => panes.forEach((pane, id) => pane.element.toggleAttribute('data-measuring', id === assetId));

    for (let repetition = 1; repetition <= c.repetitions; repetition++) {
        const order = shuffle(config.assets, random);
        orders.push(order.map((asset) => asset.id));

        for (const [position, asset] of order.entries()) {
            const pane = panes.get(asset.id);
            // Mark the measured pane before its windows start; replace its frozen image with a fresh renderer.
            markMeasuring(asset.id);
            pane.stage.replaceChildren();
            const viewer = createViewer(pane.stage, viewerConfig);
            guard.watchCanvas(viewer.renderer.domElement);
            let snapshot = null;

            try {
                progress(`Repetition ${repetition} of ${c.repetitions} · Asset ${position + 1} of ${order.length}: ${asset.name} · loading`, { done, total });
                await primeRenderer(viewer.renderer, viewer.camera);
                if (refreshHz === null) refreshHz = await estimateRefreshRate(viewer.renderer, viewer.camera);

                const baseline = { ...viewer.renderer.info.memory };
                const url = coldWarmUrl(asset.url);
                const cold = await timedLoad(viewer, url, COLD_CACHE);
                viewer.clear();
                const afterDispose = viewer.renderer.info.memory;
                const memoryClean = afterDispose.geometries === baseline.geometries && afterDispose.textures === baseline.textures;
                const warm = await timedLoad(viewer, url, WARM_CACHE);

                // One pivot around the model's centre; the group holds it and, for Physics, the box lines.
                const model = viewer.model;
                viewer.setModel(null);
                const pivot = centredPivot(model);
                const group = new THREE.Group();
                group.add(pivot);
                const diameter = scaleUnit([pivot]);
                let hull = null;
                if (motions.includes('physics')) hull = buildHull(pivot);

                let mixer = null;
                if (c.animation && warm.animations?.length) {
                    mixer = new THREE.AnimationMixer(model);
                    mixer.clipAction(warm.animations[0]).play();
                }

                results.push({
                    asset_id: asset.id,
                    motion: null,
                    repetition,
                    load: { cold: pick(cold), warm: pick(warm) },
                    collider: hull ? { points: hull.vertices.length, faces: hull.faces.length, collider_build_ms: Math.round(hull.buildMs * 1000) / 1000 } : null,
                    memory_clean: memoryClean,
                });

                for (const kind of motions) {
                    progress(`Repetition ${repetition} of ${c.repetitions} · Asset ${position + 1} of ${order.length}: ${asset.name} · ${MOTION_LABELS[kind]} · ${Math.ceil(secondsLeft() / 60)} min left`, { done, total });
                    const motion = createMotion(kind, { group, pivots: [pivot], diameter, hulls: hull ? new Map([[pivot, hull]]) : null, layout: 'single', seed: PHYSICS_SEED, lineColor: tokenColor('neutral-800') });
                    viewer.setModel(group); // auto-frame at the start of each measurement, then fixed (BENCHMARK.md §2)

                    let last = 0;
                    const frameHook = (elapsed) => {
                        mixer?.update((elapsed - last) / 1000);
                        last = elapsed;

                        return motion.update(elapsed);
                    };
                    await warmUp({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, guard, beforeFrame: frameHook,
                        durationMs: kind === 'physics' ? Math.max(warmupMs, 2000) : warmupMs, until: () => motion.ready });
                    last = 0;

                    maxCanvases = Math.max(maxCanvases, document.querySelectorAll('[data-pane] canvas').length);
                    observer.observe(document.documentElement, { subtree: true, childList: true, attributes: true, characterData: true });
                    const measured = await measureWindow({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, durationMs: windowMs, goalFrameMs: goal, guard, beforeFrame: frameHook });
                    mutations += observer.takeRecords().length;
                    observer.disconnect();

                    results.push({ asset_id: asset.id, motion: kind, repetition, metrics: measured.summary, samples: measured.samples });
                    done++;

                    // Keep the last frame of the last scenario for the frozen pane: read it right after a render.
                    if (kind === motions.at(-1)) {
                        viewer.render();
                        snapshot = viewer.renderer.domElement.toDataURL('image/jpeg', 0.85);
                    }
                    motion.dispose();
                    pivot.position.set(0, 0, 0);
                    pivot.quaternion.identity();
                }
                mixer?.stopAllAction();
            } finally {
                guard.unwatchCanvas(viewer.renderer.domElement);
                viewer.dispose();
                pane.stage.replaceChildren();
                if (snapshot) {
                    const image = new Image();
                    image.src = snapshot;
                    image.alt = `${asset.name}: last measured frame`;
                    pane.stage.append(image);
                }
                markMeasuring(null);
            }

            const last = repetition === c.repetitions && position === order.length - 1;
            if (!last) {
                progress(`Cool-down ${config.cooldown_s} s`, { done, total });
                await coolDown(cooldownMs, guard);
            }
        }
    }

    return {
        results: results.map(({ memory_clean, ...result }) => result),
        environment: {
            refresh_hz_estimate: refreshHz,
            goal_frame_ms: goal,
            shuffle_seed: seed,
            orders,
            physics_seed: PHYSICS_SEED,
            physics_settle_steps: PHYSICS.settleSteps,
            animation: c.animation ? 'on' : 'off',
            resolution: viewerConfig.resolution ?? { width: 1280, height: 720 },
            cooldown_ms: cooldownMs,
            three_revision: THREE.REVISION,
            memory_clean_after_dispose: results.filter((r) => r.motion === null).every((r) => r.memory_clean),
            dom_mutations_during_windows: mutations,
            max_canvases_during_windows: maxCanvases,
        },
    };
}
