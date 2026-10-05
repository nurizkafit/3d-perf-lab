// Asset metrics (BENCHMARK.md §3.1) and thumbnail rendering for a loaded model.
import * as THREE from 'three';
import { addLights, DEFAULT_CAMERA, DEFAULT_LIGHTING, frameCamera, tokenColor } from './viewer.js';
import { eachMaterial, eachTexture } from './loaders.js';

/**
 * Count what the file contains, as Three.js builds it.
 * - vertices: vertex count of every mesh (a mesh used twice counts twice).
 * - triangles: index count / 3, or vertex count / 3 for non-indexed triangle meshes.
 * - texture_memory_estimate: Σ width × height × 4 bytes, × 1.33 when mipmaps are generated. An estimate.
 */
export function inspectModel(root) {
    const materials = new Set();
    const textures = new Set();
    let meshes = 0;
    let vertices = 0;
    let triangles = 0;

    root.updateMatrixWorld(true);
    root.traverse((object) => {
        if (!object.isMesh) return;
        meshes++;
        const geometry = object.geometry;
        const count = geometry.attributes.position?.count ?? 0;
        vertices += count;
        if (!object.isPoints && !object.isLine) {
            triangles += Math.floor((geometry.index ? geometry.index.count : count) / 3);
        }
        eachMaterial(object, (material) => materials.add(material));
        eachTexture(object, (texture) => textures.add(texture));
    });

    const textureDimensions = [];
    let textureMemory = 0;
    for (const texture of textures) {
        const image = texture.image ?? texture.source?.data;
        const width = image?.width ?? 0;
        const height = image?.height ?? 0;
        textureDimensions.push([width, height]);
        const mipmaps = texture.generateMipmaps && ![THREE.NearestFilter, THREE.LinearFilter].includes(texture.minFilter);
        textureMemory += width * height * 4 * (mipmaps ? 1.33 : 1);
    }

    return {
        vertices,
        triangles,
        meshes,
        materials: materials.size,
        textures: textures.size,
        texture_dimensions: textureDimensions,
        texture_memory_estimate: Math.round(textureMemory),
    };
}

/**
 * Render a thumbnail with a fixed camera and size, independent of the user's view.
 * Uses its own small renderer, which is disposed afterwards.
 * @returns {Promise<Blob>} WebP when the browser can encode it, JPEG otherwise.
 */
export async function renderThumbnail(root, { size = 512, quality = 0.85, background = 'neutral-100' } = {}) {
    const renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
    renderer.setPixelRatio(1);
    renderer.setSize(size, size, false);

    const scene = new THREE.Scene();
    scene.background = tokenColor(background);
    const lights = addLights(scene, DEFAULT_LIGHTING);
    const proxy = root.clone(); // shares geometry and materials; nothing new to dispose
    scene.add(proxy);

    const camera = new THREE.PerspectiveCamera(DEFAULT_CAMERA.fov, 1, 0.01, 1000);
    frameCamera(camera, proxy, DEFAULT_CAMERA);
    renderer.render(scene, camera);

    try {
        const webp = await toBlob(renderer.domElement, 'image/webp', quality);

        return webp?.type === 'image/webp' ? webp : await toBlob(renderer.domElement, 'image/jpeg', quality);
    } finally {
        scene.remove(proxy, lights);
        renderer.dispose();
        renderer.forceContextLoss();
    }
}

function toBlob(canvas, type, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, type, quality));
}
