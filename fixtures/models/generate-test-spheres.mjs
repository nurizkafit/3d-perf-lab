// Generates the synthetic development fixtures (decision D63): one "Test sphere" per tool folder with different
// detail, and one heavy sphere for the Crowd sanity check. Dummy models, development only, never user data.
// Run from the project root: node fixtures/models/generate-test-spheres.mjs
import { writeFileSync } from 'node:fs';
import { IcosahedronGeometry } from 'three';
import { mergeVertices } from 'three/addons/utils/BufferGeometryUtils.js';

const FILES = [
    { path: 'blender/test-sphere.glb', detail: 3, color: [0.85, 0.35, 0.2, 1] }, // 320 triangles
    { path: 'spline/test-sphere.glb', detail: 12, color: [0.2, 0.45, 0.85, 1] }, // 3,380 triangles
    { path: 'prisma3d/test-sphere.glb', detail: 40, color: [0.25, 0.65, 0.35, 1] }, // 33,620 triangles
    { path: 'blender/heavy-sphere.glb', detail: 99, color: [0.55, 0.3, 0.7, 1] }, // 200,000 triangles
];

/** A one-mesh GLB: positions, normals, indices, one material. */
function glb(geometry, color) {
    const position = geometry.getAttribute('position').array;
    const normal = geometry.getAttribute('normal').array;
    const index = geometry.getIndex().array;
    const indices = position.length / 3 > 65535 ? Uint32Array.from(index) : Uint16Array.from(index);
    const pad = (n) => (n + 3) & ~3;

    const views = [Float32Array.from(position), Float32Array.from(normal), indices];
    const offsets = [];
    let length = 0;
    for (const view of views) {
        offsets.push(length);
        length = pad(length + view.byteLength);
    }
    const bin = Buffer.alloc(length);
    views.forEach((view, i) => Buffer.from(view.buffer, view.byteOffset, view.byteLength).copy(bin, offsets[i]));

    geometry.computeBoundingBox();
    const { min, max } = geometry.boundingBox;
    const count = position.length / 3;
    const json = {
        asset: { version: '2.0', generator: '3d-perf-lab generate-test-spheres.mjs' },
        scene: 0,
        scenes: [{ nodes: [0] }],
        nodes: [{ mesh: 0, name: 'Sphere' }],
        meshes: [{ primitives: [{ attributes: { POSITION: 0, NORMAL: 1 }, indices: 2, material: 0 }] }],
        materials: [{ pbrMetallicRoughness: { baseColorFactor: color, metallicFactor: 0, roughnessFactor: 0.6 } }],
        buffers: [{ byteLength: length }],
        bufferViews: [
            { buffer: 0, byteOffset: offsets[0], byteLength: views[0].byteLength, target: 34962 },
            { buffer: 0, byteOffset: offsets[1], byteLength: views[1].byteLength, target: 34962 },
            { buffer: 0, byteOffset: offsets[2], byteLength: views[2].byteLength, target: 34963 },
        ],
        accessors: [
            { bufferView: 0, componentType: 5126, count, type: 'VEC3', min: [min.x, min.y, min.z], max: [max.x, max.y, max.z] },
            { bufferView: 1, componentType: 5126, count, type: 'VEC3' },
            { bufferView: 2, componentType: indices instanceof Uint32Array ? 5125 : 5123, count: indices.length, type: 'SCALAR' },
        ],
    };
    let text = Buffer.from(JSON.stringify(json));
    text = Buffer.concat([text, Buffer.alloc(pad(text.length) - text.length, 0x20)]);

    const header = Buffer.alloc(12);
    header.write('glTF', 0);
    header.writeUInt32LE(2, 4);
    header.writeUInt32LE(12 + 8 + text.length + 8 + bin.length, 8);
    const chunk = (type, data) => {
        const h = Buffer.alloc(8);
        h.writeUInt32LE(data.length, 0);
        h.write(type, 4);

        return Buffer.concat([h, data]);
    };

    return Buffer.concat([header, chunk('JSON', text), chunk('BIN\0', bin)]);
}

for (const file of FILES) {
    const geometry = mergeVertices(new IcosahedronGeometry(0.5, file.detail).deleteAttribute('uv'));
    geometry.computeVertexNormals();
    const data = glb(geometry, file.color);
    writeFileSync(new URL(file.path, import.meta.url), data);
    console.log(`${file.path}: ${geometry.getIndex().count / 3} triangles, ${(data.length / 1024).toFixed(1)} KB`);
}
