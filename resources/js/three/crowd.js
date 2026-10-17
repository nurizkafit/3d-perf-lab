// Crowd building (BENCHMARK.md §4.6), shared by the Crowd measurement, the look phase, and the explore viewer.
// Instance mode `clone`: object.clone() shares geometry and materials, one mesh per instance. Instances are assigned
// round-robin in object order, so `copies` = copies of each object. Static and Spin use a square grid on the XZ plane
// (spacing 1.2 d); Physics uses the drop grid and the crowd box of motion.js.
import * as THREE from 'three';
import { loadModel } from './loaders.js';
import { buildHull, centredPivot, createMotion, gridPositions, PHYSICS, PHYSICS_SEED, scaleUnit } from './motion.js';

/**
 * Load the objects of one crowd group as templates (one centred pivot per object, in object order).
 * @param {{ url: string }[]} assets
 * @returns {Promise<{ templates: { pivot: THREE.Group, hull: object|null }[], diameter: number, gltfs: object[] }>}
 */
export async function loadTemplates(assets, { cache = 'default' } = {}) {
    const loaded = await Promise.all(assets.map((asset) => loadModel(asset.url, { cache })));
    const templates = loaded.map(({ gltf }) => ({ pivot: centredPivot(gltf.scene), hull: null }));

    return { templates, diameter: scaleUnit(templates.map((t) => t.pivot)), gltfs: loaded.map((l) => l.gltf) };
}

/** Physics colliders, built once per template; instances share the hull data (BENCHMARK.md §4.2). */
export function ensureHulls(templates) {
    let buildMs = 0;
    for (const template of templates) {
        if (!template.hull) {
            template.hull = buildHull(template.pivot);
            buildMs += template.hull.buildMs;
        }
    }

    return buildMs;
}

/**
 * The box that contains the crowd, for auto-framing (computed from the layout, not by traversing every instance).
 * Physics: the crowd box (floor ⌈√N⌉ × 1.2 d, walls 3 d).
 */
export function crowdBounds(count, kind, d) {
    const columns = Math.ceil(Math.sqrt(count));
    if (kind === 'physics') {
        const half = (columns * PHYSICS.spacing * d) / 2;

        return new THREE.Box3(new THREE.Vector3(-half, 0, -half), new THREE.Vector3(half, PHYSICS.crowd.wall * d, half));
    }
    const half = ((columns - 1) * PHYSICS.spacing * d) / 2 + d / 2;

    return new THREE.Box3(new THREE.Vector3(-half, -d / 2, -half), new THREE.Vector3(half, d / 2, half));
}

/**
 * Build a crowd of `copies` copies of every template, with its motion scenario.
 * @returns {{ group: THREE.Group, count: number, motion: object, bounds: THREE.Box3, dispose: () => void }}
 *   dispose() removes the instances; the shared geometry and materials stay with the templates.
 */
export function createCrowd({ templates, copies, kind, diameter, lineColor = null, shakeSteps = Infinity }) {
    const group = new THREE.Group();
    const count = copies * templates.length;
    const pivots = [];
    const hulls = new Map();
    for (let i = 0; i < count; i++) {
        const template = templates[i % templates.length];
        const pivot = template.pivot.clone();
        pivot.position.set(0, 0, 0);
        pivot.quaternion.identity();
        pivots.push(pivot);
        if (template.hull) hulls.set(pivot, template.hull);
        group.add(pivot);
    }
    if (kind !== 'physics') {
        gridPositions(count, PHYSICS.spacing * diameter).forEach((position, i) => pivots[i].position.copy(position));
    }
    const motion = createMotion(kind, { group, pivots, diameter, hulls, layout: 'crowd', seed: PHYSICS_SEED, shakeSteps, lineColor });

    return {
        group,
        count,
        motion,
        bounds: crowdBounds(count, kind, diameter),
        dispose() {
            motion.dispose();
            group.clear();
        },
    };
}
