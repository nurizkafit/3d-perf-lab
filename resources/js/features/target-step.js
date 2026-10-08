// Target step (components/performance/target-step): copy a Target's three values, let the user adjust them.
// Dispatches "target-change" ({ target_id, name, max_load_s, target_fps, max_file_size_mb }) on every change.
export default () => ({
    targetId: '',
    values: {},
    changed: false,
    config: null,

    init() {
        this.config = JSON.parse(this.$root.querySelector('[data-target-config]').textContent);
        this.targetId = String(this.config.suggestedId ?? '');
        this.applyTarget();
    },

    applyTarget() {
        const target = this.config.targets.find((t) => String(t.target_id) === this.targetId);
        this.values = Object.fromEntries(Object.keys(this.config.fields).map((key) => [key, target?.[key] ?? '']));
        this.changed = false;
        this.emit();
    },

    emit() {
        const target = this.config.targets.find((t) => String(t.target_id) === this.targetId);
        this.$dispatch('target-change', { target_id: target?.target_id ?? null, name: target?.name ?? '', ...this.values });
    },
});
