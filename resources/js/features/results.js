// Result charts (DESIGN.md §10) with uPlot, drawn after the page loads; never during a run.
import uPlot from 'uplot';
import 'uplot/dist/uPlot.min.css';

const token = (name) => getComputedStyle(document.documentElement).getPropertyValue(`--color-${name}`).trim();
const PHASES = [
    ['fetch_ms', 'Download'],
    ['parse_ms', 'Parse'],
    ['gpu_upload_ms', 'GPU upload'],
    ['first_frame_ms', 'First frame'],
];
// DESIGN.md §10 series colors; phases are also told apart by their order and the legend labels.
const COLORS = () => [token('primary-base'), token('neutral-800'), token('primary-light'), token('neutral-600')];

const width = (el) => Math.max(el.clientWidth, 260);
/** Whole-number tick steps: instance counts and probe numbers are integers. */
const INTEGER_INCRS = [1, 2, 5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000];

/** Stacked bars: each series is the cumulative sum, drawn back to front. */
function loadChart(el, data, phase) {
    const names = data.assets.map((a) => a.name);
    const x = names.map((_, i) => i);
    const colors = COLORS();
    const cumulative = [];
    let running = x.map(() => 0);
    for (const [key] of PHASES) {
        running = running.map((sum, i) => sum + (data.assets[i].load[key][phase] ?? 0));
        cumulative.push([...running]);
    }
    const bars = uPlot.paths.bars({ size: [0.6, 64] });

    return new uPlot({
        width: width(el),
        height: 240,
        scales: { x: { time: false, range: [-0.5, x.length - 0.5] }, y: { range: (u, min, max) => [0, max * 1.1] } },
        axes: [{ values: (u, ticks) => ticks.map((t) => names[t] ?? ''), splits: () => x }, { label: 'ms' }],
        series: [{}, ...PHASES.map(([, label], i) => ({ label, fill: colors[i], stroke: colors[i], paths: bars, points: { show: false } })).reverse()],
        legend: { live: false },
    }, [x, ...cumulative.reverse()], el);
}

/** Histogram of frame intervals (0.5 ms bins) with the budget p95 limit as a dashed reference line. */
function histogram(el, intervals, limit) {
    if (!intervals.length) {
        el.textContent = 'No samples.';

        return null;
    }
    const sorted = [...intervals].sort((a, b) => a - b);
    const top = Math.max(sorted[Math.floor(sorted.length * 0.995)] ?? sorted.at(-1), limit ?? 0) * 1.15;
    const bin = 0.5;
    const bins = Math.ceil(top / bin) + 1;
    const counts = new Array(bins).fill(0);
    intervals.forEach((value) => (counts[Math.min(bins - 1, Math.floor(value / bin))] += 1));
    const x = counts.map((_, i) => i * bin);
    const destructive = getComputedStyle(document.documentElement).getPropertyValue('--destructive').trim();

    return new uPlot({
        width: width(el),
        height: 200,
        scales: { x: { time: false }, y: { range: (u, min, max) => [0, max * 1.1] } },
        axes: [{ label: 'Frame interval (ms)' }, { label: 'Frames' }],
        series: [{}, { label: 'Frames', fill: token('primary-base'), stroke: token('primary-base'), paths: uPlot.paths.bars({ size: [0.9, 32], align: 1 }), points: { show: false } }],
        legend: { show: false },
        hooks: {
            draw: [(u) => {
                if (!limit) return;
                const { ctx } = u;
                const px = u.valToPos(limit, 'x', true);
                ctx.save();
                ctx.strokeStyle = destructive;
                ctx.setLineDash([6, 4]);
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(px, u.bbox.top);
                ctx.lineTo(px, u.bbox.top + u.bbox.height);
                ctx.stroke();
                ctx.fillStyle = destructive;
                ctx.font = `600 ${12 * devicePixelRatio}px system-ui`;
                const label = `Budget p95 ${limit} ms`;
                const nearRight = px + ctx.measureText(label).width + 12 * devicePixelRatio > u.bbox.left + u.bbox.width;
                ctx.textAlign = nearRight ? 'right' : 'left';
                ctx.fillText(label, px + (nearRight ? -6 : 6) * devicePixelRatio, u.bbox.top + 14 * devicePixelRatio);
                ctx.restore();
            }],
        },
    }, [x, counts], el);
}

/** Stress: p95 frame interval, render work p95 (and GPU p95) against instance count, with the budget line. */
function stressChart(el, data) {
    const destructive = getComputedStyle(document.documentElement).getPropertyValue('--destructive').trim();
    const limit = data.budget_p95_ms;
    const hasGpu = data.gpu_p95.some((v) => v !== null);
    // Lines differ by color, dash, and marker shape (DESIGN.md §10), not color only.
    const series = [
        {},
        { label: 'p95 frame interval', stroke: token('primary-base'), width: 2, points: { size: 7 } },
        { label: 'Render work p95', stroke: token('neutral-800'), width: 2, dash: [6, 4], points: { size: 7, fill: token('neutral-0') } },
        ...(hasGpu ? [{ label: 'GPU time p95', stroke: token('primary-light'), width: 2, dash: [2, 3], points: { size: 6 } }] : []),
    ];
    const values = [data.instances, data.interval_p95, data.work_p95, ...(hasGpu ? [data.gpu_p95] : [])];
    const max = Math.max(...values.slice(1).flat().filter((v) => v !== null), limit ?? 0) * 1.15;

    return new uPlot({
        width: width(el),
        height: 300,
        scales: { x: { time: false }, y: { range: [0, max || 1] } },
        axes: [{ label: 'Instances' }, { label: 'ms' }],
        series,
        hooks: {
            draw: [(u) => {
                if (!limit) return;
                const { ctx } = u;
                const py = u.valToPos(limit, 'y', true);
                ctx.save();
                ctx.strokeStyle = destructive;
                ctx.setLineDash([6, 4]);
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(u.bbox.left, py);
                ctx.lineTo(u.bbox.left + u.bbox.width, py);
                ctx.stroke();
                ctx.fillStyle = destructive;
                ctx.font = `600 ${12 * devicePixelRatio}px system-ui`;
                ctx.textAlign = 'right';
                ctx.fillText(`Budget frame time ${limit} ms`, u.bbox.left + u.bbox.width - 8 * devicePixelRatio, py - 6 * devicePixelRatio);
                ctx.restore();
            }],
        },
    }, values, el);
}

