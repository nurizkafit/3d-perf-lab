// Linked panes (BENCHMARK.md §4.3, look phase): one pane per asset, one shared camera, one motion switch for all
// panes. Not measured. Used on the Side-by-Side page and again on its results page.
//
// Each pane has its own renderer and scene; all panes render with the same camera. Orbiting, panning, or zooming
// in one pane moves every pane. Assets keep their size: a small asset looks small next to a large one.
// Loaded only through dynamic import (with three and cannon-es), never in the entry bundle.
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { addLights, DEFAULT_BACKGROUND, DEFAULT_CAMERA, DEFAULT_LIGHTING, frameBox, tokenColor } from './viewer.js';
import { disposeObject, loadModel } from './loaders.js';
// Look-phase physics uses the same seed as the measurement, so the drop looks the same as in the run.
import { buildHull, centredPivot, createMotion, PHYSICS, PHYSICS_SEED, scaleUnit } from './motion.js';

/** A shake in the look phase lasts 3 s of simulation time; the Shake action starts a new one. */
const LOOK_SHAKE_STEPS = 180;

/**
 * Mount linked panes on server- or Alpine-rendered pane elements.
 * Pane element contract: `[data-pane="<asset id>"]` holds `[data-pane-frame]` (gets data-state) with a
 * `[data-pane-stage]` inside, and an optional `[data-pane-error]` for the error text.
 *
 * @param {HTMLElement} root  element that contains the panes; fullscreen applies to it
 * @param {{ assets: {id: number, name: string, url: string}[], camera?: object, lighting?: object, background?: object }} config
 */
