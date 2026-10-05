// Local-only engine self-test (PROJECT.md M4.1): one fixture, cold + warm load, 1 s warm-up, 5 s window.
// Shows the raw summaries. Nothing is sent to the server.
import { createViewer } from '../three/viewer.js';
import { COLD_CACHE, coldWarmUrl, timedLoad, WARM_CACHE } from '../three/loaders.js';
import { createRunGuard, estimateRefreshRate, InvalidRun, measureWindow, primeRenderer, warmUp } from '../three/measure.js';
import { collectContext, suggestDeviceClass } from '../three/environment.js';

export async function runSelfTest(root, { url, classes, durationMs = 5000, warmupMs = 1000, budgetFrameMs = 16.7 }) {
    const stage = root.querySelector('[data-engine-stage]');
    stage.replaceChildren();
    document.documentElement.dataset.benchmark = 'running';

    const viewer = createViewer(stage, { mode: 'benchmark', resolution: { width: 1280, height: 720 } });
    const guard = createRunGuard(viewer.renderer.domElement);
    const result = { status: 'completed', reason: null };

    try {
        const context = await collectContext({ three_revision: (await import('three')).REVISION });
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

        await warmUp({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, durationMs: warmupMs, guard });

        // No DOM changes during the window (AGENT.md §7): count every mutation on the page.
        let mutations = 0;
        const observer = new MutationObserver((records) => (mutations += records.length));
        observer.observe(document.documentElement, { subtree: true, childList: true, attributes: true, characterData: true });
        const measured = await measureWindow({ renderer: viewer.renderer, scene: viewer.scene, camera: viewer.camera, durationMs, budgetFrameMs, guard });
        observer.takeRecords().forEach(() => mutations++);
        observer.disconnect();

        result.summary = measured.summary;
        result.samples = measured.samples;
        result.domMutationsDuringWindow = mutations;
    } catch (error) {
        result.status = error instanceof InvalidRun ? 'invalid' : 'error';
        result.reason = error.message;
    } finally {
        guard.dispose();
        delete document.documentElement.dataset.benchmark;
    }

    return { result, viewer };
}

export function initEngineSelfTest(root) {
    const config = JSON.parse(root.querySelector('[data-engine-config]').textContent);
    const button = root.querySelector('[data-engine-start]');
    const output = root.querySelector('[data-engine-output]');
    const status = root.querySelector('[data-engine-status]');
    let viewer = null;

    button.addEventListener('click', async () => {
        button.disabled = true;
        viewer?.dispose();
        status.textContent = 'Running: refresh-rate estimate, cold and warm load, 1 s warm-up, 5 s measurement. Keep this tab visible.';
        output.hidden = true;

        const run = await runSelfTest(root, config);
        viewer = run.viewer;
        window.engineSelfTestResult = run.result; // for automated checks
        render(root, run.result);
        status.textContent = run.result.status === 'completed' ? 'Completed.' : `Run ${run.result.status}: ${run.result.reason}`;
        output.hidden = false;
        button.disabled = false;

        if (run.result.samples) {
            const { drawSamplesChart } = await import('./sample-chart.js');
            drawSamplesChart(root.querySelector('[data-engine-chart]'), run.result.samples);
        }
    });
}

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
    fill('[data-engine-summary]', Object.entries(result.summary ?? {}).concat([['refresh_hz_estimate', result.refresh_hz_estimate], ['samples_truncated', result.samples?.truncated], ['dom_mutations_during_window', result.domMutationsDuringWindow]]));
    fill('[data-engine-load]', ['transfer_size', 'fetch_ms', 'parse_ms', 'gpu_upload_ms', 'first_frame_ms', 'ttff_ms'].map((key) => [key, `cold ${fmt(result.cold?.[key])} · warm ${fmt(result.warm?.[key])}`]));
    fill('[data-engine-context]', Object.entries(result.context ?? {}).concat([['suggestion', result.suggestion?.summary], ['memory baseline', result.memoryBaseline], ['memory after dispose', result.memoryAfterDispose], ['memory back to baseline', result.memoryBackToBaseline]]));
}

const fmt = (value) => (value === null || value === undefined ? 'null' : typeof value === 'number' ? (Math.round(value * 100) / 100).toString() : String(value));
