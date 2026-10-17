// Crowd explore viewer (BENCHMARK.md §4.6, decision D64): one asset or tool set, a motion scenario, and a copy count.
// Not measured. Used as the look phase on the Crowd page and as the explore viewer on the results page.
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { addLights, DEFAULT_BACKGROUND, DEFAULT_CAMERA, DEFAULT_LIGHTING, frameBox, tokenColor } from './viewer.js';
import { disposeObject } from './loaders.js';
import { createCrowd, ensureHulls, loadTemplates } from './crowd.js';

/** One look shake lasts 3 s of simulation time (same as the Side-by-Side look phase). */
const LOOK_SHAKE_STEPS = 180;

/**
 * @param {HTMLElement} container  element with a `[data-crowd-stage]`; gets data-state (loading | ready | error)
 * @param {{ camera?: object, lighting?: object, background?: object }} config
 */
export function createCrowdViewer(container, config = {}) {
    const cameraPreset = { ...DEFAULT_CAMERA, ...config.camera };
    const lighting = { ...DEFAULT_LIGHTING, ...config.lighting };
    const background = { ...DEFAULT_BACKGROUND, ...config.background };
    const stage = container.querySelector('[data-crowd-stage]') ?? container;

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.domElement.classList.add('viewer-canvas');
    renderer.domElement.tabIndex = 0;
    renderer.domElement.setAttribute('aria-label', 'Crowd: 3D view. Arrow keys pan; Shift + arrow keys orbit.');
    stage.prepend(renderer.domElement);

    const scene = new THREE.Scene();
    scene.background = tokenColor(background.color_token);
    addLights(scene, lighting);
    const camera = new THREE.PerspectiveCamera(cameraPreset.fov, 16 / 9, 0.01, 1000);
    const controls = new OrbitControls(camera, renderer.domElement);
    controls.screenSpacePanning = true;
    controls.listenToKeyEvents(renderer.domElement);

    let loaded = null;
    let crowd = null;
    let copies = 16;
    let kind = 'static';
    let loadToken = 0;
    let needsRender = true;
    let disposed = false;
    let start = performance.now();
    let frameId = null;
    controls.addEventListener('change', () => (needsRender = true));

    const setState = (state, message = '') => {
        container.dataset.state = state;
        container.toggleAttribute('aria-busy', state === 'loading');
        const error = container.querySelector('[data-crowd-error]');
        if (error) error.textContent = state === 'error' ? `The models could not be shown: ${message}` : '';
    };

    const resize = () => {
        const width = stage.clientWidth;
        const height = stage.clientHeight;
        if (!width || !height) return;
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        needsRender = true;
    };
    const observer = new ResizeObserver(resize);
    observer.observe(stage);
    resize();

    function frame() {
        if (!crowd) return;
        controls.target.copy(frameBox(camera, crowd.bounds, cameraPreset));
        controls.update();
        needsRender = true;
    }

    function rebuild({ reframe = true } = {}) {
        if (crowd) {
            scene.remove(crowd.group);
            crowd.dispose();
            crowd = null;
        }
        if (!loaded) return;
        if (kind === 'physics') ensureHulls(loaded.templates);
        crowd = createCrowd({ templates: loaded.templates, copies, kind, diameter: loaded.diameter, lineColor: tokenColor('neutral-800'), shakeSteps: LOOK_SHAKE_STEPS });
        scene.add(crowd.group);
        start = performance.now();
        if (reframe) frame();
        needsRender = true;
    }

    const loop = () => {
        frameId = requestAnimationFrame(loop);
        const moving = crowd && kind !== 'static';
        if (!needsRender && !moving) return;
        needsRender = false;
        crowd?.motion.update(performance.now() - start);
        renderer.render(scene, camera);
    };
    frameId = requestAnimationFrame(loop);

    function releaseTemplates() {
        loaded?.templates.forEach((template) => disposeObject(template.pivot));
        loaded = null;
    }

    return {
        /** Show the crowd of one asset or tool set (its assets in object order). */
        async setGroup(assets) {
            const token = ++loadToken;
            setState('loading');
            try {
                const next = await loadTemplates(assets);
                if (disposed || token !== loadToken) {
                    next.templates.forEach((template) => disposeObject(template.pivot));

                    return;
                }
                if (crowd) {
                    scene.remove(crowd.group);
                    crowd.dispose();
                    crowd = null;
                }
                releaseTemplates();
                loaded = next;
                rebuild();
                setState('ready');
            } catch (error) {
                if (!disposed && token === loadToken) setState('error', error.message);
            }
        },
        setCopies(value) {
            copies = Math.max(1, Math.round(value));
            rebuild();
        },
        setMotion(value) {
            kind = value;
            rebuild();
        },
        shake() {
            crowd?.motion.shake();
        },
        resetView: frame,
        get instances() {
            return crowd?.count ?? 0;
        },
        dispose() {
            if (disposed) return;
            disposed = true;
            cancelAnimationFrame(frameId);
            observer.disconnect();
            if (crowd) crowd.dispose();
            releaseTemplates();
            controls.stopListenToKeyEvents?.();
            controls.dispose();
            disposeObject(scene);
            renderer.dispose();
            renderer.forceContextLoss();
            renderer.domElement.remove();
        },
    };
}

