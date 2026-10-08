// Smoothness label rule (BENCHMARK.md §4.4). Same edge cases as tests/Unit/SmoothnessTest.php.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { smoothnessLabel } from '../../resources/js/three/smoothness.js';

const goal60 = 1000 / 60;

test('Smooth up to 1.25 × the goal frame time with at most 1% stutter', () => {
    assert.equal(smoothnessLabel(1.25 * goal60, 1, 60), 'smooth');
    assert.equal(smoothnessLabel(16.9, 0, 60), 'smooth'); // a 60 Hz display with timer jitter is Smooth (old 16.7 ms issue)
});

test('more than 1% stutter is not Smooth', () => {
    assert.equal(smoothnessLabel(16.7, 1.01, 60), 'mostly_smooth');
});

test('Mostly smooth up to 2 × the goal frame time', () => {
    assert.equal(smoothnessLabel(1.25 * goal60 + 0.001, 0, 60), 'mostly_smooth');
    assert.equal(smoothnessLabel(2 * goal60, 30, 60), 'mostly_smooth');
});

test('Choppy above 2 × the goal frame time', () => {
    assert.equal(smoothnessLabel(2 * goal60 + 0.001, 0, 60), 'choppy');
});

test('the goal frame time follows the Target frame rate', () => {
    assert.equal(smoothnessLabel(40, 0, 30), 'smooth'); // 30 fps: Smooth up to 41.67 ms
    assert.equal(smoothnessLabel(40, 0, 60), 'choppy');
});

test('missing values give no label', () => {
    assert.equal(smoothnessLabel(null, 0, 60), null);
    assert.equal(smoothnessLabel(16, undefined, 60), null);
});
