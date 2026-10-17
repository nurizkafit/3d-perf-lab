// Crowd Test, measure phase (BENCHMARK.md §4.6). For each repetition, for each group (asset, or tool set) in a seeded
// shuffled order, for each selected motion scenario: a visible search. Probe at 1 copy, double until a probe is not
// Smooth or the cap is reached, then binary search until the gap ≤ max(1, 5% of the last Smooth count). Each probe:
// build the crowd, auto-frame it, warm-up 1 s (Physics 2 s and settled), 4 s window. A search stops after 3 minutes.
//
// The canvas shows the crowd during the whole search. The progress strip changes only between probes.
import * as THREE from 'three';
import { createViewer, tokenColor } from '../viewer.js';
import { disposeObject } from '../loaders.js';
import { coolDown, estimateRefreshRate, measureWindow, primeRenderer, warmUp } from '../measure.js';
import { PHYSICS, PHYSICS_SEED } from '../motion.js';
import { seededRandom, shuffle } from '../random.js';
import { goalFrameMs, LABELS, smoothnessLabel } from '../smoothness.js';
import { createCrowd, ensureHulls, loadTemplates } from '../crowd.js';

const MOTION_LABELS = { static: 'Static', spin: 'Spin', physics: 'Physics' };

/**
 * The search procedure, independent of rendering (unit-tested in tests/js/crowd-search.test.mjs).
 * @param {(copies: number) => Promise<boolean>} probe  measures one probe; true when Smooth
 * @param {{ capCopies: number, overTime: () => boolean }} options
 * @returns {Promise<{ smoothCopies: number, capReached: boolean, timeLimitReached: boolean }>}
 */
export async function searchCopies(probe, { capCopies, overTime }) {
    let lastSmooth = 0;
    let firstFail = null;
    let capReached = false;
    let timeLimitReached = false;
    let copies = 1;

    for (;;) {
        if (!(await probe(copies))) {
            firstFail = copies;
            break;
        }
        lastSmooth = copies;
        if (copies >= capCopies) {
            capReached = true;
            break;
        }
        if (overTime()) {
            timeLimitReached = true;
            break;
        }
        copies = Math.min(copies * 2, capCopies);
    }

    if (firstFail !== null && lastSmooth > 0) {
        while (firstFail - lastSmooth > Math.max(1, Math.floor(0.05 * lastSmooth))) {
            if (overTime()) {
                timeLimitReached = true;
                break;
            }
            const middle = Math.floor((lastSmooth + firstFail) / 2);
            if (await probe(middle)) lastSmooth = middle;
            else firstFail = middle;
        }
    }

    return { smoothCopies: lastSmooth, capReached, timeLimitReached };
}

/**
 * @param {object} config run config from pages/lab/run (configuration snapshot, target, groups, cooldown_s)
 * @param {{ stage: HTMLElement, guard: object, progress: (text: string, step?: object) => void }} ui
 */
