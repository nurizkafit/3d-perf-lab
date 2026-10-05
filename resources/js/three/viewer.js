// Shared Three.js viewer (AGENT.md §6.2). One viewer, several modes; M3.1 implements `preview`.
// Benchmark (M4.1) and public reproduction (M4.5) are later modes of this same viewer.
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { ViewHelper } from 'three/addons/helpers/ViewHelper.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { disposeObject, loadModel } from './loaders.js';

export const DEFAULT_CAMERA = { fov: 45, position_mode: 'auto_frame', distance_factor: 1.5, elevation_deg: 20, azimuth_deg: 45 };
export const DEFAULT_LIGHTING = { ambient_intensity: 0.6, directional_lights: [{ intensity: 2, elevation_deg: 45, azimuth_deg: 45 }], environment_map: false };
export const DEFAULT_BACKGROUND = { color_token: 'neutral-100' };

/** Read a design-token color from CSS, so the viewer never hard-codes a color. */
export function tokenColor(token) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(`--color-${token}`).trim();

    // Missing token: transparent canvas, so the frame's CSS background shows through.
    return value ? new THREE.Color(value) : null;
}

/** Unit vector from elevation and azimuth in degrees (Y up, azimuth 0 = +Z, clockwise seen from above). */
export function direction(elevationDeg, azimuthDeg) {
    const elevation = THREE.MathUtils.degToRad(elevationDeg);
    const azimuth = THREE.MathUtils.degToRad(azimuthDeg);

    return new THREE.Vector3(
        Math.cos(elevation) * Math.sin(azimuth),
        Math.sin(elevation),
        Math.cos(elevation) * Math.cos(azimuth),
    );
}

/**
 * Auto-frame: fit the bounding sphere of `object` in the camera view (BENCHMARK.md §2).
 * Returns the sphere centre, which becomes the orbit target.
 */
export function frameCamera(camera, object, preset = DEFAULT_CAMERA) {
    return frameBox(camera, new THREE.Box3().setFromObject(object), preset);
}

/** Auto-frame a box (Stress/Headroom: the grid of the largest step), returns the centre. */
export function frameBox(camera, box, preset = DEFAULT_CAMERA) {
    const sphere = box.getBoundingSphere(new THREE.Sphere());
    const radius = sphere.radius > 0 ? sphere.radius : 1;
    const fov = THREE.MathUtils.degToRad(preset.fov);
    const fitFov = camera.aspect < 1 ? 2 * Math.atan(Math.tan(fov / 2) * camera.aspect) : fov;
    const distance = (radius / Math.sin(fitFov / 2)) * preset.distance_factor;

    camera.fov = preset.fov;
    camera.near = Math.max(distance / 1000, 0.001);
    camera.far = distance * 100;
    camera.position.copy(sphere.center).addScaledVector(direction(preset.elevation_deg, preset.azimuth_deg), distance);
    camera.lookAt(sphere.center);
    camera.updateProjectionMatrix();

    return sphere.center.clone();
}

/** Ambient + directional lights from a lighting preset. */
export function addLights(scene, lighting = DEFAULT_LIGHTING) {
    const group = new THREE.Group();
    group.name = 'preset-lights';
    group.add(new THREE.AmbientLight(0xffffff, lighting.ambient_intensity));
    for (const light of lighting.directional_lights ?? []) {
        const directional = new THREE.DirectionalLight(0xffffff, light.intensity);
        directional.position.copy(direction(light.elevation_deg, light.azimuth_deg)).multiplyScalar(10);
        group.add(directional);
    }
    scene.add(group);

    return group;
}

/**
 * Create a viewer inside `container`.
 * @param {HTMLElement} container
 * @param {{ mode?: 'preview', camera?: object, lighting?: object, background?: object, onState?: (state: string, detail?: string) => void }} config
 */
