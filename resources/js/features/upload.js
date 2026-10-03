// Bulk upload metadata table (PRD.md §7.1). M1.3: behaviour on sample rows only; no requests.
// Rows come from <script type="application/json" id="upload-rows">.

const BULK_FIELDS = ['author', 'tool', 'hours'];
const FLASH_MS = 260; // matches --duration-slow

export default () => ({
    rows: [],
    bulk: { author: '', tool: '', hours: '' },
    // Rows skipped by the last bulk apply because they were edited by hand.
    skipped: [],
    lastTarget: null,
    message: '',

    init() {
        const source = document.getElementById('upload-rows');
        const rows = source ? JSON.parse(source.textContent) : [];
        this.rows = rows.map((row) => ({ ...row, selected: false, edited: false, expanded: false, flash: {} }));
    },

    get activeRows() {
        return this.rows.filter((row) => row.status !== 'removed');
    },

    get selectableRows() {
        return this.activeRows.filter((row) => row.status === 'staged');
    },

    get selectedRows() {
        return this.selectableRows.filter((row) => row.selected);
    },

    get allSelected() {
        return this.selectableRows.length > 0 && this.selectedRows.length === this.selectableRows.length;
    },

    get stagedCount() {
        return this.selectableRows.length;
    },

    get invalidCount() {
        return this.activeRows.filter((row) => row.status === 'invalid').length;
    },

    get bulkEmpty() {
        return BULK_FIELDS.every((field) => String(this.bulk[field]).trim() === '');
    },

    toggleAll(checked) {
        this.selectableRows.forEach((row) => (row.selected = checked));
    },

    // A hand edit protects the row from the next bulk apply.
    markEdited(row) {
        row.edited = true;
    },

    apply(target, overwrite = false) {
        const targets = target === 'selected' ? this.selectedRows : this.selectableRows;
        if (this.bulkEmpty || targets.length === 0) return;

        const skipped = [];
        let changed = 0;

        targets.forEach((row) => {
            if (row.edited && !overwrite) {
                skipped.push(row.id);
                return;
            }
            BULK_FIELDS.forEach((field) => {
                const value = String(this.bulk[field]).trim();
                if (value === '' || String(row[field]) === value) return;
                row[field] = value;
                this.flash(row, field);
            });
            row.edited = false;
            changed++;
        });

        this.skipped = skipped;
        this.lastTarget = target;
        this.message = skipped.length
            ? `Applied to ${changed} ${changed === 1 ? 'row' : 'rows'}. ${skipped.length} edited ${skipped.length === 1 ? 'row was' : 'rows were'} not changed.`
            : `Applied to ${changed} ${changed === 1 ? 'row' : 'rows'}.`;
    },

    overwriteEdited() {
        const ids = this.skipped;
        this.rows.filter((row) => ids.includes(row.id)).forEach((row) => (row.edited = false));
        const previous = this.lastTarget;
        this.skipped = [];
        // Re-apply only to the skipped rows.
        const keep = this.rows.map((row) => row.selected);
        this.rows.forEach((row) => (row.selected = ids.includes(row.id)));
        this.apply('selected', true);
        this.rows.forEach((row, i) => (row.selected = keep[i]));
        this.lastTarget = previous;
    },

    flash(row, field) {
        row.flash[field] = false;
        requestAnimationFrame(() => {
            row.flash[field] = true;
            setTimeout(() => (row.flash[field] = false), FLASH_MS);
        });
    },

    remove(row) {
        row.status = 'removed';
        row.selected = false;
    },

    restore(row) {
        row.status = 'staged';
    },
});
