// Motion scenarios (BENCHMARK.md §4.2): Static, Spin, Physics. Each scenario is a per-frame update that the
// measurement loop calls before every render. The motion is the same for every asset: same speed, same seeded
// drop, same shake path, same physics settings. Assets are never rescaled.
//
// Loaded only through dynamic import (with three and cannon-es), never in the entry bundle.
import * as THREE from 'three';
import * as CANNON from 'cannon-es';
import { ConvexHull } from 'three/addons/math/ConvexHull.js';
import { seededRandom } from './random.js';

export const MOTIONS = ['static', 'spin', 'physics'];

/** Spin speed: 180° per second around each object's own vertical axis. */
export const SPIN_RAD_PER_S = Math.PI;

/** Physics settings (BENCHMARK.md §4.2). Lengths are multiples of the scale unit d. */
export const PHYSICS = {
    dt: 1 / 60, // one fixed step per rendered frame
    settleSteps: 120, // drop and settle before the shake starts (2 s of simulation time)
    solverIterations: 10,
    gravity: 9.82, // × d per s²
    maxHullPoints: 32,
    spacing: 1.2, // grid spacing, × d
    dropHeight: 1.5, // × d above the floor
    dropOffset: 0.1, // max seeded horizontal offset, × d
    wallThickness: 0.5, // × d; thick enough that a shaken object does not pass through
    shakeHz: 1.5,
    shakeAmplitude: 0.15, // × box width
    single: { floor: 3, wall: 2 }, // Side-by-Side box, × d
    crowd: { wall: 3 }, // Crowd box wall height, × d; floor side = ⌈√N⌉ × spacing
    friction: 0.4,
    restitution: 0.1,
};

/**
 * Wrap an object in a pivot group whose origin is the centre of the object's bounding box, so it spins and
 * tumbles around its own centre. The object keeps its scale.
 */
export function centredPivot(object) {
    const pivot = new THREE.Group();
    const centre = new THREE.Box3().setFromObject(object).getCenter(new THREE.Vector3());
    object.position.sub(centre);
    pivot.add(object);

    return pivot;
}

/** Scale unit d (BENCHMARK.md §4.2): the largest bounding-sphere diameter of the given objects. */
export function scaleUnit(objects) {
    let diameter = 0;
    for (const object of objects) {
        const sphere = new THREE.Box3().setFromObject(object).getBoundingSphere(new THREE.Sphere());
        diameter = Math.max(diameter, sphere.radius * 2);
    }

    return diameter || 1;
}

/** Deterministic square grid on the XZ plane, centred on the origin, used by Static and Spin crowds. */
export function gridPositions(count, spacing) {
    const columns = Math.ceil(Math.sqrt(count));
    const offset = ((columns - 1) * spacing) / 2;

    return Array.from({ length: count }, (_, i) => new THREE.Vector3((i % columns) * spacing - offset, 0, Math.floor(i / columns) * spacing - offset));
}

/** All vertex positions of an object, in the space of `root`. */
function collectPoints(object, root) {
    root.updateMatrixWorld(true);
    const toRoot = new THREE.Matrix4().copy(root.matrixWorld).invert();
    const matrix = new THREE.Matrix4();
    const points = [];

    object.traverse((child) => {
        const position = child.isMesh ? child.geometry?.attributes?.position : null;
        if (!position) return;
        matrix.multiplyMatrices(toRoot, child.matrixWorld);
        for (let i = 0; i < position.count; i++) {
            points.push(new THREE.Vector3().fromBufferAttribute(position, i).applyMatrix4(matrix));
        }
    });

    return points;
}

/** Farthest-point sampling: a deterministic, evenly spread subset of `count` points. */
function farthestPoints(points, count) {
    const centroid = points.reduce((sum, p) => sum.add(p), new THREE.Vector3()).divideScalar(points.length);
    let first = 0;
    points.forEach((p, i) => p.distanceToSquared(centroid) > points[first].distanceToSquared(centroid) && (first = i));

    const chosen = [first];
    const distance = points.map((p) => p.distanceToSquared(points[first]));
    while (chosen.length < count) {
        let next = 0;
        distance.forEach((d, i) => d > distance[next] && (next = i));
        chosen.push(next);
        points.forEach((p, i) => (distance[i] = Math.min(distance[i], p.distanceToSquared(points[next]))));
    }

    return chosen.map((i) => points[i]);
}

