// Stress Test (BENCHMARK.md §4.3): N instances → 1 scene, at fixed steps. One renderer and one scene for the
// whole run. Each repetition loads the assets once (cold), then measures every step in ascending order.
// Instance mode `clone`: object.clone() shares geometry and materials; one mesh per instance.
import { Box3, Group, Vector3 } from 'three';
import { createViewer, frameBox } from '../viewer.js';
import { COLD_CACHE, coldWarmUrl, disposeObject, loadModel, uploadToGpu } from '../loaders.js';
import { coolDown, estimateRefreshRate, measureWindow, primeRenderer, warmUp } from '../measure.js';

/** Deterministic square grid on the XZ plane, centred on the origin. */
export function gridPositions(count, spacing) {
    const columns = Math.ceil(Math.sqrt(count));
    const rows = Math.ceil(count / columns);
    const offsetX = ((columns - 1) * spacing) / 2;
    const offsetZ = ((rows - 1) * spacing) / 2;

    return Array.from({ length: count }, (_, i) => new Vector3((i % columns) * spacing - offsetX, 0, Math.floor(i / columns) * spacing - offsetZ));
}

/** Instances assigned round-robin in selection order (mixed), or all from one asset (repeated). */
function buildInstances(sources, count, spacing) {
    const group = new Group();
    gridPositions(count, spacing).forEach((position, i) => {
        const source = sources[i % sources.length];
        const instance = source.object.clone(); // shares geometry and materials
        instance.position.add(position).sub(source.centre);
        group.add(instance);
    });

    return group;
}

export async function runStress(config, { stage, guard, progress }) {
    const c = config.configuration;
    const steps = [...c.steps].sort((a, b) => a - b);
    const repetitions = c.repetitions;
    const warmupMs = c.warmup_s * 1000;
    const windowMs = c.measurement_s * 1000;
    const cooldownMs = ['phone', 'tablet'].includes(config.device_category) ? 10000 : 3000;
    const p95Item = config.budget.items?.max_p95_frame_ms;
    const mixed = config.scenario === 'mixed';
    const assets = mixed ? config.assets : config.assets.slice(0, 1);
    const total = repetitions * steps.length;
    const results = [];
    let done = 0;

    // One renderer and one scene for every step and repetition (acceptance: verified by the engine).
    const viewer = createViewer(stage, { mode: 'benchmark', resolution: c.resolution?.values, camera: c.camera?.values, lighting: c.lighting?.values, background: c.background?.values });
    guard.watchCanvas(viewer.renderer.domElement);
    const engine = { renderers_created: 1, scene_uuid: viewer.scene.uuid, scenes_used: new Set() };
    let refreshHz = null;
    let budgetFrameMs = p95Item?.enabled && p95Item.limit ? p95Item.limit : null;

    try {
        await primeRenderer(viewer.renderer, viewer.camera);
        refreshHz = await estimateRefreshRate(viewer.renderer, viewer.camera);
        budgetFrameMs ??= 1000 / refreshHz;
        if (refreshHz && refreshHz < (1000 / budgetFrameMs) * 0.95) {
            progress(`Warning: the display runs at about ${refreshHz} Hz, below the budget frame rate. Results will show it.`, { done, total });
            await coolDown(3000, guard);
        }

        for (let repetition = 1; repetition <= repetitions; repetition++) {
            progress(`Repetition ${repetition} of ${repetitions} · loading ${assets.length} ${assets.length === 1 ? 'asset' : 'assets'}`, { done, total });

            // Load once per repetition (cold), then reuse for every step.
            const sources = [];
            for (const asset of assets) {
                const { gltf } = await loadModel(coldWarmUrl(asset.url), { cache: COLD_CACHE });
                const box = new Box3().setFromObject(gltf.scene);
                sources.push({ asset, object: gltf.scene, centre: box.getCenter(new Vector3()).setY(box.min.y), diameter: box.getSize(new Vector3()).length() });
            }
            // Spacing = 1.2 × the largest bounding-box diameter; camera framed on the largest step's grid, then fixed.
            const spacing = 1.2 * Math.max(...sources.map((s) => s.diameter));
            const largest = gridPositions(steps.at(-1), spacing);
            const gridBox = new Box3().setFromPoints(largest).expandByScalar(spacing / 2);
            frameBox(viewer.camera, gridBox, c.camera?.values);

            for (const step of steps) {
                progress(`Repetition ${repetition} of ${repetitions} · ${step} ${step === 1 ? 'instance' : 'instances'}`, { done, total });
                const group = buildInstances(sources, step, spacing);
                viewer.scene.add(group);
                engine.scenes_used.add(viewer.scene.uuid);
                await uploadToGpu(viewer.renderer, viewer.scene, viewer.camera);

                await warmUp({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, durationMs: warmupMs, guard });
                const measured = await measureWindow({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, durationMs: windowMs, budgetFrameMs, guard });
                results.push({
                    asset_id: mixed ? null : assets[0].id,
                    step_instances: step,
                    repetition,
                    metrics: { ...measured.summary, refresh_hz_estimate: refreshHz },
                    samples: measured.samples,
                });

                viewer.scene.remove(group); // clones share geometry/materials: nothing to dispose per step
                done++;
            }

            sources.forEach((source) => disposeObject(source.object));
            if (repetition < repetitions) await coolDown(cooldownMs, guard);
        }
    } finally {
        guard.unwatchCanvas(viewer.renderer.domElement);
        viewer.dispose();
    }

    return {
        results,
        environment: {
            refresh_hz_estimate: refreshHz,
            budget_frame_ms: budgetFrameMs,
            instance_mode: 'clone',
            steps,
            scenario: config.scenario,
            renderers_created: engine.renderers_created,
            scenes_used: engine.scenes_used.size,
            resolution: c.resolution?.values ?? { width: 1280, height: 720 },
            cooldown_ms: cooldownMs,
            three_revision: (await import('three')).REVISION,
        },
    };
}
