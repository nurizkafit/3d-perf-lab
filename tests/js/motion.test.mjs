// Motion scenarios (BENCHMARK.md §4.2): spin speed, simplified hull limits, physics determinism.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import * as THREE from 'three';
import { buildHull, centredPivot, createMotion, PHYSICS, scaleUnit, SPIN_RAD_PER_S } from '../../resources/js/three/motion.js';

const meshes = () => [
    new THREE.Mesh(new THREE.SphereGeometry(0.5, 48, 24)),
    new THREE.Mesh(new THREE.BoxGeometry(1, 0.6, 0.4)),
    new THREE.Mesh(new THREE.TorusKnotGeometry(0.3, 0.1, 128, 16)),
];

function physicsScene(count, seed = 7) {
    const group = new THREE.Group();
    const pivots = Array.from({ length: count }, (_, i) => centredPivot(meshes()[i % 3]));
    pivots.forEach((pivot) => group.add(pivot));
    const hulls = new Map(pivots.map((pivot) => [pivot, buildHull(pivot)]));
    const motion = createMotion('physics', { group, pivots, hulls, diameter: scaleUnit(pivots), layout: count === 1 ? 'single' : 'crowd', seed });

    return { motion, group };
}

test('Spin turns each object 180° per second around its own vertical axis', () => {
    const pivots = [centredPivot(meshes()[1]), centredPivot(meshes()[2])];
    const motion = createMotion('spin', { pivots });
    motion.update(500);
    pivots.forEach((pivot) => assert.ok(Math.abs(pivot.rotation.y - SPIN_RAD_PER_S / 2) < 1e-9));
    motion.dispose();
    pivots.forEach((pivot) => assert.equal(pivot.rotation.y, 0));
});

test('Static does nothing and reports no physics time', () => {
    assert.equal(createMotion('static', {}).update(1000), null);
});

test('the simplified hull has at most 32 points and valid faces', () => {
    for (const mesh of meshes()) {
        const pivot = centredPivot(mesh);
        const hull = buildHull(pivot);
        assert.ok(hull.vertices.length >= 4 && hull.vertices.length <= PHYSICS.maxHullPoints, `${hull.vertices.length} points`);
        hull.faces.flat().forEach((index) => assert.ok(index >= 0 && index < hull.vertices.length));
    }
});

test('a box keeps its 8 corners: the hull follows the shape', () => {
    const hull = buildHull(centredPivot(new THREE.Mesh(new THREE.BoxGeometry(2, 1, 1))));
    assert.equal(hull.vertices.length, 8);
});

test('physics gives the same states in two runs with the same seed', () => {
    const a = physicsScene(9);
    const b = physicsScene(9);
    for (let i = 0; i < PHYSICS.settleSteps + 120; i++) {
        a.motion.update();
        b.motion.update();
    }
    assert.deepEqual(a.motion.state(), b.motion.state());
});

test('physics reports its own time and is ready after the settle steps', () => {
    const { motion } = physicsScene(1);
    assert.equal(motion.ready, false);
    let time = null;
    for (let i = 0; i < PHYSICS.settleSteps; i++) time = motion.update();
    assert.equal(typeof time, 'number');
    assert.ok(time >= 0);
    assert.equal(motion.ready, true);
});

test('objects stay inside the shaken box', () => {
    const { motion } = physicsScene(9, 3);
    for (let i = 0; i < PHYSICS.settleSteps + 300; i++) motion.update();
    motion.state().forEach(([, y]) => assert.ok(y > -0.5, `an object fell through the floor (y = ${y})`));
});
