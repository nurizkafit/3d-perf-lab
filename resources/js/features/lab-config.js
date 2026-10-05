// Lab configuration page (DESIGN.md §9.2): assets → settings → budget → device → Run.
// Run creates the test (POST /api/tests), starts a run on this device, and opens benchmark mode.
import { apiFetch } from '../api.js';

/** FormData → nested object for keys like configuration[repetitions] and budget[items][x][limit]. */
function formToObject(form) {
    const data = {};
    for (const [key, value] of new FormData(form)) {
        const path = key.replace(/\]/g, '').split('[');
        let node = data;
        path.forEach((part, i) => {
            if (i === path.length - 1) node[part] = value;
            else node = node[part] ??= {};
        });
    }

    return data;
}

const toNumber = (value) => (value === '' || value === undefined ? null : Number(value));

export default () => ({
    config: {},
    assets: [],
    scenario: 'repeated',
    steps: '',
    running: false,
    error: '',

    init() {
        this.config = JSON.parse(document.getElementById('lab-config').textContent);
        this.steps = (this.config.defaultSteps ?? []).join(', ');
    },

    applyStepPreset(id) {
        const preset = this.config.stepPresets.find((p) => String(p.id) === String(id));
        if (preset) this.steps = preset.steps.join(', ');
    },

    get parsedSteps() {
        return this.steps.split(',').map((s) => s.trim()).filter(Boolean).map(Number);
    },

    /** Same rules as the server (CreateTestRequest): ascending, distinct, 1–500. */
    get stepsError() {
        if (this.config.method !== 'stress') return null;
        const steps = this.parsedSteps;
        if (steps.length === 0) return 'Enter at least one instance count.';
        if (steps.some((n) => !Number.isInteger(n) || n < 1 || n > this.config.maxInstances)) return `Each count must be a whole number from 1 to ${this.config.maxInstances}.`;
        if (steps.some((n, i) => i > 0 && n <= steps[i - 1])) return 'List the counts in ascending order without repeats.';
        if (steps.length > 12) return 'Use at most 12 steps.';

        return null;
    },

    openSelector() {
        window.dispatchEvent(new CustomEvent('open-asset-selector', { detail: { selected: this.assets } }));
    },

    onSelected(event) {
        this.assets = event.detail.assets;
        this.error = '';
    },

    remove(index) {
        this.assets.splice(index, 1);
    },

    move(index, step) {
        const target = index + step;
        if (target < 0 || target >= this.assets.length) return;
        [this.assets[index], this.assets[target]] = [this.assets[target], this.assets[index]];
    },

    /** The reason the run cannot start, or null (DESIGN.md §9.2: reason next to the disabled button). */
    get blocker() {
        if (this.assets.length === 0) return 'Select at least one asset.';
        if (this.assets.length > this.config.maxAssets) return `Select at most ${this.config.maxAssets} assets.`;
        if (this.config.method === 'stress' && this.scenario === 'repeated' && this.assets.length !== 1) return 'A repeated-asset test uses exactly one asset. Remove the others or switch to Mixed.';
        if (this.scenario === 'mixed' && this.config.method !== 'comparison') {
            if (this.assets.length < 2) return 'A mixed scenario needs at least two assets.';
            const tools = [...new Set(this.assets.map((asset) => asset.tool))];
            // Same-tool rule (PRD.md §6.2); the server rejects it too.
            if (tools.length > 1) return `All assets in a mixed scenario must come from one authoring tool. Selected: ${tools.join(', ')}.`;
        }
        if (this.stepsError) return this.stepsError;
        if (!this.config.deviceClass) return 'Confirm the device class of this device.';

        return null;
    },

    async run() {
        if (this.blocker || this.running) return;
        this.running = true;
        this.error = '';
        const form = formToObject(this.$refs.form);
        const items = Object.fromEntries(Object.entries(form.budget?.items ?? {}).map(([key, item]) => [key, { enabled: item.enabled === '1', limit: toNumber(item.limit) }]));
        const c = form.configuration;

        try {
            const test = await apiFetch(this.config.endpoints.tests, {
                method: 'POST',
                json: {
                    name: form.name,
                    method: this.config.method,
                    scenario: this.config.method === 'comparison' ? null : this.scenario,
                    asset_ids: this.assets.map((asset) => asset.id),
                    configuration: {
                        repetitions: toNumber(c.repetitions), warmup_s: toNumber(c.warmup_s), measurement_s: toNumber(c.measurement_s),
                        animation: c.animation === '1',
                        resolution_id: toNumber(c.resolution_id), camera_id: toNumber(c.camera_id), lighting_id: toNumber(c.lighting_id), background_id: toNumber(c.background_id),
                        ...(this.config.method === 'stress' ? { steps: this.parsedSteps } : {}),
                    },
                    budget: { preset_id: toNumber(form.budget?.preset_id), items },
                },
            });
            const { collectContext } = await import('../three/environment.js');
            const run = await apiFetch(this.config.endpoints.runs.replace('__TEST__', test.id), { method: 'POST', json: { environment: await collectContext() } });
            location.assign(run.url);
        } catch (error) {
            this.error = error.message;
            this.running = false;
        }
    },
});
