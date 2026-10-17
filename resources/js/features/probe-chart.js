// Crowd probe sequence chart (BENCHMARK.md §4.6, DESIGN.md §10): probe number on X, copies on Y. Each probe is marked
// by shape and a short text label, not by color only: Smooth = circle "S", Mostly smooth = square "M", Choppy =
// triangle "C". Rendered on the results page only (uPlot, dynamic import).
import uPlot from 'uplot';
import 'uplot/dist/uPlot.min.css';

const color = (name) => getComputedStyle(document.documentElement).getPropertyValue(`--${name}`).trim() || getComputedStyle(document.documentElement).getPropertyValue(`--color-${name}`).trim();
const SHORT = { smooth: 'S', mostly_smooth: 'M', choppy: 'C' };

function marker(ctx, label, x, y, size) {
    ctx.beginPath();
    if (label === 'smooth') {
        ctx.arc(x, y, size, 0, Math.PI * 2);
    } else if (label === 'mostly_smooth') {
        ctx.rect(x - size, y - size, size * 2, size * 2);
    } else {
        ctx.moveTo(x, y - size * 1.2);
        ctx.lineTo(x + size * 1.1, y + size * 0.9);
        ctx.lineTo(x - size * 1.1, y + size * 0.9);
        ctx.closePath();
    }
    ctx.fill();
    ctx.stroke();
}

/** `element` holds `<script type="application/json" data-probe-data>` with { probes: [{ copies, label }] }. */
export function drawProbeChart(element) {
    const { probes } = JSON.parse(element.querySelector('script[data-probe-data]').textContent);
    const target = element.querySelector('[data-probe-canvas]') ?? element;
    target.replaceChildren();
    if (!probes.length) return null;
    const x = probes.map((_, i) => i + 1);
    const y = probes.map((p) => p.copies);
    const width = Math.max(target.clientWidth, 200);
    const line = color('color-neutral-800');
    const fill = color('primary') || line;
    const surface = color('color-neutral-0') || '#fff';
    const text = color('color-neutral-950') || '#000';

    const heightFor = (w) => Math.round(Math.min(320, Math.max(180, w * 0.4)));
    const chart = new uPlot({
        width,
        height: heightFor(width),
        scales: { x: { time: false, range: [0.5, probes.length + 0.5] }, y: { range: (u, min, max) => [0, Math.max(1, max) * 1.15] } },
        axes: [{ label: 'Probe', incrs: [1, 2, 5, 10] }, { label: 'Copies' }],
        legend: { show: false },
        series: [{}, { label: 'Copies', stroke: line, width: 1.5, points: { show: false } }],
        hooks: {
            draw: [(u) => {
                const ctx = u.ctx;
                const ratio = devicePixelRatio || 1;
                ctx.save();
                ctx.lineWidth = 1.5 * ratio;
                ctx.strokeStyle = line;
                ctx.font = `${12 * ratio}px "Atkinson Hyperlegible Mono Variable", monospace`;
                ctx.textAlign = 'center';
                probes.forEach((probe, i) => {
                    const px = u.valToPos(x[i], 'x', true);
                    const py = u.valToPos(y[i], 'y', true);
                    ctx.fillStyle = probe.label === 'smooth' ? fill : surface;
                    marker(ctx, probe.label, px, py, 5 * ratio);
                    ctx.fillStyle = text;
                    ctx.fillText(SHORT[probe.label] ?? '–', px, py - 10 * ratio);
                });
                ctx.restore();
            }],
        },
    }, [x, y], target);
    // Follow the container width (phone rotation, window resize), so the chart never widens the page.
    new ResizeObserver(() => {
        const style = getComputedStyle(element);
        const next = Math.max(element.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight), 200);
        if (Math.abs(next - chart.width) > 4) chart.setSize({ width: next, height: heightFor(next) });
    }).observe(element);

    return chart;
}