export function createViewer(container, config = {}) {
    if (config.mode === 'benchmark') return createBenchmarkViewer(container, config);

    const cameraPreset = { ...DEFAULT_CAMERA, ...config.camera };
    const lighting = { ...DEFAULT_LIGHTING, ...config.lighting };
    const background = { ...DEFAULT_BACKGROUND, ...config.background };
    const onState = config.onState ?? (() => {});

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.autoClear = false;
    renderer.domElement.classList.add('viewer-canvas');
    container.prepend(renderer.domElement);

    const scene = new THREE.Scene();
    scene.background = tokenColor(background.color_token);
    addLights(scene, lighting);

    let pmrem = null;
    if (lighting.environment_map) {
        pmrem = new THREE.PMREMGenerator(renderer);
        const room = new RoomEnvironment();
        scene.environment = pmrem.fromScene(room, 0.04).texture;
        room.dispose?.();
    }

    const camera = new THREE.PerspectiveCamera(cameraPreset.fov, 1, 0.01, 1000);
    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.screenSpacePanning = true;

    const gizmo = new ViewHelper(camera, renderer.domElement);
    gizmo.location = { top: 8, right: 8, bottom: 0, left: null };
    let gizmoVisible = true;

    let model = null;
    let needsRender = true;
    let disposed = false;
    let loadToken = 0;
    let lastTimings = null;
    const timer = new THREE.Timer();

    const requestRender = () => (needsRender = true);
    controls.addEventListener('change', requestRender);

    const onPointerUp = (event) => {
        if (gizmoVisible && gizmo.handleClick(event)) requestRender();
    };
    renderer.domElement.addEventListener('pointerup', onPointerUp);

    const resize = () => {
        const width = container.clientWidth;
        const height = container.clientHeight;
        if (width === 0 || height === 0) return;
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        requestRender();
    };
    const observer = new ResizeObserver(resize);
    observer.observe(container);
    resize();

    // Render on demand: only when the view changed, the controls are settling, or the gizmo animates.
    renderer.setAnimationLoop((timestamp) => {
        timer.update(timestamp);
        const delta = timer.getDelta();
        const moving = controls.update(delta);
        if (gizmo.animating) {
            gizmo.update(delta);
            needsRender = true;
        }
        if (!needsRender && !moving) return;
        needsRender = false;
        renderer.clear();
        renderer.render(scene, camera);
        if (gizmoVisible) gizmo.render(renderer);
    });

    function frame() {
        if (!model) return;
        // Flush pending damping momentum first, so it cannot move the camera after the reset.
        controls.enableDamping = false;
        controls.update();
        const target = frameCamera(camera, model, cameraPreset);
        controls.target.copy(target);
        gizmo.center.copy(target);
        controls.update();
        controls.enableDamping = true;
        requestRender();
    }

    function clear() {
        if (!model) return;
        scene.remove(model);
        disposeObject(model);
        model = null;
        requestRender();
    }

    async function load(url) {
        const token = ++loadToken;
        clear();
        onState('loading');
        try {
            const result = await loadModel(url);
            if (disposed || token !== loadToken) {
                disposeObject(result.gltf.scene);
                return null;
            }
            model = result.gltf.scene;
            lastTimings = result.timings;
            scene.add(model);
            frame();
            onState('ready');

            return result;
        } catch (error) {
            if (!disposed && token === loadToken) onState('error', error.message);

            return null;
        }
    }

    function setGizmo(visible) {
        gizmoVisible = visible;
        requestRender();
    }

    function dispose() {
        if (disposed) return;
        disposed = true;
        renderer.setAnimationLoop(null);
        observer.disconnect();
        renderer.domElement.removeEventListener('pointerup', onPointerUp);
        controls.removeEventListener('change', requestRender);
        controls.dispose();
        gizmo.dispose();
        timer.dispose?.();
        clear();
        disposeObject(scene);
        scene.environment?.dispose();
        pmrem?.dispose();
        renderer.dispose();
        renderer.forceContextLoss();
        renderer.domElement.remove();
    }

    return {
        renderer,
        scene,
        camera,
        controls,
        get model() {
            return model;
        },
        load,
        clear,
        resetView: frame,
        setGizmo,
        get gizmoVisible() {
            return gizmoVisible;
        },
        /** Load-phase timings of the last model (fetch_ms, parse_ms, transfer_size). */
        get lastTimings() {
            return lastTimings;
        },
        dispose,
    };
}

