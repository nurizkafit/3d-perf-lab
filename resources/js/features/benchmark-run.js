// Benchmark page (pages/lab/run): preflight, run the method, submit or invalidate (BENCHMARK.md §4.1).
// The progress strip is the only DOM that changes, and only between measurement windows.
import { apiFetch } from '../api.js';

const RUNNERS = {
    comparison: () => import('../three/methods/comparison.js').then((m) => m.runComparison),
    stress: () => import('../three/methods/stress.js').then((m) => m.runStress),
};

export function initBenchmarkRun(root) {
    const config = JSON.parse(document.getElementById('run-config').textContent);
    const progressText = root.querySelector('[data-progress-text]');
    const preflight = root.querySelector('[data-preflight]');
    const outcome = root.querySelector('[data-outcome]');
    const startButton = root.querySelector('[data-start]');
    const cancelButton = root.querySelector('[data-cancel]');
    const stage = root.querySelector('[data-benchmark-stage]');
    let guard = null;
    let wakeLock = null;
    let state = 'ready';

    const bar = root.querySelector('[data-progress-bar]');
    const count = root.querySelector('[data-progress-count]');
    /** Called by the runner between windows only: text, and optionally { done, total } measurements. */
    let lastTotal = 1;
    const progress = (text, step = null) => {
        progressText.textContent = text;
        if (!step) return;
        lastTotal = step.total;
        const fraction = step.total ? step.done / step.total : 0;
        bar.firstElementChild.style.transform = `scaleX(${fraction})`;
        bar.setAttribute('aria-valuenow', String(Math.round(fraction * 100)));
        count.textContent = step.done < step.total ? `Measurement ${step.done + 1} of ${step.total}` : `${step.total} of ${step.total} done`;
    };
    const show = (element, visible) => element.toggleAttribute('hidden', !visible);

    const finish = (title, message, actions = {}) => {
        show(preflight, false);
        outcome.querySelector('[data-outcome-title]').textContent = title;
        outcome.querySelector('[data-outcome-message]').textContent = message;
        show(outcome.querySelector('[data-again]'), Boolean(actions.again));
        show(outcome, true);
        show(cancelButton, false);
    };

    // WebGL 2 is required (BENCHMARK.md §4.1 step 1).
    const webgl2 = Boolean(document.createElement('canvas').getContext('webgl2'));
    if (!webgl2) {
        apiFetch(config.endpoints.invalidate, { method: 'POST', json: { reason: 'WebGL 2 is not available in this browser.' } }).catch(() => {});
        finish('This browser cannot run the test', 'WebGL 2 is not available in this browser. Use a current browser with hardware acceleration.');
        startButton.disabled = true;
    }

    startButton.addEventListener('click', async () => {
        if (state !== 'ready') return;
        state = 'running';
        show(preflight, false);
        const { createRunGuard, InvalidRun } = await import('../three/measure.js');
        guard = createRunGuard();
        try {
            wakeLock = await navigator.wakeLock?.request('screen');
        } catch {
            wakeLock = null; // not available: the checklist asks to keep the screen on
        }

        try {
            const runner = await (RUNNERS[config.method] ?? (() => Promise.reject(new Error(`The ${config.method} runner is not available yet.`))))();
            const { results, environment } = await runner(config, { stage, guard, progress });
            if (guard.cancelled) return;
            progress('Saving the results…', { done: lastTotal, total: lastTotal });
            await apiFetch(config.endpoints.submit, { method: 'POST', json: { results, environment } });
            state = 'done';
            location.assign(config.endpoints.results);
        } catch (error) {
            if (guard?.cancelled) return;
            state = 'stopped';
            if (error instanceof InvalidRun) {
                await apiFetch(config.endpoints.invalidate, { method: 'POST', json: { reason: error.reason } }).catch(() => {});
                finish('Run invalid', `${error.reason} The run was not saved as a result.`, { again: true });
            } else {
                await apiFetch(config.endpoints.invalidate, { method: 'POST', json: { reason: `Error: ${error.message}`.slice(0, 250) } }).catch(() => {});
                finish('Run stopped', error.message, { again: true });
            }
        } finally {
            guard?.dispose();
            wakeLock?.release?.();
            stage.replaceChildren();
        }
    });

    cancelButton.addEventListener('click', async () => {
        if (guard) {
            guard.cancelled = true;
            guard.reason ??= 'Cancelled by the user.';
        }
        await apiFetch(config.endpoints.cancel, { method: 'POST', json: {} }).catch(() => {});
        location.assign(config.endpoints.done);
    });

    outcome.querySelector('[data-again]')?.addEventListener('click', async () => {
        const { collectContext } = await import('../three/environment.js');
        const run = await apiFetch(config.endpoints.again, { method: 'POST', json: { environment: await collectContext() } });
        location.assign(run.url);
    });
}
