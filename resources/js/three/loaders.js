// GLB/GLTF loading with load-phase timings (BENCHMARK.md §3.2) and disposal helpers.
// The benchmark (M4.1) uses the same functions; preview mode ignores the timings.
import { LoadingManager } from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

/**
 * Load a GLB or GLTF file.
 * - fetch_ms: request start → last byte received (the main file; GLTF dependents load during parse).
 * - parse_ms: GLTFLoader.parseAsync on the received buffer.
 * - transfer_size: PerformanceResourceTiming.transferSize when the browser exposes it.
 *
 * @param {string} url
 * @param {{ cache?: RequestCache, signal?: AbortSignal }} [options]
 */
export async function loadModel(url, { cache = 'default', signal } = {}) {
    const fetchStart = performance.now();
    const response = await fetch(url, { cache, signal, credentials: 'same-origin' });
    if (!response.ok) throw new Error(`The model could not be downloaded (HTTP ${response.status}).`);
    const buffer = await response.arrayBuffer();
    const fetchEnd = performance.now();

    const resourcePath = new URL('./', new URL(url, location.href)).href;
    const loader = new GLTFLoader();
    const gltf = await loader.parseAsync(buffer, resourcePath);
    const parseEnd = performance.now();

    return {
        gltf,
        bytes: buffer.byteLength,
        timings: {
            fetch_ms: fetchEnd - fetchStart,
            parse_ms: parseEnd - fetchEnd,
            transfer_size: transferSize(response.url),
        },
    };
}

/**
 * Load a model from local files before upload (bulk upload inspection). GLTF dependents are
 * resolved by file name from `dependents`, so nothing leaves the browser.
 * @param {File} main  .glb or .gltf
 * @param {File[]} dependents  .bin and texture files referenced by a .gltf
 */
export async function loadModelFromFiles(main, dependents = []) {
    const urls = new Map(dependents.map((file) => [file.name, URL.createObjectURL(file)]));
    const manager = new LoadingManager();
    manager.setURLModifier((url) => urls.get(decodeURIComponent(url.split('/').pop())) ?? url);
    try {
        const gltf = await new GLTFLoader(manager).parseAsync(await main.arrayBuffer(), '');

        return gltf;
    } finally {
        urls.forEach((url) => URL.revokeObjectURL(url));
    }
}

/**
 * Cold/warm URL pair (BENCHMARK.md §3.2, decision D35): a unique query parameter makes the cold request miss
 * every cache; `cache: 'reload'` downloads it but stores the response, so the warm request for the same URL
 * with the default cache is served from the browser cache.
 */
export function coldWarmUrl(url) {
    return `${url}${url.includes('?') ? '&' : '?'}run=${crypto.randomUUID()}`;
}

export const COLD_CACHE = 'reload';
export const WARM_CACHE = 'default';

/**
 * One timed load into a benchmark viewer (BENCHMARK.md §3.2): fetch, parse, GPU upload, first frame, TTFF.
 * @param {{ setModel: Function, render: Function, renderer, scene, camera }} viewer benchmark-mode viewer
 */
export async function timedLoad(viewer, url, cache) {
    const { gltf, timings } = await loadModel(url, { cache });
    viewer.setModel(gltf.scene);
    const gpuUploadMs = await uploadToGpu(viewer.renderer, viewer.scene, viewer.camera);
    const firstStart = performance.now();
    viewer.render();
    await new Promise((resolve) => requestAnimationFrame(resolve));
    const firstFrameMs = performance.now() - firstStart;

    return {
        transfer_size: timings.transfer_size,
        fetch_ms: timings.fetch_ms,
        parse_ms: timings.parse_ms,
        gpu_upload_ms: gpuUploadMs,
        first_frame_ms: firstFrameMs,
        ttff_ms: timings.fetch_ms + timings.parse_ms + gpuUploadMs + firstFrameMs,
        // Not a metric: lets the runner play the first clip when animation is enabled (BENCHMARK.md §2).
        animations: gltf.animations,
    };
}

/**
 * Upload geometry, materials, and textures to the GPU and time it (gpu_upload_ms).
 * Uses compileAsync when available, otherwise compile + texture init.
 */
export async function uploadToGpu(renderer, scene, camera) {
    const start = performance.now();
    if (renderer.compileAsync) {
        await renderer.compileAsync(scene, camera);
    } else {
        renderer.compile(scene, camera);
    }
    scene.traverse((object) => eachTexture(object, (texture) => renderer.initTexture(texture)));

    return performance.now() - start;
}

/** Dispose every geometry, material, and texture under an object. */
export function disposeObject(root) {
    root.traverse((object) => {
        object.geometry?.dispose();
        eachMaterial(object, (material) => {
            eachTexture({ material }, (texture) => texture.dispose());
            material.dispose();
        });
    });
}

export function eachMaterial(object, callback) {
    if (!object.material) return;
    (Array.isArray(object.material) ? object.material : [object.material]).forEach(callback);
}

export function eachTexture(object, callback) {
    eachMaterial(object, (material) => {
        for (const value of Object.values(material)) {
            if (value?.isTexture) callback(value);
        }
    });
}

function transferSize(url) {
    const entries = performance.getEntriesByName(url, 'resource');
    const entry = entries[entries.length - 1];

    return entry && 'transferSize' in entry ? entry.transferSize : null;
}
