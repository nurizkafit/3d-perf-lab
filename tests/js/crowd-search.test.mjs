// Crowd search procedure (BENCHMARK.md §4.6): doubling, binary search to within max(1, 5%), cap, time limit.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { searchCopies } from '../../resources/js/three/methods/crowd.js';

/** A device that is Smooth up to `limit` copies; records the probed counts. */
const device = (limit) => {
    const probed = [];

    return { probed, probe: async (copies) => (probed.push(copies), copies <= limit) };
};

test('doubles, then narrows to within 5% of the last Smooth count', async () => {
    const d = device(300);
    const result = await searchCopies(d.probe, { capCopies: 1024, overTime: () => false });
    assert.deepEqual(d.probed.slice(0, 10), [1, 2, 4, 8, 16, 32, 64, 128, 256, 512]);
    assert.ok(result.smoothCopies <= 300 && 300 - result.smoothCopies <= Math.max(1, Math.floor(0.05 * result.smoothCopies)), `got ${result.smoothCopies}`);
    assert.equal(result.capReached, false);
    assert.equal(result.timeLimitReached, false);
});

test('a first probe that is not Smooth gives 0 without a binary search', async () => {
    const d = device(0);
    assert.deepEqual(await searchCopies(d.probe, { capCopies: 1024, overTime: () => false }), { smoothCopies: 0, capReached: false, timeLimitReached: false });
    assert.deepEqual(d.probed, [1]);
});

test('a Smooth cap stops the search and records the flag; the last step probes exactly the cap', async () => {
    const d = device(10_000);
    const result = await searchCopies(d.probe, { capCopies: 341, overTime: () => false });
    assert.equal(result.smoothCopies, 341);
    assert.equal(result.capReached, true);
    assert.equal(d.probed.at(-1), 341);
});

test('the time limit stops the search and keeps the last Smooth count', async () => {
    const d = device(10_000);
    let probes = 0;
    const result = await searchCopies(async (c) => (probes++, d.probe(c)), { capCopies: 4096, overTime: () => probes >= 4 });
    assert.equal(result.smoothCopies, 8);
    assert.equal(result.timeLimitReached, true);
});

test('the binary search stops at a gap of one for small counts', async () => {
    const d = device(5);
    const result = await searchCopies(d.probe, { capCopies: 1024, overTime: () => false });
    assert.equal(result.smoothCopies, 5);
});
