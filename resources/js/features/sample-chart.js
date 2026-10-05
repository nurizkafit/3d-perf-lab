// Frame-interval and render-work samples as a line chart (uPlot, approved at M4.1). Rendered after the run only.
import uPlot from 'uplot';
import 'uplot/dist/uPlot.min.css';

const token = (name) => getComputedStyle(document.documentElement).getPropertyValue(`--color-${name}`).trim();

export function drawSamplesChart(container, samples) {
    container.replaceChildren();
    const frames = samples.interval.map((_, i) => i + 1);
    const work = samples.interval.map((_, i) => samples.work[i + 1] ?? null);
    const width = Math.max(container.clientWidth, 280);

    return new uPlot({
        width,
        height: Math.round(width * 0.35),
        scales: { x: { time: false } },
        axes: [{ label: 'Frame' }, { label: 'ms' }],
        series: [
            {},
            { label: 'Frame interval', stroke: token('primary-base') || token('primary'), width: 1.5 },
            { label: 'Render work (CPU)', stroke: token('neutral-800'), width: 1.5, dash: [6, 4] },
        ],
    }, [frames, samples.interval, work], container);
}