/** Headroom bars in selection order with the value printed on each bar (BENCHMARK.md §4.4). */
function headroomBars(el, data) {
    const x = data.groups.map((_, i) => i);
    const names = data.groups.map((g) => g.label);
    const values = data.groups.map((g) => g.median ?? 0);
    const ink = token('neutral-950');

    return new uPlot({
        width: width(el),
        height: 260,
        scales: { x: { time: false, range: [-0.5, x.length - 0.5] }, y: { range: (u, min, max) => [0, Math.max(max, 1) * 1.2] } },
        axes: [{ values: (u, ticks) => ticks.map((t) => names[t] ?? ''), splits: () => x }, { label: 'Instances', incrs: INTEGER_INCRS }],
        series: [{}, { label: 'Headroom', fill: token('primary-base'), stroke: token('primary-base'), paths: uPlot.paths.bars({ size: [0.5, 80] }), points: { show: false } }],
        legend: { show: false },
        hooks: {
            draw: [(u) => {
                const { ctx } = u;
                ctx.save();
                ctx.fillStyle = ink;
                ctx.textAlign = 'center';
                ctx.font = `700 ${13 * devicePixelRatio}px system-ui`;
                data.groups.forEach((g, i) => ctx.fillText(g.display, u.valToPos(i, 'x', true), u.valToPos(values[i], 'y', true) - 8 * devicePixelRatio));
                ctx.restore();
            }],
        },
    }, [x, values], el);
}

/**
 * Probe sequence: probe number on X, instance count on Y. Pass = filled circle, fail = cross (shape),
 * plus a "Pass"/"Fail" legend: never color only (BENCHMARK.md §4.4).
 */
function probeChart(el, group) {
    const series = group.repetitions;
    const longest = Math.max(1, ...series.map((r) => r.probes.length));
    const x = Array.from({ length: longest }, (_, i) => i + 1);
    const lines = [token('primary-base'), token('neutral-800'), token('primary-light'), token('neutral-600'), token('primary-dark')];
    const dashes = [[], [6, 4], [2, 3], [8, 3, 2, 3], [1, 2]];
    const columns = series.map((r) => x.map((_, i) => r.probes[i]?.instances ?? null));

    const chart = new uPlot({
        width: width(el),
        height: 240,
        scales: { x: { time: false, range: [0.5, longest + 0.5] }, y: { distr: 3, log: 2 } },
        axes: [{ label: 'Probe', incrs: INTEGER_INCRS }, { label: 'Instances' }],
        legend: { live: false },
        series: [{}, ...series.map((r, i) => ({ label: `Repetition ${r.repetition}`, stroke: lines[i % lines.length], dash: dashes[i % dashes.length], width: 1.5, points: { show: false } }))],
        hooks: {
            draw: [(u) => {
                const { ctx } = u;
                const r = 5 * devicePixelRatio;
                ctx.save();
                series.forEach((rep, s) => rep.probes.forEach((probe, i) => {
                    const cx = u.valToPos(i + 1, 'x', true);
                    const cy = u.valToPos(probe.instances, 'y', true);
                    ctx.strokeStyle = lines[s % lines.length];
                    ctx.fillStyle = lines[s % lines.length];
                    ctx.lineWidth = 2 * devicePixelRatio;
                    if (probe.pass) {
                        ctx.beginPath();
                        ctx.arc(cx, cy, r, 0, Math.PI * 2);
                        ctx.fill();
                    } else {
                        ctx.beginPath();
                        ctx.moveTo(cx - r, cy - r);
                        ctx.lineTo(cx + r, cy + r);
                        ctx.moveTo(cx + r, cy - r);
                        ctx.lineTo(cx - r, cy + r);
                        ctx.stroke();
                    }
                }));
                ctx.restore();
            }],
        },
    }, [x, ...columns], el);

    const key = document.createElement('p');
    key.className = 'text-body-sm text-neutral-600';
    key.textContent = '● Pass (filled circle) · ✕ Fail (cross). The probe list below names each result.';
    el.after(key);

    return chart;
}

export function initResults(root) {
    const data = JSON.parse(document.getElementById('results-charts').textContent);
    if (data.headroom?.groups) {
        headroomBars(root.querySelector('[data-chart="headroom-bars"]'), data.headroom);
        root.querySelectorAll('[data-chart="probes"]').forEach((el) => probeChart(el, data.headroom.groups[Number(el.dataset.index)]));

        return;
    }
    if (data.stress?.instances) {
        stressChart(root.querySelector('[data-chart="stress"]'), data.stress);

        return;
    }
    if (!data.assets) return;
    loadChart(root.querySelector('[data-chart="load-cold"]'), data, 'cold');
    loadChart(root.querySelector('[data-chart="load-warm"]'), data, 'warm');
    root.querySelectorAll('[data-chart="histogram"]').forEach((el) => histogram(el, data.assets[Number(el.dataset.index)].intervals, data.budget_p95_ms));
}
