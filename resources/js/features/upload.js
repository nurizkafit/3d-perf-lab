// Bulk upload (PRD.md §7.1): drop → group → inspect in the browser → upload each model → edit metadata → register.
// Config comes from <script type="application/json" id="upload-config">.
import { apiFetch, uploadWithProgress } from '../api.js';
import { parseFileName } from './filename-meta.js';

const BULK_FIELDS = ['author', 'tool_id', 'object_id', 'production_time_minutes'];
const FLASH_MS = 260; // --duration-slow
const SAVE_DELAY_MS = 600;
const MODEL_EXTENSIONS = ['glb', 'gltf'];

const extension = (name) => name.split('.').pop().toLowerCase();

/** GLTF + the dropped files it references; every other file is its own model (the server validates it). */
async function groupFiles(files) {
    const byName = new Map(files.map((file) => [file.name, file]));
    const used = new Set();
    const groups = [];

    for (const file of files.filter((f) => extension(f.name) === 'gltf')) {
        let references = [];
        try {
            const json = JSON.parse(await file.text());
            references = [...(json.buffers ?? []), ...(json.images ?? [])]
                .map((entry) => entry.uri)
                .filter((uri) => uri && !uri.startsWith('data:'))
                .map((uri) => decodeURIComponent(uri.split('/').pop()));
        } catch {
            // Not JSON: the server reports it as invalid.
        }
        const dependents = [...new Set(references)].map((name) => byName.get(name)).filter(Boolean);
        dependents.forEach((dependent) => used.add(dependent));
        groups.push({ main: file, dependents });
    }

    for (const file of files) {
        if (extension(file.name) !== 'gltf' && !used.has(file)) groups.push({ main: file, dependents: [] });
    }

    return groups;
}

/** Metrics + thumbnail from the local files. Any failure (no WebGL, broken model) gives null: the row shows a placeholder. */
async function inspectLocally(group) {
    if (!MODEL_EXTENSIONS.includes(extension(group.main.name))) return { metrics: null, thumbnail: null };
    try {
        const [{ loadModelFromFiles, disposeObject }, { inspectModel, renderThumbnail }] = await Promise.all([
            import('../three/loaders.js'),
            import('../three/inspect.js'),
        ]);
        const gltf = await loadModelFromFiles(group.main, group.dependents);
        const metrics = inspectModel(gltf.scene);
        const thumbnail = await renderThumbnail(gltf.scene);
        disposeObject(gltf.scene);

        return { metrics, thumbnail };
    } catch {
        return { metrics: null, thumbnail: null };
    }
}