/** Vertices and faces of a computed ConvexHull. Faces keep the hull's outward, counter-clockwise order. */
function hullGeometry(hull) {
    const index = new Map();
    const vertices = [];
    const faces = hull.faces.map((face) => {
        const loop = [];
        let edge = face.edge;
        do {
            const node = edge.head();
            if (!index.has(node)) {
                index.set(node, vertices.length);
                vertices.push(node.point.clone());
            }
            loop.push(index.get(node));
            edge = edge.next;
        } while (edge !== face.edge);

        return loop;
    });

    return { vertices, faces };
}

/**
 * Simplified convex hull of an object (decision D50): the hull of all vertices, reduced to at most
 * PHYSICS.maxHullPoints hull points with farthest-point sampling, then hulled again. It follows the shape
 * closely but not exactly; concave parts are filled. Coordinates are in the space of `root` (the pivot).
 * @returns {{ vertices: THREE.Vector3[], faces: number[][], buildMs: number }}
 */
export function buildHull(object, root = object, maxPoints = PHYSICS.maxHullPoints) {
    const start = performance.now();
    const points = collectPoints(object, root);
    if (points.length < 4) throw new Error('The model has too few vertices for a physics shape.');

    let { vertices, faces } = hullGeometry(new ConvexHull().setFromPoints(points));
    if (vertices.length > maxPoints) {
        ({ vertices, faces } = hullGeometry(new ConvexHull().setFromPoints(farthestPoints(vertices, maxPoints))));
    }

    return { vertices, faces, buildMs: performance.now() - start };
}

/** A new cannon-es shape for one body (a shape belongs to one body; the hull data is shared). */
function hullShape(hull) {
    return new CANNON.ConvexPolyhedron({
        vertices: hull.vertices.map((v) => new CANNON.Vec3(v.x, v.y, v.z)),
        faces: hull.faces,
    });
}

/**
 * Create a motion scenario for objects that are already in the scene.
 * @param {'static'|'spin'|'physics'} kind
 * @param {{ group: THREE.Group, pivots: THREE.Group[], diameter: number, hulls?: Map<THREE.Group, object>,
 *           layout?: 'single'|'crowd', seed?: number, shakeSteps?: number, lineColor?: THREE.Color|null }} options
 *   group: parent of the pivots; the Physics box lines are added to it. hulls: buildHull() per pivot (Physics).
 *   shakeSteps: how long one shake lasts (Infinity during measurement; a few seconds in the look phase).
 * @returns {{ kind: string, ready: boolean, step: number, update: (elapsedMs: number) => number|null, shake: () => void, dispose: () => void }}
 *   update() returns the physics time in ms (Physics), else null.
 */
export function createMotion(kind, options) {
    if (kind === 'static') return { kind, ready: true, step: 0, update: () => null, shake() {}, dispose() {} };
    if (kind === 'spin') return createSpin(options);
    if (kind === 'physics') return createPhysics(options);
    throw new Error(`Unknown motion scenario: ${kind}`);
}

function createSpin({ pivots }) {
    const base = pivots.map((pivot) => pivot.rotation.y);

    return {
        kind: 'spin',
        ready: true,
        step: 0,
        update(elapsedMs) {
            const angle = (SPIN_RAD_PER_S * elapsedMs) / 1000;
            pivots.forEach((pivot, i) => (pivot.rotation.y = base[i] + angle));

            return null;
        },
        shake() {},
        dispose() {
            pivots.forEach((pivot, i) => (pivot.rotation.y = base[i]));
        },
    };
}

