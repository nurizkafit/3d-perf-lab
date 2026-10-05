// Local-only viewer lab (dev/viewer.blade.php): metrics, thumbnails, and the mount/unmount memory test.
import { createViewer } from '../three/viewer.js';
import { inspectModel, renderThumbnail } from '../three/inspect.js';

export function initViewerLab(root) {
    root.querySelectorAll('[data-fixture]').forEach((section) => {
        const viewerElement = section.querySelector('[data-viewer]');
        viewerElement.addEventListener('viewer:state', async ({ detail }) => {
            if (detail.state !== 'ready') return;
            const model = viewerElement.viewer.model;
            const metrics = inspectModel(model);
            const timings = viewerElement.viewer.lastTimings ?? {};
            const values = {
                ...metrics,
                texture_dimensions: metrics.texture_dimensions.map(([w, h]) => `${w}×${h}`).join(', ') || '—',
                fetch_ms: timings.fetch_ms?.toFixed(1) ?? '—',
                parse_ms: timings.parse_ms?.toFixed(1) ?? '—',
            };
            for (const [key, value] of Object.entries(values)) {
                const cell = section.querySelector(`[data-metric="${key}"]`);
                if (cell) cell.textContent = String(value);
            }
            section.dataset.metrics = JSON.stringify(metrics);

            const blob = await renderThumbnail(model);
            section.querySelector('[data-thumbnail]').src = URL.createObjectURL(blob);
            section.querySelector('[data-thumbnail-info]').textContent = `Thumbnail: ${blob.type}, ${(blob.size / 1024).toFixed(1)} KB, 512 × 512`;
        });
    });

    const button = root.querySelector('[data-memory-test]');
    button?.addEventListener('click', async () => {
        button.disabled = true;
        const output = root.querySelector('[data-memory-result]');
        output.textContent = 'Running…';
        const result = await memoryTest(root.querySelector('[data-memory-stage]'), root.querySelector('[data-viewer]')?.dataset.src);
        output.textContent = JSON.stringify(result, null, 2);
        window.viewerLabMemoryResult = result;
        button.disabled = false;
    });
}

async function memoryTest(stage, url) {
    const snapshot = (viewer) => ({ ...viewer.renderer.info.memory, programs: viewer.renderer.info.programs?.length ?? 0 });

    // 1. Mount, load, dispose — 20 times. A leak would show as "Too many active WebGL contexts" warnings.
    const mounts = [];
    for (let i = 0; i < 20; i++) {
        const viewer = createViewer(stage);
        await viewer.load(url);
        mounts.push(snapshot(viewer));
        viewer.dispose();
    }

    // 2. One viewer: empty baseline, then load + clear 20 times.
    // The first physically based material makes the renderer upload one shared DFG lookup texture
    // (three r186, getDFGLUT). It belongs to the renderer, not to the model, so the comparison uses
    // the memory after the first load + clear ("warm baseline").
    const viewer = createViewer(stage);
    await new Promise(requestAnimationFrame);
    const emptyBaseline = snapshot(viewer);
    let loaded = null;
    let warmBaseline = null;
    for (let i = 0; i < 20; i++) {
        await viewer.load(url);
        await new Promise(requestAnimationFrame);
        loaded ??= snapshot(viewer);
        viewer.clear();
        await new Promise(requestAnimationFrame);
        warmBaseline ??= snapshot(viewer);
    }
    const after = snapshot(viewer);
    viewer.dispose();

    return {
        mountUnmountCycles: mounts.length,
        emptyBaseline,
        loadedMemory: loaded,
        warmBaseline,
        afterTwentyLoadClearCycles: after,
        geometriesBackToEmpty: after.geometries === emptyBaseline.geometries,
        noGrowthOverCycles: after.geometries === warmBaseline.geometries && after.textures === warmBaseline.textures && after.programs === warmBaseline.programs,
    };
}
