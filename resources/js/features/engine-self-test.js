// Local-only engine self-test (PROJECT.md M4.1, motion scenarios M4.2): one fixture, cold + warm load, then a
// warm-up and a measurement window in each selected motion scenario (Static, Spin, Physics). The smoothness label
// uses the frame rate of the selected Target. Nothing is sent to the server.
import * as THREE from 'three';
import { createViewer, tokenColor } from '../three/viewer.js';
import { COLD_CACHE, coldWarmUrl, timedLoad, WARM_CACHE } from '../three/loaders.js';
import { createRunGuard, estimateRefreshRate, InvalidRun, measureWindow, primeRenderer, warmUp } from '../three/measure.js';
import { collectContext, suggestDeviceClass } from '../three/environment.js';
import { buildHull, centredPivot, createMotion, MOTIONS, PHYSICS, scaleUnit } from '../three/motion.js';
import { goalFrameMs, LABELS, smoothnessLabel } from '../three/smoothness.js';

const PHYSICS_SEED = 20261008;

export async function runSelfTest(root, { url, classes, motions, targetFps, durationMs = 5000, warmupMs = 1000 }) {
    const stage = root.querySelector('[data-engine-stage]');
    stage.replaceChildren();
    document.documentElement.dataset.benchmark = 'running';

    const viewer = createViewer(stage, { mode: 'benchmark', resolution: { width: 1280, height: 720 } });
    const guard = createRunGuard(viewer.renderer.domElement);
    const result = { status: 'completed', reason: null, targetFps, motions: {} };
    const goal = goalFrameMs(targetFps);

    try {
        const context = await collectContext({ three_revision: THREE.REVISION });
        result.context = context;
        result.suggestion = suggestDeviceClass(context, classes);
        result.refresh_hz_estimate = await estimateRefreshRate(viewer.renderer, viewer.camera);

        await primeRenderer(viewer.renderer, viewer.camera);
        const baseline = { ...viewer.renderer.info.memory };
        const runUrl = coldWarmUrl(url);
        result.cold = await timedLoad(viewer, runUrl, COLD_CACHE);
        viewer.clear();
        result.memoryAfterDispose = { ...viewer.renderer.info.memory };
        result.memoryBaseline = baseline;
        result.memoryBackToBaseline = baseline.geometries === result.memoryAfterDispose.geometries && baseline.textures === result.memoryAfterDispose.textures;
        result.warm = await timedLoad(viewer, runUrl, WARM_CACHE);

        // One pivot around the model's centre; the group holds it and, for Physics, the box lines.
        const model = viewer.model;
        viewer.setModel(null);
        const pivot = centredPivot(model);
        const group = new THREE.Group();
        group.add(pivot);
        const diameter = scaleUnit([pivot]);

        // No DOM changes during a window (AGENT.md §7): count every mutation on the page.
        let mutations = 0;
        const observer = new MutationObserver((records) => (mutations += records.length));

        for (const kind of MOTIONS.filter((m) => motions.includes(m))) {
            let hulls = null;
            if (kind === 'physics') {
                const hull = buildHull(pivot);
                hulls = new Map([[pivot, hull]]);
                result.hull = { points: hull.vertices.length, faces: hull.faces.length, collider_build_ms: hull.buildMs };
            }
            const motion = createMotion(kind, { group, pivots: [pivot], diameter, hulls, layout: 'single', seed: PHYSICS_SEED, lineColor: tokenColor('neutral-800') });
            viewer.setModel(group); // auto-frame once (with the box for Physics), then the camera stays fixed

            const frameHook = (elapsedMs) => motion.update(elapsedMs);
            await warmUp({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, guard, beforeFrame: frameHook,
                durationMs: kind === 'physics' ? Math.max(warmupMs, 2000) : warmupMs, until: () => motion.ready });

            observer.observe(document.documentElement, { subtree: true, childList: true, attributes: true, characterData: true });
            const measured = await measureWindow({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, durationMs, goalFrameMs: goal, guard, beforeFrame: frameHook });
            observer.takeRecords().forEach(() => mutations++);
            observer.disconnect();

            const label = smoothnessLabel(measured.summary.interval_p95_ms, measured.summary.jank_pct, targetFps);
            result.motions[kind] = { summary: measured.summary, samples: measured.samples, label };

            if (kind === 'physics') result.determinism = replayMatches(pivot, diameter, motion);
            motion.dispose();
            pivot.position.set(0, 0, 0);
            pivot.quaternion.identity();
        }
        result.domMutationsDuringWindows = mutations;
    } catch (error) {
        result.status = error instanceof InvalidRun ? 'invalid' : 'error';
        result.reason = error.message;
    } finally {
        guard.dispose();
        delete document.documentElement.dataset.benchmark;
    }

    return { result, viewer };
}

/**
 * Determinism check (PROJECT.md M4.2): replay the same number of physics steps without rendering, with a fresh
 * copy of the model and the same seed, and compare every body position and rotation.
 */