export function createLinkedPanes(root, config) {
    const cameraPreset = { ...DEFAULT_CAMERA, ...config.camera };
    const lighting = { ...DEFAULT_LIGHTING, ...config.lighting };
    const background = { ...DEFAULT_BACKGROUND, ...config.background };

    const camera = new THREE.PerspectiveCamera(cameraPreset.fov, 16 / 9, 0.01, 1000);
    const target = new THREE.Vector3(); // shared by every pane's controls
    const panes = [];
    let motionKind = 'static';
    let needsRender = true;
    let disposed = false;
    let start = performance.now();
    let frameId = null;

    const requestRender = () => (needsRender = true);

    for (const asset of config.assets) {
        const element = root.querySelector(`[data-pane="${asset.id}"]`);
        if (!element) continue;
        const frame = element.querySelector('[data-pane-frame]') ?? element;
        const stage = element.querySelector('[data-pane-stage]');

        const renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.domElement.classList.add('viewer-canvas');
        renderer.domElement.tabIndex = 0;
        renderer.domElement.setAttribute('aria-label', `${asset.name}: 3D view. Arrow keys pan; Shift + arrow keys orbit.`);
        stage.prepend(renderer.domElement);

        const scene = new THREE.Scene();
        scene.background = tokenColor(background.color_token);
        addLights(scene, lighting);

        const controls = new OrbitControls(camera, renderer.domElement);
        controls.target = target;
        controls.screenSpacePanning = true;
        controls.listenToKeyEvents(renderer.domElement);
        controls.addEventListener('change', requestRender);

        const pane = { asset, element, frame, stage, renderer, scene, controls, group: null, pivot: null, diameter: 1, hull: null, motion: null };
        panes.push(pane);
        setState(pane, 'loading');
    }

    // All panes have the same CSS size, so one aspect ratio serves the shared camera.
    const resize = () => {
        for (const pane of panes) {
            const width = pane.stage.clientWidth;
            const height = pane.stage.clientHeight;
            if (width === 0 || height === 0) continue;
            pane.renderer.setSize(width, height, false);
            camera.aspect = width / height;
        }
        camera.updateProjectionMatrix();
        requestRender();
    };
    const observer = new ResizeObserver(resize);
    panes.forEach((pane) => observer.observe(pane.stage));

    function setState(pane, state, message = '') {
        pane.frame.dataset.state = state;
        pane.frame.toggleAttribute('aria-busy', state === 'loading');
        const error = pane.element.querySelector('[data-pane-error]');
        if (error) error.textContent = state === 'error' ? `The model could not be shown: ${message}` : '';
    }

    /** Frame every pane with one camera: the largest asset (or Physics box) fits; the camera then stays put. */
    function frameAll() {
        const ready = panes.filter((pane) => pane.group);
        if (ready.length === 0) return;
        const box = new THREE.Box3();
        for (const pane of ready) {
            if (motionKind === 'physics') {
                const d = pane.diameter;
                const half = (PHYSICS.single.floor * d) / 2;
                box.union(new THREE.Box3(new THREE.Vector3(-half, 0, -half), new THREE.Vector3(half, PHYSICS.single.wall * d, half)));
            } else {
                box.union(new THREE.Box3().setFromObject(pane.pivot));
            }
        }
        target.copy(frameBox(camera, box, cameraPreset));
        panes.forEach((pane) => pane.controls.update());
        requestRender();
    }

    function applyMotion(pane) {
        pane.motion?.dispose();
        pane.motion = null;
        pane.pivot.position.set(0, 0, 0);
        pane.pivot.quaternion.identity();
        if (motionKind === 'physics' && !pane.hull) pane.hull = buildHull(pane.pivot);
        pane.motion = createMotion(motionKind, {
            group: pane.group,
            pivots: [pane.pivot],
            diameter: pane.diameter,
            hulls: pane.hull ? new Map([[pane.pivot, pane.hull]]) : null,
            layout: 'single',
            seed: PHYSICS_SEED,
            shakeSteps: LOOK_SHAKE_STEPS,
            lineColor: tokenColor('neutral-800'),
        });
    }

    async function load(pane) {
        try {
            const { gltf } = await loadModel(pane.asset.url);
            if (disposed) {
                disposeObject(gltf.scene);

                return;
            }
            pane.pivot = centredPivot(gltf.scene);
            pane.group = new THREE.Group();
            pane.group.add(pane.pivot);
            pane.scene.add(pane.group);
            pane.diameter = scaleUnit([pane.pivot]);
            applyMotion(pane);
            setState(pane, 'ready');
            frameAll();
        } catch (error) {
            if (!disposed) setState(pane, 'error', error.message);
        }
    }

    // One loop for all panes: continuous while something moves, otherwise only after a camera change.
    const loop = () => {
        frameId = requestAnimationFrame(loop);
        const moving = motionKind !== 'static' && panes.some((pane) => pane.motion);
        if (!needsRender && !moving) return;
        needsRender = false;
        const elapsed = performance.now() - start;
        for (const pane of panes) {
            if (!pane.group) continue;
            pane.motion?.update(elapsed);
            pane.renderer.render(pane.scene, camera);
        }
    };

    resize();
    panes.forEach(load);
    frameId = requestAnimationFrame(loop);

    return {
        get motion() {
            return motionKind;
        },
        /** Switch every pane to Static, Spin, or Physics. Physics drops the objects again. */
        setMotion(kind) {
            if (kind === motionKind) return;
            motionKind = kind;
            start = performance.now();
            panes.filter((pane) => pane.group).forEach(applyMotion);
            frameAll();
        },
        /** Physics only: shake every box again. */
        shake() {
            panes.forEach((pane) => pane.motion?.shake());
        },
        resetView: frameAll,
        toggleFullscreen() {
            if (document.fullscreenElement) document.exitFullscreen();
            else root.requestFullscreen?.();
        },
        dispose() {
            if (disposed) return;
            disposed = true;
            cancelAnimationFrame(frameId);
            observer.disconnect();
            for (const pane of panes) {
                pane.motion?.dispose();
                pane.controls.removeEventListener('change', requestRender);
                pane.controls.stopListenToKeyEvents?.();
                pane.controls.dispose();
                disposeObject(pane.scene);
                pane.renderer.dispose();
                pane.renderer.forceContextLoss();
                pane.renderer.domElement.remove();
            }
            panes.length = 0;
        },
    };
}

/**
 * Wire the toolbar of a `[data-linked-panes]` element (motion switch, Shake, Reset view, Fullscreen) to the panes.
 * The toolbar controls are plain HTML: `[data-panes-motion]` radios, `[data-panes-shake]`, `[data-panes-reset]`,
 * `[data-panes-fullscreen]`.
 */
export function wireToolbar(root, panes) {
    const shake = root.querySelector('[data-panes-shake]');
    const update = () => shake && (shake.disabled = panes.motion !== 'physics');
    const listeners = [];
    const on = (element, type, handler) => {
        element?.addEventListener(type, handler);
        listeners.push(() => element?.removeEventListener(type, handler));
    };

    root.querySelectorAll('[data-panes-motion]').forEach((input) => {
        if (input.checked) panes.setMotion(input.value);
        on(input, 'change', () => {
            if (!input.checked) return;
            panes.setMotion(input.value);
            update();
        });
    });
    on(shake, 'click', () => panes.shake());
    on(root.querySelector('[data-panes-reset]'), 'click', () => panes.resetView());
    on(root.querySelector('[data-panes-fullscreen]'), 'click', () => panes.toggleFullscreen());
    update();

    return () => listeners.forEach((off) => off());
}

/** Results page: mount the panes from the JSON config inside the element. */
export function mountLinkedPanesElement(root) {
    const config = JSON.parse(root.querySelector('script[data-panes-config]').textContent);
    const panes = createLinkedPanes(root, config);
    wireToolbar(root, panes);

    return panes;
}