/**
 * Wire an explore element (results page and Crowd page): `[data-crowd-group]` select (index into config.groups),
 * `[data-crowd-motion]` radios, `[data-crowd-copies]` range + `[data-crowd-copies-value]` output, `[data-crowd-shake]`,
 * `[data-crowd-reset]`, `[data-crowd-fullscreen]`. config.groups[i] = { label, assets: [{ url }], defaults: { motion: copies } }.
 */
export function mountCrowdExplorer(root, config) {
    const frameElement = root.querySelector('[data-crowd-frame]') ?? root;
    const viewer = createCrowdViewer(frameElement, config);
    const groupSelect = root.querySelector('[data-crowd-group]');
    const range = root.querySelector('[data-crowd-copies]');
    const output = root.querySelector('[data-crowd-copies-value]');
    const shake = root.querySelector('[data-crowd-shake]');
    let groupIndex = 0;
    let motion = root.querySelector('[data-crowd-motion]:checked')?.value ?? 'static';
    // The page may mount a new explorer on the same controls: every listener goes with dispose().
    const listeners = new AbortController();
    const signal = listeners.signal;

    const showCopies = () => {
        const objects = config.groups[groupIndex]?.assets.length ?? 1;
        if (output) output.textContent = `${range.value} ${Number(range.value) === 1 ? 'copy' : 'copies'} (${Number(range.value) * objects} instances)`;
    };
    // The default copy count is the group's smooth_copies for the motion (results), else config.lookCopies.
    const applyDefault = () => {
        const value = config.groups[groupIndex]?.defaults?.[motion] ?? config.lookCopies ?? 16;
        range.value = String(Math.max(1, Math.min(Number(range.max), value || 1)));
        showCopies();
        viewer.setCopies(Number(range.value));
    };

    groupSelect?.addEventListener('change', async () => {
        groupIndex = Number(groupSelect.value);
        await viewer.setGroup(config.groups[groupIndex].assets);
        applyDefault();
    }, { signal });
    root.querySelectorAll('[data-crowd-motion]').forEach((input) => input.addEventListener('change', () => {
        if (!input.checked) return;
        motion = input.value;
        if (shake) shake.disabled = motion !== 'physics';
        viewer.setMotion(motion);
        applyDefault();
    }, { signal }));
    range?.addEventListener('input', showCopies, { signal });
    range?.addEventListener('change', () => viewer.setCopies(Number(range.value)), { signal });
    shake?.addEventListener('click', () => viewer.shake(), { signal });
    root.querySelector('[data-crowd-reset]')?.addEventListener('click', () => viewer.resetView(), { signal });
    root.querySelector('[data-crowd-fullscreen]')?.addEventListener('click', () => (document.fullscreenElement ? document.exitFullscreen() : root.requestFullscreen?.()), { signal });
    if (shake) shake.disabled = motion !== 'physics';

    if (groupSelect) groupSelect.value = '0';
    if (config.groups.length) {
        viewer.setMotion(motion);
        viewer.setGroup(config.groups[0].assets).then(applyDefault);
    }

    return {
        viewer,
        dispose() {
            listeners.abort();
            viewer.dispose();
        },
    };
}

/** Results page: the explore viewer from its JSON config. */
export function mountCrowdExplorerElement(root) {
    return mountCrowdExplorer(root, JSON.parse(root.querySelector('script[data-crowd-config]').textContent));
}