export async function runCrowd(config, { stage, guard, progress }) {
    const c = config.configuration;
    const motions = c.motions;
    const goal = goalFrameMs(config.target.target_fps);
    const timeLimitMs = c.time_limit_s * 1000;
    const cooldownMs = config.cooldown_s * 1000;
    const viewerConfig = { mode: 'benchmark', resolution: c.resolution?.values, camera: c.camera?.values, lighting: c.lighting?.values, background: c.background?.values };
    const perTool = config.scenario === 'per_tool';
    const seed = crypto.getRandomValues(new Uint32Array(1))[0];
    const random = seededRandom(seed);
    const orders = [];
    const results = [];
    let refreshHz = null;
    let mutations = 0;
    const observer = new MutationObserver((records) => (mutations += records.length));
    const total = c.repetitions * config.groups.length * motions.length;
    let done = 0;
    let colliderMs = 0;

    for (let repetition = 1; repetition <= c.repetitions; repetition++) {
        const order = shuffle(config.groups, random);
        orders.push(order.map((group) => group.key));

        for (const [position, group] of order.entries()) {
            stage.replaceChildren();
            const viewer = createViewer(stage, viewerConfig);
            guard.watchCanvas(viewer.renderer.domElement);
            const objects = group.assets.length;
            const capCopies = Math.max(1, Math.floor(c.cap / objects));
            const groupKey = perTool ? { asset_id: null, group_tool_id: group.key } : { asset_id: group.key, group_tool_id: null };
            let loaded = null;

            try {
                progress(`Repetition ${repetition} of ${c.repetitions} · ${group.label} (${position + 1} of ${order.length}) · loading`, { done, total });
                await primeRenderer(viewer.renderer, viewer.camera);
                if (refreshHz === null) refreshHz = await estimateRefreshRate(viewer.renderer, viewer.camera);
                loaded = await loadTemplates(group.assets);
                if (motions.includes('physics')) colliderMs += ensureHulls(loaded.templates);

                for (const kind of motions) {
                    const start = performance.now();
                    const overTime = () => performance.now() - start >= timeLimitMs;
                    let probeNumber = 0;

                    const probe = async (copies) => {
                        probeNumber++;
                        const crowd = createCrowd({ templates: loaded.templates, copies, kind, diameter: loaded.diameter, lineColor: tokenColor('neutral-800') });
                        viewer.scene.add(crowd.group);
                        viewer.frame(crowd.bounds); // auto-frame the crowd of this probe, then fixed (BENCHMARK.md §2)
                        try {
                            const frameHook = (elapsed) => crowd.motion.update(elapsed);
                            const warmupMs = (kind === 'physics' ? c.physics_probe_warmup_s : c.probe_warmup_s) * 1000;
                            await warmUp({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, guard, beforeFrame: frameHook, durationMs: warmupMs, until: () => crowd.motion.ready });
                            observer.observe(document.documentElement, { subtree: true, childList: true, attributes: true, characterData: true });
                            const measured = await measureWindow({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, durationMs: c.probe_window_s * 1000, goalFrameMs: goal, guard, beforeFrame: frameHook });
                            mutations += observer.takeRecords().length;
                            observer.disconnect();
                            const label = smoothnessLabel(measured.summary.interval_p95_ms, measured.summary.jank_pct, config.target.target_fps);
                            results.push({ ...groupKey, motion: kind, repetition, probe_copies: copies, metrics: measured.summary });
                            // Between probes only: the last probe and its label.
                            progress(`Repetition ${repetition} of ${c.repetitions} · ${group.label} · ${MOTION_LABELS[kind]} · probe ${probeNumber}: ${copies} ${copies === 1 ? 'copy' : 'copies'} — ${LABELS[label] ?? 'Not measured'}`, { done, total });

                            return label === 'smooth';
                        } finally {
                            observer.disconnect();
                            viewer.scene.remove(crowd.group);
                            crowd.dispose();
                        }
                    };

                    progress(`Repetition ${repetition} of ${c.repetitions} · ${group.label} · ${MOTION_LABELS[kind]} · starting the search (cap ${capCopies} ${capCopies === 1 ? 'copy' : 'copies'})`, { done, total });
                    const outcome = await searchCopies(probe, { capCopies, overTime });
                    results.push({ ...groupKey, motion: kind, repetition, probe_copies: null, search: { cap_reached: outcome.capReached, time_limit_reached: outcome.timeLimitReached } });
                    done++;
                    progress(`${group.label} · ${MOTION_LABELS[kind]}: Smooth up to ${outcome.smoothCopies}${outcome.capReached ? ' (cap)' : ''} copies${outcome.timeLimitReached ? ' · time limit reached' : ''}`, { done, total });
                    if (done < total) await coolDown(cooldownMs, guard);
                }
            } finally {
                guard.unwatchCanvas(viewer.renderer.domElement);
                loaded?.templates.forEach((template) => disposeObject(template.pivot));
                viewer.dispose();
            }
        }
    }

    return {
        results,
        environment: {
            refresh_hz_estimate: refreshHz,
            goal_frame_ms: goal,
            shuffle_seed: seed,
            orders,
            physics_seed: PHYSICS_SEED,
            physics_settle_steps: PHYSICS.settleSteps,
            instance_mode: c.instance_mode,
            resolution: viewerConfig.resolution ?? { width: 1280, height: 720 },
            cooldown_ms: cooldownMs,
            collider_build_ms: Math.round(colliderMs * 1000) / 1000,
            three_revision: THREE.REVISION,
            dom_mutations_during_windows: mutations,
        },
    };
}