/**
 * Benchmark mode (BENCHMARK.md §2): fixed renderer settings, fixed drawing buffer from the resolution preset
 * (CSS scales the canvas), no controls, no gizmo, no overlays, no animation loop. measure.js drives the frames.
 * @param {HTMLElement} container
 * @param {{ resolution?: {width: number, height: number}, camera?: object, lighting?: object, background?: object }} config
 */
export function createBenchmarkViewer(container, config = {}) {
    const resolution = { width: 1280, height: 720, ...config.resolution };
    const cameraPreset = { ...DEFAULT_CAMERA, ...config.camera };
    const lighting = { ...DEFAULT_LIGHTING, ...config.lighting };
    const background = { ...DEFAULT_BACKGROUND, ...config.background };

    const renderer = new THREE.WebGLRenderer({ antialias: false, powerPreference: 'high-performance', preserveDrawingBuffer: false });
    renderer.setPixelRatio(1);
    renderer.setSize(resolution.width, resolution.height, false); // drawing buffer in pixels; CSS sets the display size
    renderer.toneMapping = THREE.NoToneMapping;
    renderer.shadowMap.enabled = false;
    renderer.domElement.classList.add('benchmark-canvas-element');
    renderer.domElement.style.aspectRatio = `${resolution.width} / ${resolution.height}`;
    container.prepend(renderer.domElement);

    const scene = new THREE.Scene();
    scene.background = tokenColor(background.color_token);
    addLights(scene, lighting);

    let pmrem = null;
    if (lighting.environment_map) {
        pmrem = new THREE.PMREMGenerator(renderer);
        const room = new RoomEnvironment();
        scene.environment = pmrem.fromScene(room, 0.04).texture;
    }

    const camera = new THREE.PerspectiveCamera(cameraPreset.fov, resolution.width / resolution.height, 0.01, 1000);
    let model = null;

    return {
        mode: 'benchmark',
        renderer,
        scene,
        camera,
        resolution,
        get model() {
            return model;
        },
        /** Put a loaded object in the scene and auto-frame it once; the camera then stays fixed. */
        setModel(object) {
            if (model) scene.remove(model);
            model = object;
            if (object) {
                scene.add(object);
                frameCamera(camera, object, cameraPreset);
            }
        },
        clear() {
            if (!model) return;
            scene.remove(model);
            disposeObject(model);
            model = null;
        },
        render() {
            renderer.render(scene, camera);
        },
        dispose() {
            this.clear();
            disposeObject(scene);
            scene.environment?.dispose();
            pmrem?.dispose();
            renderer.dispose();
            renderer.forceContextLoss();
            renderer.domElement.remove();
        },
    };
}

/**
 * Wire a server-rendered viewer element (components/performance/viewer.blade.php):
 * reads data-src and the JSON config, connects the control bar, and shows loading/error states.
 */
export function mountViewerElement(element) {
    const stage = element.querySelector('[data-viewer-stage]');
    const config = JSON.parse(element.querySelector('script[data-viewer-config]')?.textContent || '{}');
    const status = element.querySelector('[data-viewer-status]');

    const viewer = createViewer(stage, {
        ...config,
        onState: (state, detail) => {
            element.dataset.state = state;
            if (status) status.textContent = state === 'error' ? `The model could not be shown: ${detail}` : '';
            element.toggleAttribute('aria-busy', state === 'loading');
            element.dispatchEvent(new CustomEvent('viewer:state', { detail: { state, message: detail } }));
        },
    });

    element.querySelector('[data-viewer-reset]')?.addEventListener('click', () => viewer.resetView());
    element.querySelector('[data-viewer-fullscreen]')?.addEventListener('click', () => {
        if (document.fullscreenElement) document.exitFullscreen();
        else element.requestFullscreen?.();
    });
    const gizmoButton = element.querySelector('[data-viewer-gizmo]');
    gizmoButton?.addEventListener('click', () => {
        viewer.setGizmo(!viewer.gizmoVisible);
        gizmoButton.setAttribute('aria-pressed', String(viewer.gizmoVisible));
    });

    element.viewer = viewer;
    if (element.dataset.src) viewer.load(element.dataset.src);

    return viewer;
}