export default () => ({
    config: {},
    sessionId: null,
    rows: [],
    bulk: { author: '', tool_id: '', object_id: '', production_time_minutes: '' },
    over: false,
    busy: false,
    registering: false,
    skipped: [],
    message: '',
    resultMessage: '',
    nextKey: 1,
    saveTimers: {},

    init() {
        this.config = JSON.parse(document.getElementById('upload-config').textContent);
        this.sessionId = this.config.sessionId;
        this.rows = (this.config.items ?? []).map((item) => this.rowFromItem(item));
    },

    rowFromItem(item, base = {}) {
        return {
            key: base.key ?? `s${item.id}`,
            selected: base.selected ?? false,
            edited: base.edited ?? false,
            expanded: base.expanded ?? false,
            flash: {},
            progress: 1,
            preview: base.preview ?? item.thumbnail_url,
            ...item,
            // Keep what the user already typed while the file was uploading.
            ...(base.edited ? { name: base.name, author: base.author, tool_id: base.tool_id, object_id: base.object_id, production_time_minutes: base.production_time_minutes, description: base.description } : {}),
        };
    },

    // ---------- Derived state ----------

    get activeRows() {
        return this.rows.filter((row) => row.status !== 'removed');
    },
    get stagedRows() {
        return this.rows.filter((row) => row.status === 'staged');
    },
    get selectedRows() {
        return this.stagedRows.filter((row) => row.selected);
    },
    get allSelected() {
        return this.stagedRows.length > 0 && this.selectedRows.length === this.stagedRows.length;
    },
    get invalidCount() {
        return this.rows.filter((row) => row.status === 'invalid').length;
    },
    get uploadingCount() {
        return this.rows.filter((row) => row.status === 'uploading').length;
    },
    get bulkEmpty() {
        return BULK_FIELDS.every((field) => String(this.bulk[field]).trim() === '');
    },

    // ---------- Adding files ----------

    async drop(event) {
        this.over = false;
        await this.addFiles([...(event.dataTransfer?.files ?? [])]);
    },

    async pick(event) {
        await this.addFiles([...event.target.files]);
        event.target.value = '';
    },

    async addFiles(files) {
        if (!files.length) return;
        this.resultMessage = '';
        const groups = await groupFiles(files);
        const rows = groups.map((group) => {
            const row = {
                key: `l${this.nextKey++}`, id: null, file: group.main.name, status: 'uploading', progress: 0,
                format: extension(group.main.name).toUpperCase(), size: this.formatSize(group.main.size + group.dependents.reduce((sum, f) => sum + f.size, 0)),
                name: '', author: this.config.authorDefault, tool_id: '', object_id: '', production_time_minutes: '', description: '',
                selected: false, edited: false, expanded: false, flash: {}, preview: null, error: null, warning: null, field_errors: {},
                dependents: group.dependents.map((f) => f.name),
            };
            this.rows.push(row);

            return { row: this.rows[this.rows.length - 1], group };
        });

        try {
            await this.ensureSession();
        } catch (error) {
            rows.forEach(({ row }) => Object.assign(row, { status: 'failed', error: error.message }));

            return;
        }

        // One model per request, one at a time, so each row shows its own progress.
        for (const { row, group } of rows) {
            await this.uploadGroup(row, group);
        }
    },

    async ensureSession() {
        if (this.sessionId) return;
        const session = await apiFetch(this.config.endpoints.sessions, { method: 'POST', json: {} });
        this.sessionId = session.id;
        history.replaceState(null, '', session.url); // reload resumes this session
    },

    async uploadGroup(row, group) {
        const tooLarge = [group.main, ...group.dependents].find((file) => file.size > this.config.maxBytes);
        if (tooLarge) {
            Object.assign(row, { status: 'invalid', progress: 1, error: `${tooLarge.name} is larger than the maximum of ${this.formatSize(this.config.maxBytes)}.` });

            return;
        }

        const { metrics, thumbnail } = await inspectLocally(group);
        if (thumbnail) row.preview = URL.createObjectURL(thumbnail);

        const form = new FormData();
        form.append('file', group.main);
        group.dependents.forEach((file) => form.append('dependents[]', file));
        if (metrics) form.append('metrics', JSON.stringify(metrics));
        if (thumbnail) form.append('thumbnail', thumbnail, thumbnail.type === 'image/webp' ? 'thumbnail.webp' : 'thumbnail.jpg');

        try {
            const item = await uploadWithProgress(this.endpoint('files'), form, (fraction) => (row.progress = fraction));
            const wasEdited = row.edited;
            Object.assign(row, this.rowFromItem(item, row));
            if (!wasEdited) this.applyFileNameMeta(row, group.main.name);
            if (row.edited) this.save(row);
        } catch (error) {
            Object.assign(row, { status: 'failed', progress: 1, error: error.message });
        }
    },

    // Pre-fill author, tool, and object from `author_tool_object.glb`. Empty parts change nothing.
    applyFileNameMeta(row, fileName) {
        const found = parseFileName(fileName, { tools: this.config.tools ?? [], objects: this.config.objects ?? [] });
        if (found.author && this.config.canSetAuthor) row.author = found.author;
        if (found.tool_id) row.tool_id = Number(found.tool_id); // same type as the server items
        if (found.object_id) row.object_id = Number(found.object_id);
        if (found.tool_id || found.object_id || (found.author && this.config.canSetAuthor)) this.save(row);
    },

    // ---------- Editing ----------

    edit(row) {
        row.edited = true;
        row.field_errors = {};
        this.save(row);
    },

    save(row) {
        if (!row.id || row.status !== 'staged') return;
        clearTimeout(this.saveTimers[row.key]);
        this.saveTimers[row.key] = setTimeout(() => {
            apiFetch(this.endpoint('item', row.id), { method: 'PATCH', json: this.metadata(row) }).catch(() => {});
        }, SAVE_DELAY_MS);
    },

    metadata(row) {
        return {
            name: row.name,
            author: row.author,
            tool_id: row.tool_id === '' ? null : Number(row.tool_id),
            object_id: row.object_id === '' ? null : Number(row.object_id),
            production_time_minutes: row.production_time_minutes === '' ? null : row.production_time_minutes,
            description: row.description,
        };
    },

    toggleAll(checked) {
        this.stagedRows.forEach((row) => (row.selected = checked));
    },

    // ---------- Bulk apply (never overwrites a hand-edited row without confirmation) ----------

    apply(target, overwrite = false, onlyKeys = null) {
        let targets = target === 'selected' ? this.selectedRows : this.stagedRows;
        if (onlyKeys) targets = this.stagedRows.filter((row) => onlyKeys.includes(row.key));
        if (this.bulkEmpty || targets.length === 0) return;

        const skipped = [];
        let changed = 0;
        targets.forEach((row) => {
            if (row.edited && !overwrite) {
                skipped.push(row.key);

                return;
            }
            BULK_FIELDS.forEach((field) => {
                if (field === 'author' && !this.config.canSetAuthor) return;
                const value = String(this.bulk[field]).trim();
                if (value === '' || String(row[field]) === value) return;
                row[field] = value;
                this.flash(row, field);
            });
            row.edited = false;
            row.field_errors = {};
            this.save(row);
            changed++;
        });

        this.skipped = skipped;
        const rowsWord = (n) => (n === 1 ? 'row' : 'rows');
        this.message = skipped.length
            ? `Applied to ${changed} ${rowsWord(changed)}. ${skipped.length} edited ${skipped.length === 1 ? 'row was' : 'rows were'} not changed.`
            : `Applied to ${changed} ${rowsWord(changed)}.`;
    },

    overwriteEdited() {
        const keys = this.skipped;
        this.skipped = [];
        this.apply('all', true, keys);
    },

    flash(row, field) {
        row.flash[field] = false;
        requestAnimationFrame(() => {
            row.flash[field] = true;
            setTimeout(() => (row.flash[field] = false), FLASH_MS);
        });
    },

    // ---------- Remove and register ----------

    async remove(row) {
        if (!row.id) {
            this.rows = this.rows.filter((candidate) => candidate !== row);

            return;
        }
        try {
            const item = await apiFetch(this.endpoint('item', row.id), { method: 'DELETE' });
            Object.assign(row, { status: item.status, selected: false });
        } catch (error) {
            row.error = error.message;
        }
    },

    async register() {
        if (!this.stagedRows.length || this.registering) return;
        this.registering = true;
        this.resultMessage = '';
        const rows = Object.fromEntries(this.stagedRows.map((row) => [row.id, this.metadata(row)]));

        try {
            const result = await apiFetch(this.endpoint('register'), { method: 'POST', json: { rows } });
            const byId = new Map(result.items.map((item) => [item.id, item]));
            this.rows.forEach((row, index) => {
                const item = row.id && byId.get(row.id);
                if (item) this.rows[index] = this.rowFromItem({ ...item, field_errors: result.errors[row.id] ?? {} }, row);
            });
            const ok = Object.keys(result.registered).length;
            const failed = Object.keys(result.errors).length;
            this.resultMessage = failed
                ? `Registered ${ok} ${ok === 1 ? 'asset' : 'assets'}. ${failed} ${failed === 1 ? 'row needs' : 'rows need'} changes; see the messages in the table.`
                : `Registered ${ok} ${ok === 1 ? 'asset' : 'assets'}.`;
        } catch (error) {
            this.resultMessage = error.message;
        } finally {
            this.registering = false;
        }
    },

    // ---------- Helpers ----------

    endpoint(name, id = null) {
        return this.config.endpoints[name].replace('__SESSION__', this.sessionId).replace('__ITEM__', id);
    },

    fieldError(row, field) {
        return row.field_errors?.[field]?.[0] ?? null;
    },

    formatSize(bytes) {
        const round = (value) => String(Math.round(value * 10) / 10);
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${round(bytes / 1024)} KB`;

        return `${round(bytes / 1024 / 1024)} MB`;
    },

    statusLabel(row) {
        return { uploading: 'Uploading', staged: 'Staged', invalid: 'Invalid', registered: 'Registered', failed: 'Failed', removed: 'Removed' }[row.status];
    },

    statusVariant(row) {
        return { uploading: 'outline', staged: 'info', invalid: 'destructive', registered: 'success', failed: 'destructive', removed: 'outline' }[row.status];
    },
});
