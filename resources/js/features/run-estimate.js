// Estimated run time (BENCHMARK.md §4.1 step 1). Same rule as App\Support\RunEstimate on the server, which also
// sets the run expiry from it (decision D59). No Three.js here: the configuration page uses it in the entry bundle.

/**
 * @param {{ motions: string[], repetitions: number, warmup_s: number, measurement_s: number }} configuration
 * @param {number} assetCount
 * @param {{ loadAllowanceS: number, physicsMinWarmupS: number, coolDownS: number }} rules
 * @returns {number} seconds
 */
export function sideBySideSeconds(configuration, assetCount, { loadAllowanceS, physicsMinWarmupS, coolDownS }) {
    const warmup = Number(configuration.warmup_s) || 0;
    const window = Number(configuration.measurement_s) || 0;
    const perMotion = configuration.motions.reduce((sum, motion) => sum + (motion === 'physics' ? Math.max(warmup, physicsMinWarmupS) : warmup) + window, 0);

    return Math.ceil((Number(configuration.repetitions) || 0) * assetCount * (loadAllowanceS + perMotion + coolDownS));
}

/** "about 9 min" / "about 40 s" */
export function formatDuration(seconds) {
    if (seconds < 60) return `about ${seconds} s`;

    return `about ${Math.ceil(seconds / 60)} min`;
}

/**
 * Crowd estimate (decision D62), same rule as App\Support\RunEstimate::crowdSeconds: typical = every search doubles
 * up to the cap plus a few binary-search probes; maximum = every search runs to the time limit.
 * @returns {{ typical: number, max: number }} seconds
 */
export function crowdSeconds(configuration, groupCount, objectsPerGroup, { loadAllowanceS, coolDownS, probeBuildS, typicalBinaryProbes }) {
    const capCopies = Math.max(1, Math.floor((Number(configuration.cap) || 1024) / Math.max(1, objectsPerGroup)));
    let doublings = 0;
    while (2 ** doublings < capCopies) doublings++;
    const probes = doublings + 1 + typicalBinaryProbes;
    const limit = configuration.time_limit_s ?? 180;
    let typical = 0;
    let max = 0;
    for (const motion of configuration.motions) {
        const probe = (motion === 'physics' ? 2 : 1) + 4 + probeBuildS;
        typical += Math.min(limit, probes * probe);
        max += limit + probe;
    }
    const perGroup = loadAllowanceS + coolDownS * Math.max(1, configuration.motions.length);
    const runs = (Number(configuration.repetitions) || 1) * groupCount;

    return { typical: Math.ceil(runs * (typical + perGroup)), max: Math.ceil(runs * (max + perGroup)) };
}
