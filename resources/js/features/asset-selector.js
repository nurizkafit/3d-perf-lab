// Asset Selector modal (PRD.md §8.2). Selection is kept by id across searches and filters;
// the order of selection is the order of the results later (BENCHMARK.md §4.2).
import { apiFetch } from '../api.js';

export default (max = 8) => ({
    max,
    query: '',
    tool: '',
    format: '',
    author: '',
    results: [],
    selected: [],
    loading: false,
    status: '',
    previewAsset: null,
    previewTimer: null,
    viewer: null,

    open(detail = {}) {
        this.selected = [...(detail.selected ?? [])];
        this.$root.showModal();
        this.search();
        this.$nextTick(() => this.$root.querySelector('#selector-q').focus());
    },

    onClose() {
        clearTimeout(this.previewTimer);
        this.viewer?.dispose();
        this.viewer = null;
        this.previewAsset = null;
    },

    async search() {
        this.loading = true;
        const params = new URLSearchParams({ q: this.query, tool: this.tool, format: this.format, author: this.author });
        try {
            const body = await apiFetch(`${this.$root.dataset.endpoint ?? '/api/assets'}?${params}`);
            this.results = body.assets;
            this.status = body.limited ? 'Showing the first 60 matches. Narrow the search to see more.' : `${body.assets.length} found`;
        } catch (error) {
            this.status = error.message;
        } finally {
            this.loading = false;
        }
    },

    isSelected(asset) {
        return this.selected.some((s) => s.id === asset.id);
    },

    toggle(asset) {
        if (this.isSelected(asset)) {
            this.selected = this.selected.filter((s) => s.id !== asset.id);
        } else if (this.selected.length >= this.max) {
            this.status = `A test can have at most ${this.max} assets.`;
        } else {
            this.selected = [...this.selected, asset];
        }
    },

    selectVisible() {
        for (const asset of this.results) {
            if (!this.isSelected(asset) && this.selected.length < this.max) this.selected.push(asset);
        }
        if (this.results.some((a) => !this.isSelected(a))) this.status = `A test can have at most ${this.max} assets.`;
    },

    clear() {
        this.selected = [];
    },

    focusOption(index) {
        const options = this.$root.querySelectorAll('[role="option"]');
        options[Math.max(0, Math.min(index, options.length - 1))]?.focus();
    },

    // Live preview of one asset in the modal (hover or focus), using the shared viewer in preview mode.
    schedulePreview(asset) {
        if (!window.matchMedia('(min-width: 64rem)').matches) return;
        clearTimeout(this.previewTimer);
        this.previewTimer = setTimeout(() => this.showPreview(asset), 250);
    },

    async showPreview(asset) {
        if (this.previewAsset?.id === asset.id || !this.$root.open) return;
        this.previewAsset = asset;
        const { createViewer } = await import('../three/viewer.js');
        // $refs does not resolve from the x-for row scope; look the stage up from the dialog.
        this.viewer ??= createViewer(this.$root.querySelector('[data-selector-preview]'), { mode: 'preview' });
        this.viewer.load(asset.file_url);
    },

    confirm() {
        window.dispatchEvent(new CustomEvent('assets-selected', { detail: { assets: this.selected } }));
        this.$root.close();
    },
});
