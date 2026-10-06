// Headroom search (BENCHMARK.md §4.4), independent of Three.js so it can be tested with a fake probe.
//   1. Probe at 1. If it fails, the result is 0.
//   2. Double (2, 4, 8, …) until a probe fails or the count reaches the cap.
//   3. After a failure, binary search between the last pass and the first fail until the gap ≤ max(1, 5% of the last pass).
//   4. The search stops when the time limit is reached; the result is the last passing count.
// Result: { headroom_instances, cap_reached, time_limit_reached, probes: [{ instances, pass, ...summary }] }

/**
 * @param {{ probe: (instances: number) => Promise<{ pass: boolean }>, cap: number, timeLimitMs: number, now?: () => number }} options
 */
export async function searchHeadroom({ probe, cap, timeLimitMs, now = () => performance.now() }) {
    const start = now();
    const probes = [];
    let timeLimitReached = false;

    const run = async (instances) => {
        const result = await probe(instances);
        probes.push({ instances, ...result });

        return result.pass;
    };
    const outOfTime = () => {
        if (now() - start >= timeLimitMs) timeLimitReached = true;

        return timeLimitReached;
    };
    const done = (headroom, capReached = false) => ({ headroom_instances: headroom, cap_reached: capReached, time_limit_reached: timeLimitReached, probes });

    if (!(await run(1))) return done(0);
    if (cap <= 1) return done(1, true);

    let lastPass = 1;
    let firstFail = null;
    for (let count = Math.min(2, cap); ; count = Math.min(count * 2, cap)) {
        if (outOfTime()) return done(lastPass);
        if (await run(count)) {
            lastPass = count;
            if (count === cap) return done(cap, true); // "≥ cap"
        } else {
            firstFail = count;
            break;
        }
    }

    let low = lastPass;
    let high = firstFail;
    while (high - low > Math.max(1, Math.floor(low * 0.05))) {
        if (outOfTime()) break;
        const middle = Math.floor((low + high) / 2);
        if (await run(middle)) low = middle;
        else high = middle;
    }

    return done(low);
}

/** Pass criterion for one probe (BENCHMARK.md §4.4): p95 frame interval and jank inside the budget. */
export function probePasses(summary, budget) {
    return summary.interval_p95_ms !== null && summary.jank_pct !== null
        && summary.interval_p95_ms <= budget.max_p95_frame_ms && summary.jank_pct <= budget.max_jank_pct;
}
