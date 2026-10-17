// Side-by-Side page (BENCHMARK.md §4.3, DESIGN.md §9.2): select 2–4 assets, look at them in linked panes, then
// configure: 1 Assets → 2 Settings → 3 Target → 4 Device → Run. Run creates the test (POST /api/tests), starts a
// run on this device, and opens benchmark mode. Three.js loads only when the panes mount (dynamic import).
import { apiFetch } from '../api.js';
import { formatDuration, sideBySideSeconds } from './run-estimate.js';

const MOTIONS = ['static', 'spin', 'physics'];
const toNumber = (value) => (value === '' || value === undefined || value === null ? null : Number(value));

// Draft kept while the user confirms the device class on /device, which reloads this page. sessionStorage: this tab
// only, a convenience; the page works without it.
const DRAFT_KEY = 'lab-draft:side_by_side';
const readDraft = () => {
    try {
        const raw = sessionStorage.getItem(DRAFT_KEY);
        sessionStorage.removeItem(DRAFT_KEY);

        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
};

export default () => ({
    config: {},
    assets: [],
    name: '',
    motions: [...MOTIONS],
    repetitions: 3,
    warmup: 2,
    measurement: 10,
    animation: false,
    presets: { resolution: '', camera: '', lighting: '', background: '' },
    target: null,
    running: false,
    error: '',
    panes: null,
    unwire: null,
    mountToken: 0,

    init() {
        this.config = JSON.parse(document.getElementById('lab-config').textContent);
        this.name = this.$root.querySelector('[name="name"]').value;
        this.presets = { ...this.config.defaults };
        const draft = readDraft();
        if (draft) this.restoreDraft(draft);
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (link && new URL(link.href, location.href).pathname === '/device') this.saveDraft();
        }, true);
        this.$watch('presets', () => this.mountPanes());
        if (this.assets.length) this.$nextTick(() => this.mountPanes());
    },

    destroy() {
        this.unmountPanes();
    },

    saveDraft() {
        const target = this.$root.querySelector('[x-data="targetStep"]');
        const step = target ? window.Alpine.$data(target) : null;
        const draft = {
            assets: this.assets, name: this.name, motions: this.motions, repetitions: this.repetitions, warmup: this.warmup,
            measurement: this.measurement, animation: this.animation, presets: this.presets,
            // Keep the Target only when the user changed it; otherwise the Target suggested for the confirmed class applies.
            target: step && (step.changed || step.targetId !== String(step.config.suggestedId ?? '')) ? { targetId: step.targetId, values: step.values } : null,
        };
        try {
            sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
        } catch {
            // Storage unavailable: the page starts empty, as before.
        }
    },

    restoreDraft(draft) {
        ['assets', 'name', 'motions', 'repetitions', 'warmup', 'measurement', 'animation'].forEach((key) => draft[key] !== undefined && (this[key] = draft[key]));
        this.presets = { ...this.presets, ...draft.presets };
        if (!draft.target) return;
        // The Target step initialises after this component; restore once it has.
        this.$nextTick(() => {
            const element = this.$root.querySelector('[x-data="targetStep"]');
            const step = element ? window.Alpine.$data(element) : null;
            if (!step) return;
            step.targetId = draft.target.targetId;
            step.values = draft.target.values;
            step.changed = true;
            step.emit();
        });
    },

    // ---------- Assets ----------

    openSelector() {
        window.dispatchEvent(new CustomEvent('open-asset-selector', { detail: { selected: this.assets } }));
    },

    onSelected(event) {
        this.setAssets(event.detail.assets);
        this.error = '';
    },

    remove(index) {
        this.setAssets(this.assets.filter((_, i) => i !== index));
    },

    move(index, step) {
        const target = index + step;
        if (target < 0 || target >= this.assets.length) return;
        const next = [...this.assets];
        [next[index], next[target]] = [next[target], next[index]];
        this.setAssets(next);
    },

    /** The panes follow the selection order; they are rebuilt after every change. */
    setAssets(assets) {
        this.unmountPanes();
        this.assets = assets;
        this.$nextTick(() => this.mountPanes());
    },

    // ---------- Look phase (not measured) ----------

    unmountPanes() {
        this.mountToken++;
        this.unwire?.();
        this.panes?.dispose();
        this.panes = null;
        this.unwire = null;
    },

    async mountPanes() {
        this.unmountPanes();
        const token = this.mountToken;
        const visible = this.assets.slice(0, this.config.maxAssets);
        if (visible.length === 0) return;
        const { createLinkedPanes, wireToolbar } = await import('../three/panes.js');
        if (token !== this.mountToken) return;
        const values = (type) => this.config.presetValues[this.presets[type]] ?? null;
        const root = this.$refs.look;
        this.panes = createLinkedPanes(root, {
            assets: visible.map((asset) => ({ id: asset.id, name: asset.name, url: asset.file_url })),
            camera: values('camera'),
            lighting: values('lighting'),
            background: values('background'),
        });
        this.unwire = wireToolbar(root, this.panes);
    },

    // ---------- Settings ----------

    toggleMotion(kind, checked) {
        const next = checked ? [...new Set([...this.motions, kind])] : this.motions.filter((m) => m !== kind);
        // At least one motion scenario stays selected (BENCHMARK.md §4.2, decision D51).
        if (next.length === 0) return;
        this.motions = MOTIONS.filter((m) => next.includes(m));
    },

    onTarget(event) {
        this.target = event.detail;
    },

    get estimateSeconds() {
        return sideBySideSeconds(
            { motions: this.motions, repetitions: this.repetitions, warmup_s: this.warmup, measurement_s: this.measurement },
            this.assets.length,
            { loadAllowanceS: this.config.loadAllowanceS, physicsMinWarmupS: this.config.physicsMinWarmupS, coolDownS: this.config.coolDownS },
        );
    },

    get estimate() {
        return formatDuration(this.estimateSeconds);
    },

    // ---------- Run ----------

    /** The reason the run cannot start, or null (DESIGN.md §9.2: reason next to the disabled button). */
    get blocker() {
        if (this.assets.length < this.config.minAssets) return `Select ${this.config.minAssets} to ${this.config.maxAssets} assets.`;
        if (this.assets.length > this.config.maxAssets) return `Select at most ${this.config.maxAssets} assets.`;
        if (this.motions.length === 0) return 'Select at least one motion scenario.';
        if (!(this.repetitions >= 1 && this.repetitions <= 5)) return 'Repetitions must be 1 to 5.';
        if (!(this.warmup >= 1 && this.warmup <= 10)) return 'Warm-up must be 1 to 10 s.';
        if (!(this.measurement >= 5 && this.measurement <= 30)) return 'Measurement must be 5 to 30 s.';
        if (!this.target?.target_id) return 'Select a Target.';
        if (!this.config.deviceClass) return 'Confirm the device class of this device.';

        return null;
    },

    async run() {
        if (this.blocker || this.running) return;
        this.running = true;
        this.error = '';
        try {
            const test = await apiFetch(this.config.endpoints.tests, {
                method: 'POST',
                json: {
                    name: this.name,
                    method: 'side_by_side',
                    asset_ids: this.assets.map((asset) => asset.id),
                    configuration: {
                        motions: this.motions,
                        repetitions: toNumber(this.repetitions),
                        warmup_s: toNumber(this.warmup),
                        measurement_s: toNumber(this.measurement),
                        animation: this.animation,
                        resolution_id: toNumber(this.presets.resolution),
                        camera_id: toNumber(this.presets.camera),
                        lighting_id: toNumber(this.presets.lighting),
                        background_id: toNumber(this.presets.background),
                    },
                    target: {
                        target_id: this.target.target_id,
                        max_load_s: toNumber(this.target.max_load_s),
                        target_fps: toNumber(this.target.target_fps),
                        max_file_size_mb: toNumber(this.target.max_file_size_mb),
                    },
                },
            });
            this.unmountPanes(); // free the WebGL contexts before benchmark mode
            const { collectContext } = await import('../three/environment.js');
            const run = await apiFetch(this.config.endpoints.runs.replace('__TEST__', test.id), { method: 'POST', json: { environment: await collectContext() } });
            location.assign(run.url);
        } catch (error) {
            this.error = error.message;
            this.running = false;
        }
    },
});
