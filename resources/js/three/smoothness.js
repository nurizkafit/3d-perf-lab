// Smoothness label (BENCHMARK.md §4.4, decision D44). Same rule as App\Support\Smoothness on the server;
// the browser needs it for the Crowd search and the progress strip.

export const SMOOTH_FACTOR = 1.25;
export const MOSTLY_FACTOR = 2;
export const MAX_JANK_PCT = 1;

export const LABELS = { smooth: 'Smooth', mostly_smooth: 'Mostly smooth', choppy: 'Choppy' };

export const goalFrameMs = (targetFps) => 1000 / targetFps;

/** @returns {'smooth'|'mostly_smooth'|'choppy'|null} null when a value is missing */
export function smoothnessLabel(intervalP95Ms, jankPct, targetFps) {
    if (intervalP95Ms === null || intervalP95Ms === undefined || jankPct === null || jankPct === undefined) return null;
    const goal = goalFrameMs(targetFps);
    if (intervalP95Ms <= SMOOTH_FACTOR * goal && jankPct <= MAX_JANK_PCT) return 'smooth';
    if (intervalP95Ms <= MOSTLY_FACTOR * goal) return 'mostly_smooth';

    return 'choppy';
}
