// Budget step (components/performance/budget-step): copy a preset's items, let the user adjust them.
export default () => ({
    presetId: '',
    items: {},
    changed: false,
    config: null,

    init() {
        this.config = JSON.parse(this.$root.querySelector('[data-budget-config]').textContent);
        this.presetId = String(this.config.suggestedId ?? '');
        this.applyPreset();
    },

    applyPreset() {
        const preset = this.config.presets.find((p) => String(p.id) === this.presetId);
        this.items = Object.fromEntries(Object.keys(this.config.items).map((key) => {
            const item = preset?.items?.[key] ?? { enabled: false, limit: null };

            return [key, { enabled: Boolean(item.enabled), limit: item.limit ?? '' }];
        }));
        this.changed = false;
    },
});
