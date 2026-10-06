// Run: node tests/js/headroom-search.test.mjs  (no dependencies; uses node:test)
import test from 'node:test';
import assert from 'node:assert/strict';
import { probePasses, searchHeadroom } from '../../resources/js/three/methods/headroom-search.js';

const fakeProbe = (threshold, msPerProbe = 5000, clock = { t: 0 }) => ({
    clock,
    probe: async (instances) => {
        clock.t += msPerProbe;
        return { pass: instances <= threshold };
    },
    now: () => clock.t,
});

test('fails at one instance → 0', async () => {
    const f = fakeProbe(0);
    const r = await searchHeadroom({ probe: f.probe, cap: 1024, timeLimitMs: 180000, now: f.now });
    assert.equal(r.headroom_instances, 0);
    assert.deepEqual(r.probes.map((p) => p.instances), [1]);
});

test('doubling, then binary search until the gap is at most 5% of the last pass', async () => {
    const f = fakeProbe(300);
    const r = await searchHeadroom({ probe: f.probe, cap: 1024, timeLimitMs: 1e9, now: f.now });
    assert.deepEqual(r.probes.slice(0, 10).map((p) => p.instances), [1, 2, 4, 8, 16, 32, 64, 128, 256, 512]);
    assert.ok(r.headroom_instances <= 300 && 300 - r.headroom_instances <= Math.max(1, 0.05 * r.headroom_instances));
    assert.equal(r.cap_reached, false);
    assert.equal(r.time_limit_reached, false);
});

test('a light model reaches a higher headroom than a heavy one', async () => {
    const lf = fakeProbe(900);
    const hf = fakeProbe(40);
    const l = await searchHeadroom({ probe: lf.probe, cap: 1024, timeLimitMs: 1e9, now: lf.now });
    const h = await searchHeadroom({ probe: hf.probe, cap: 1024, timeLimitMs: 1e9, now: hf.now });
    assert.ok(l.headroom_instances > h.headroom_instances);
});

test('the cap stops the search and is recorded', async () => {
    const f = fakeProbe(1e9);
    const r = await searchHeadroom({ probe: f.probe, cap: 1024, timeLimitMs: 1e9, now: f.now });
    assert.equal(r.headroom_instances, 1024);
    assert.equal(r.cap_reached, true);
    assert.equal(r.probes.at(-1).instances, 1024);
});

test('a cap that is not a power of two is probed exactly', async () => {
    const f = fakeProbe(1e9);
    const r = await searchHeadroom({ probe: f.probe, cap: 3000, timeLimitMs: 1e9, now: f.now });
    assert.deepEqual(r.probes.slice(-2).map((p) => p.instances), [2048, 3000]);
    assert.equal(r.headroom_instances, 3000);
});

test('the time limit stops the search, keeps the last pass, and is recorded', async () => {
    const f = fakeProbe(1e9, 60000); // one minute per probe
    const r = await searchHeadroom({ probe: f.probe, cap: 4096, timeLimitMs: 180000, now: f.now });
    assert.equal(r.time_limit_reached, true);
    assert.equal(r.cap_reached, false);
    assert.equal(r.headroom_instances, 4); // probes at 1, 2, 4 (3 minutes), then stopped
});

test('pass criterion needs both p95 and jank inside the budget', () => {
    const budget = { max_p95_frame_ms: 16.7, max_jank_pct: 1 };
    assert.equal(probePasses({ interval_p95_ms: 16.7, jank_pct: 1 }, budget), true);
    assert.equal(probePasses({ interval_p95_ms: 16.8, jank_pct: 0 }, budget), false);
    assert.equal(probePasses({ interval_p95_ms: 10, jank_pct: 1.5 }, budget), false);
    assert.equal(probePasses({ interval_p95_ms: null, jank_pct: 0 }, budget), false);
});