function createPhysics({ group, pivots, diameter: d, hulls, layout = 'single', seed = 1, shakeSteps = Infinity, lineColor = null }) {
    const random = seededRandom(seed);
    const columns = Math.ceil(Math.sqrt(pivots.length));
    const width = layout === 'single' ? PHYSICS.single.floor * d : columns * PHYSICS.spacing * d;
    const height = (layout === 'single' ? PHYSICS.single.wall : PHYSICS.crowd.wall) * d;
    const thickness = PHYSICS.wallThickness * d;

    const world = new CANNON.World({ gravity: new CANNON.Vec3(0, -PHYSICS.gravity * d, 0) });
    world.broadphase = new CANNON.SAPBroadphase(world);
    world.solver.iterations = PHYSICS.solverIterations;
    world.allowSleep = false;
    world.defaultContactMaterial.friction = PHYSICS.friction;
    world.defaultContactMaterial.restitution = PHYSICS.restitution;

    // Open-top box: floor and four walls, inner floor surface at y = 0. Kinematic: moved by the shake path only.
    const box = new CANNON.Body({ type: CANNON.Body.KINEMATIC });
    const half = width / 2;
    box.addShape(new CANNON.Box(new CANNON.Vec3(half + thickness, thickness / 2, half + thickness)), new CANNON.Vec3(0, -thickness / 2, 0));
    for (const [x, z, hx, hz] of [[half + thickness / 2, 0, thickness / 2, half + thickness], [-half - thickness / 2, 0, thickness / 2, half + thickness],
        [0, half + thickness / 2, half, thickness / 2], [0, -half - thickness / 2, half, thickness / 2]]) {
        box.addShape(new CANNON.Box(new CANNON.Vec3(hx, height / 2, hz)), new CANNON.Vec3(x, height / 2, z));
    }
    world.addBody(box);

    // The box is drawn as its 12 edges: one line object, the same for every asset.
    const lines = new THREE.LineSegments(
        new THREE.EdgesGeometry(new THREE.BoxGeometry(width, height, width)),
        new THREE.LineBasicMaterial(lineColor ? { color: lineColor } : {}),
    );
    lines.position.y = height / 2;
    group.add(lines);

    // Seeded drop: one grid layer above the floor, a seeded rotation, and a small seeded offset.
    const spacing = PHYSICS.spacing * d;
    const offset = ((columns - 1) * spacing) / 2;
    const bodies = pivots.map((pivot, i) => {
        const body = new CANNON.Body({ mass: 1, shape: hullShape(hulls.get(pivot)) });
        const jitter = () => (random() * 2 - 1) * PHYSICS.dropOffset * d;
        body.position.set((i % columns) * spacing - offset + jitter(), PHYSICS.dropHeight * d, Math.floor(i / columns) * spacing - offset + jitter());
        body.quaternion.setFromAxisAngle(new CANNON.Vec3(random() * 2 - 1, random() * 2 - 1, random() * 2 - 1).unit(), random() * Math.PI * 2);
        world.addBody(body);

        return body;
    });

    const omega = 2 * Math.PI * PHYSICS.shakeHz;
    const amplitude = PHYSICS.shakeAmplitude * width;
    let shakeStart = PHYSICS.settleSteps;

    const sync = () => {
        bodies.forEach((body, i) => {
            pivots[i].position.set(body.position.x, body.position.y, body.position.z);
            pivots[i].quaternion.set(body.quaternion.x, body.quaternion.y, body.quaternion.z, body.quaternion.w);
        });
        lines.position.x = box.position.x;
    };
    sync();

    const motion = {
        kind: 'physics',
        step: 0,
        get ready() {
            return motion.step >= PHYSICS.settleSteps;
        },
        /** One fixed physics step per frame (no sub-steps, no catch-up), so every device sees the same states. */
        update() {
            const start = performance.now();
            const t = (motion.step - shakeStart) * PHYSICS.dt;
            const shaking = t >= 0 && motion.step - shakeStart < shakeSteps;
            box.position.x = shaking ? amplitude * Math.sin(omega * t) : 0;
            box.velocity.x = shaking ? amplitude * omega * Math.cos(omega * t) : 0;
            world.step(PHYSICS.dt);
            motion.step++;
            sync();

            return performance.now() - start;
        },
        /** Look phase only: start a new shake now. */
        shake() {
            shakeStart = Math.max(motion.step, PHYSICS.settleSteps);
        },
        /** Positions of all bodies, for the determinism check. */
        state() {
            return bodies.map((b) => [b.position.x, b.position.y, b.position.z, b.quaternion.x, b.quaternion.y, b.quaternion.z, b.quaternion.w]);
        },
        dispose() {
            group.remove(lines);
            lines.geometry.dispose();
            lines.material.dispose();
            bodies.forEach((body) => world.removeBody(body));
            world.removeBody(box);
        },
    };

    return motion;
}
