// Estimated run time: same numbers as App\Support\RunEstimate (tests/Unit/BestMarkersTest.php).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { formatDuration, sideBySideSeconds } from '../../resources/js/features/run-estimate.js';

const rules = (coolDownS) => ({ loadAllowanceS: 4, physicsMinWarmupS: 2, coolDownS });

test('the estimate counts loads, motions, the physics warm-up minimum, and the cool-down', () => {
    assert.equal(sideBySideSeconds({ motions: ['static', 'spin', 'physics'], repetitions: 3, warmup_s: 1, measurement_s: 10 }, 2, rules(3)), 246);
    assert.equal(sideBySideSeconds({ motions: ['static'], repetitions: 1, warmup_s: 2, measurement_s: 10 }, 2, rules(10)), 52);
    assert.equal(sideBySideSeconds({ motions: ['static', 'spin', 'physics'], repetitions: 5, warmup_s: 10, measurement_s: 30 }, 4, rules(3)), 2540);
});

test('durations read in seconds below a minute, else in whole minutes rounded up', () => {
    assert.equal(formatDuration(40), 'about 40 s');
    assert.equal(formatDuration(246), 'about 5 min');
});

test('the crowd estimate has a typical value and a maximum with the 3-minute limit per search', async () => {
    const { crowdSeconds } = await import('../../resources/js/features/run-estimate.js');
    const rules = { loadAllowanceS: 4, coolDownS: 3, probeBuildS: 0.5, typicalBinaryProbes: 4 };
    // cap 1024 → 11 doubling probes + 4 = 15; static/spin probe 5.5 s, physics 6.5 s; per group 4 + 3 × 3 = 13 s.
    assert.deepEqual(crowdSeconds({ motions: ['static', 'spin', 'physics'], repetitions: 1, cap: 1024 }, 2, 1, rules), {
        typical: Math.ceil(2 * (15 * 5.5 + 15 * 5.5 + 15 * 6.5 + 13)),
        max: Math.ceil(2 * (185.5 + 185.5 + 186.5 + 13)),
    });
});
