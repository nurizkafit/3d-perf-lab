// Headroom Test (BENCHMARK.md §4.4): instances increase until the frame budget fails.
// One renderer and one scene for the run. Repeated: each selected asset gets its own search;
// mixed: one search for the set (round-robin). Placement and instance mode as the Stress Test;
// the camera frames the grid at the cap count and stays fixed. Each probe: 1 s warm-up, 4 s window.
import { Box3, Vector3 } from 'three';
import { createViewer, frameBox } from '../viewer.js';
import { COLD_CACHE, coldWarmUrl, disposeObject, loadModel, uploadToGpu } from '../loaders.js';
import { coolDown, estimateRefreshRate, measureWindow, primeRenderer, warmUp } from '../measure.js';
import { buildInstances, gridPositions } from './stress.js';
import { probePasses, searchHeadroom } from './headroom-search.js';

export async function runHeadroom(config, { stage, guard, progress }) {
    const c = config.configuration;
    const cap = c.cap ?? 1024;
    const repetitions = c.repetitions;
    const cooldownMs = ['phone', 'tablet'].includes(config.device_category) ? 10000 : 3000;
    const budget = {
        max_p95_frame_ms: config.budget.items.max_p95_frame_ms.limit,
        max_jank_pct: config.budget.items.max_jank_pct.limit,
    };
    const mixed = config.scenario === 'mixed';
    const searches = mixed ? [{ assets: config.assets, assetId: null, label: 'Mixed set' }] : config.assets.map((asset) => ({ assets: [asset], assetId: asset.id, label: asset.name }));
    const total = repetitions * searches.length;
    const results = [];
    let done = 0;

    const viewer = createViewer(stage, { mode: 'benchmark', resolution: c.resolution?.values, camera: c.camera?.values, lighting: c.lighting?.values, background: c.background?.values });
    guard.watchCanvas(viewer.renderer.domElement);
    let refreshHz = null;

    try {
        await primeRenderer(viewer.renderer, viewer.camera);
        refreshHz = await estimateRefreshRate(viewer.renderer, viewer.camera);
        if (refreshHz && refreshHz < (1000 / budget.max_p95_frame_ms) * 0.95) {
            progress(`Warning: the display runs at about ${refreshHz} Hz, below the budget frame rate. Results will show it.`, { done, total });
            await coolDown(3000, guard);
        }

        for (let repetition = 1; repetition <= repetitions; repetition++) {
            for (const search of searches) {
                progress(`Repetition ${repetition} of ${repetitions} · ${search.label} · loading`, { done, total });
                const sources = [];
                for (const asset of search.assets) {
                    const { gltf } = await loadModel(coldWarmUrl(asset.url), { cache: COLD_CACHE });
                    const box = new Box3().setFromObject(gltf.scene);
                    sources.push({ asset, object: gltf.scene, centre: box.getCenter(new Vector3()).setY(box.min.y), diameter: box.getSize(new Vector3()).length() });
                }
                const spacing = 1.2 * Math.max(...sources.map((s) => s.diameter));
                frameBox(viewer.camera, new Box3().setFromPoints(gridPositions(cap, spacing)).expandByScalar(spacing / 2), c.camera?.values);

                let lastSummary = null;
                const probe = async (instances) => {
                    progress(`Repetition ${repetition} of ${repetitions} · ${search.label} · probing ${instances} ${instances === 1 ? 'instance' : 'instances'}`, { done, total });
                    const group = buildInstances(sources, instances, spacing);
                    viewer.scene.add(group);
                    try {
                        await uploadToGpu(viewer.renderer, viewer.scene, viewer.camera);
                        await warmUp({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, durationMs: (c.probe_warmup_s ?? 1) * 1000, guard });
                        const { summary } = await measureWindow({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, durationMs: (c.probe_window_s ?? 4) * 1000, budgetFrameMs: budget.max_p95_frame_ms, guard });
                        const pass = probePasses(summary, budget);
                        if (pass || lastSummary === null) lastSummary = summary; // summary of the last passing probe (or of the first probe)

                        return { pass, interval_p95_ms: summary.interval_p95_ms, jank_pct: summary.jank_pct };
                    } finally {
                        viewer.scene.remove(group);
                    }
                };

                const outcome = await searchHeadroom({ probe, cap, timeLimitMs: (c.time_limit_s ?? 180) * 1000 });
                results.push({
                    asset_id: search.assetId,
                    repetition,
                    metrics: { ...lastSummary, headroom_instances: outcome.headroom_instances, refresh_hz_estimate: refreshHz },
                    probes: outcome.probes,
                    flags: { cap_reached: outcome.cap_reached, time_limit_reached: outcome.time_limit_reached },
                });

                sources.forEach((source) => disposeObject(source.object));
                done++;
                if (done < total) await coolDown(cooldownMs, guard);
            }
        }
    } finally {
        guard.unwatchCanvas(viewer.renderer.domElement);
        viewer.dispose();
    }

    return {
        results,
        environment: {
            refresh_hz_estimate: refreshHz,
            instance_mode: 'clone',
            cap,
            scenario: config.scenario,
            renderers_created: 1,
            resolution: c.resolution?.values ?? { width: 1280, height: 720 },
            cooldown_ms: cooldownMs,
            three_revision: (await import('three')).REVISION,
        },
    };
}
