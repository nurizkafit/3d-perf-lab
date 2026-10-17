// Crowd page (BENCHMARK.md §4.6, DESIGN.md §9.2): scenario (Per asset / Per tool), selection, look phase (the explore
// viewer, decision D64), then 2 Settings → 3 Target → 4 Device → Run. Three.js loads only when the viewer mounts.
import { apiFetch } from '../api.js';
import { crowdSeconds, formatDuration } from './run-estimate.js';

const MOTIONS = ['static', 'spin', 'physics'];
const toNumber = (value) => (value === '' || value === undefined || value === null ? null : Number(value));

const DRAFT_KEY = 'lab-draft:crowd';
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
    scenario: 'per_asset',
    name: '',
    assets: [],
    toolIds: [],
    toolNames: {},
    common: { objects: [], missing: [] },
    commonLoading: false,
    commonError: '',
    choices: {}, // objectId → { included: bool, assets: { toolId: assetId } }
    motions: [...MOTIONS],
    repetitions: 1,
    cap: 1024,
    presets: { resolution: '', camera: '', lighting: '', background: '' },
    target: null,
    running: false,
    error: '',
    explorer: null,
    mountToken: 0,

    init() {
        this.config = JSON.parse(document.getElementById('lab-config').textContent);
        this.name = this.$root.querySelector('[name="name"]').value;
        this.presets = { ...this.config.defaults };
        this.cap = this.config.defaultCap;
        this.$root.querySelectorAll('[data-tool-option]').forEach((input) => (this.toolNames[input.value] = input.dataset.toolName));
        const draft = readDraft();
        if (draft) this.restoreDraft(draft);
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (link && new URL(link.href, location.href).pathname === '/device') this.saveDraft();
        }, true);
        this.$watch('presets', () => this.mountExplorer());
        this.$nextTick(() => this.mountExplorer());
    },

    destroy() {
        this.unmountExplorer();
    },

    saveDraft() {
        const draft = { scenario: this.scenario, name: this.name, assets: this.assets, toolIds: this.toolIds, choices: this.choices, motions: this.motions, repetitions: this.repetitions, cap: this.cap, presets: this.presets };
        try {
            sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
        } catch {
            // Storage unavailable: the page starts empty.
        }
    },

    restoreDraft(draft) {
        ['scenario', 'name', 'assets', 'toolIds', 'motions', 'repetitions', 'cap'].forEach((key) => draft[key] !== undefined && (this[key] = draft[key]));
        this.presets = { ...this.presets, ...draft.presets };
        if (this.scenario === 'per_tool' && this.toolIds.length >= 2) this.loadCommon(draft.choices);
    },

    // ---------- Selection ----------

    setScenario(value) {
        this.scenario = value;
        this.error = '';
        this.mountExplorer();
    },

    openSelector() {
        window.dispatchEvent(new CustomEvent('open-asset-selector', { detail: { selected: this.assets } }));
    },

    onSelected(event) {
        this.assets = event.detail.assets;
        this.mountExplorer();
    },

    remove(index) {
        this.assets = this.assets.filter((_, i) => i !== index);
        this.mountExplorer();
    },

    move(index, step) {
        const target = index + step;
        if (target < 0 || target >= this.assets.length) return;
        const next = [...this.assets];
        [next[index], next[target]] = [next[target], next[index]];
        this.assets = next;
        this.mountExplorer();
    },

    toggleTool(id, checked) {
        id = Number(id);
        this.toolIds = checked ? [...this.toolIds.filter((t) => t !== id), id] : this.toolIds.filter((t) => t !== id);
        this.loadCommon();
    },

    /** Objects present in every selected tool (PRD.md §8.3): each ticked by default, newest asset per tool. */
    async loadCommon(previous = null) {
        this.common = { objects: [], missing: [] };
        this.commonError = '';
        if (this.toolIds.length < 2) {
            this.choices = {};
            this.mountExplorer();

            return;
        }
        this.commonLoading = true;
        try {
            const body = await apiFetch(`${this.config.endpoints.commonObjects}?tools=${this.toolIds.join(',')}`);
            const choices = {};
            for (const object of body.objects) {
                const old = (previous ?? this.choices)[object.id];
                choices[object.id] = {
                    included: old ? old.included : true,
                    assets: Object.fromEntries(this.toolIds.map((toolId) => {
                        const candidates = object.assets[toolId] ?? [];
                        const kept = old?.assets?.[toolId];

                        return [toolId, candidates.some((a) => a.id === kept) ? kept : candidates[0]?.id];
                    })),
                };
            }
            // Choices first: the table binds to them.
            this.choices = choices;
            this.common = body;
        } catch (error) {
            this.commonError = error.message;
        } finally {
            this.commonLoading = false;
            this.mountExplorer();
        }
    },

    get includedObjects() {
        return this.common.objects.filter((object) => this.choices[object.id]?.included);
    },

    candidate(object, toolId) {
        return (object.assets[toolId] ?? []).find((a) => a.id === Number(this.choices[object.id]?.assets?.[toolId]));
    },

    /** Crowd groups in selection order: one per asset (Per asset) or one per tool with its chosen assets (Per tool). */
    get groups() {
        if (this.scenario === 'per_asset') {
            return this.assets.slice(0, this.config.maxAssets).map((asset) => ({ label: asset.name, assets: [{ url: asset.file_url }] }));
        }
        if (this.toolIds.length < 2 || this.includedObjects.length === 0) return [];

        return this.toolIds.map((toolId) => ({
            label: this.toolNames[toolId] ?? 'Tool',
            assets: this.includedObjects.slice(0, this.config.maxObjects).map((object) => ({ url: this.candidate(object, toolId)?.file_url })).filter((a) => a.url),
        }));
    },

    // ---------- Look phase (not measured) ----------

    unmountExplorer() {
        this.mountToken++;
        this.explorer?.dispose();
        this.explorer = null;
    },

    async mountExplorer() {
        this.unmountExplorer();
        const token = this.mountToken;
        await this.$nextTick();
        const groups = this.groups;
        if (groups.length === 0) return;
        const { mountCrowdExplorer } = await import('../three/crowd-viewer.js');
        if (token !== this.mountToken) return;
        const values = (type) => this.config.presetValues[this.presets[type]] ?? null;
        this.explorer = mountCrowdExplorer(this.$refs.look, {
            groups, lookCopies: this.config.lookCopies, camera: values('camera'), lighting: values('lighting'), background: values('background'),
        });
    },

    // ---------- Settings ----------

    toggleMotion(kind, checked) {
        const next = checked ? [...new Set([...this.motions, kind])] : this.motions.filter((m) => m !== kind);
        if (next.length === 0) return; // at least one stays selected (D51)
        this.motions = MOTIONS.filter((m) => next.includes(m));
    },

    onTarget(event) {
        this.target = event.detail;
    },

    get objectsPerGroup() {
        return this.scenario === 'per_tool' ? Math.max(1, this.includedObjects.length) : 1;
    },

    get estimate() {
        const seconds = crowdSeconds(
            { motions: this.motions, repetitions: this.repetitions, cap: this.cap },
            this.scenario === 'per_tool' ? this.toolIds.length : this.assets.length,
            this.objectsPerGroup,
            { loadAllowanceS: this.config.loadAllowanceS, coolDownS: this.config.coolDownS, probeBuildS: this.config.probeBuildS, typicalBinaryProbes: this.config.typicalBinaryProbes },
        );

        return `${formatDuration(seconds.typical)}, at most ${formatDuration(seconds.max).replace('about ', '')}`;
    },

    get capCopies() {
        return Math.max(1, Math.floor((Number(this.cap) || 0) / this.objectsPerGroup));
    },

    // ---------- Run ----------

    get blocker() {
        if (this.scenario === 'per_asset') {
            if (this.assets.length < 1) return 'Select 1 to 8 assets.';
            if (this.assets.length > this.config.maxAssets) return `Select at most ${this.config.maxAssets} assets.`;
        } else {
            if (this.toolIds.length < 2) return 'Select at least two authoring tools.';
            if (this.includedObjects.length === 0) return 'Include at least one object that exists in every selected tool.';
            if (this.includedObjects.length > this.config.maxObjects) return `Include at most ${this.config.maxObjects} objects.`;
        }
        if (this.motions.length === 0) return 'Select at least one motion scenario.';
        if (!(this.repetitions >= 1 && this.repetitions <= 3)) return 'Repetitions must be 1 to 3.';
        if (!(this.cap >= 1 && this.cap <= this.config.maxCap)) return `The cap must be 1 to ${this.config.maxCap.toLocaleString()} instances.`;
        if (!this.target?.target_id) return 'Select a Target.';
        if (!this.config.deviceClass) return 'Confirm the device class of this device.';

        return null;
    },

    async run() {
        if (this.blocker || this.running) return;
        this.running = true;
        this.error = '';
        const selection = this.scenario === 'per_asset'
            ? { asset_ids: this.assets.map((asset) => asset.id) }
            : {
                tool_ids: this.toolIds,
                objects: this.includedObjects.map((object) => ({ object_id: object.id, assets: Object.fromEntries(this.toolIds.map((toolId) => [toolId, Number(this.choices[object.id].assets[toolId])])) })),
            };
        try {
            const test = await apiFetch(this.config.endpoints.tests, {
                method: 'POST',
                json: {
                    name: this.name,
                    method: 'crowd',
                    scenario: this.scenario,
                    ...selection,
                    configuration: {
                        motions: this.motions,
                        repetitions: toNumber(this.repetitions),
                        cap: toNumber(this.cap),
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
            this.unmountExplorer(); // free the WebGL context before benchmark mode
            const { collectContext } = await import('../three/environment.js');
            const run = await apiFetch(this.config.endpoints.runs.replace('__TEST__', test.id), { method: 'POST', json: { environment: await collectContext() } });
            location.assign(run.url);
        } catch (error) {
            this.error = error.message;
            this.running = false;
        }
    },
});