function replayMatches(pivot, diameter, measuredMotion) {
    const copy = pivot.clone();
    copy.position.set(0, 0, 0);
    copy.quaternion.identity();
    const group = new THREE.Group();
    group.add(copy);
    const replay = createMotion('physics', { group, pivots: [copy], diameter, hulls: new Map([[copy, buildHull(copy)]]), layout: 'single', seed: PHYSICS_SEED });
    while (replay.step < measuredMotion.step) replay.update();
    const same = JSON.stringify(replay.state()) === JSON.stringify(measuredMotion.state());
    replay.dispose();

    return { steps: measuredMotion.step, same };
}

export function initEngineSelfTest(root) {
    const config = JSON.parse(root.querySelector('[data-engine-config]').textContent);
    const button = root.querySelector('[data-engine-start]');
    const output = root.querySelector('[data-engine-output]');
    const status = root.querySelector('[data-engine-status]');
    let viewer = null;

    button.addEventListener('click', async () => {
        const motions = [...root.querySelectorAll('[data-engine-motion]:checked')].map((input) => input.value);
        // Read the field at click time: the Target step may initialise before this module loads.
        const targetFps = Number(root.querySelector('#target-target_fps')?.value);
        if (motions.length === 0 || !(targetFps > 0)) {
            status.textContent = motions.length === 0 ? 'Select at least one motion scenario.' : 'Enter the Target frame rate.';

            return;
        }

        button.disabled = true;
        viewer?.dispose();
        status.textContent = `Running: refresh-rate estimate, cold and warm load, then ${motions.join(', ')} (warm-up and 5 s each). Keep this tab visible.`;
        output.hidden = true;

        const run = await runSelfTest(root, { ...config, motions, targetFps });
        viewer = run.viewer;
        window.engineSelfTestResult = run.result; // for automated checks
        render(root, run.result);
        status.textContent = run.result.status === 'completed' ? 'Completed.' : `Run ${run.result.status}: ${run.result.reason}`;
        output.hidden = false;
        button.disabled = false;

        const last = Object.values(run.result.motions).at(-1);
        if (last?.samples) {
            const { drawSamplesChart } = await import('./sample-chart.js');
            drawSamplesChart(root.querySelector('[data-engine-chart]'), last.samples);
        }
    });
}

const SUMMARY_KEYS = ['frames', 'interval_p50_ms', 'interval_p95_ms', 'fps_avg', 'jank_pct', 'work_p95_ms', 'physics_p95_ms', 'gpu_p95_ms', 'draw_calls', 'rendered_triangles'];

function render(root, result) {
    const fill = (selector, rows) => {
        const body = root.querySelector(selector);
        body.replaceChildren(...rows.map(([key, value]) => {
            const tr = document.createElement('tr');
            const th = document.createElement('th');
            th.scope = 'row';
            th.textContent = key;
            const td = document.createElement('td');
            if (typeof value === 'object' || String(value).length > 24) td.className = 'cell-wrap break-all';
            else td.dataset.numeric = '';
            td.textContent = value === null || value === undefined ? 'null' : typeof value === 'object' ? JSON.stringify(value) : String(typeof value === 'number' ? Math.round(value * 1000) / 1000 : value);
            tr.append(th, td);

            return tr;
        }));
    };

    const motions = Object.entries(result.motions ?? {});
    fill('[data-engine-summary]', [
        ['target_fps', result.targetFps],
        ...motions.map(([kind, m]) => [`${kind}: label`, m.label ? `${LABELS[m.label]} (${fmt(1000 / m.summary.interval_p95_ms)} fps at p95)` : 'null']),
        ...motions.flatMap(([kind, m]) => SUMMARY_KEYS.map((key) => [`${kind}: ${key}`, m.summary[key]])),
        ['refresh_hz_estimate', result.refresh_hz_estimate],
        ['dom_mutations_during_windows', result.domMutationsDuringWindows],
        ['physics hull', result.hull ?? null],
        ['physics determinism', result.determinism ?? null],
        ['physics settle steps', PHYSICS.settleSteps],
    ]);
    fill('[data-engine-load]', ['transfer_size', 'fetch_ms', 'parse_ms', 'gpu_upload_ms', 'first_frame_ms', 'ttff_ms'].map((key) => [key, `cold ${fmt(result.cold?.[key])} · warm ${fmt(result.warm?.[key])}`]));
    fill('[data-engine-context]', Object.entries(result.context ?? {}).concat([['suggestion', result.suggestion?.summary], ['memory baseline', result.memoryBaseline], ['memory after dispose', result.memoryAfterDispose], ['memory back to baseline', result.memoryBackToBaseline]]));
}

const fmt = (value) => (value === null || value === undefined ? 'null' : typeof value === 'number' ? (Math.round(value * 100) / 100).toString() : String(value));
