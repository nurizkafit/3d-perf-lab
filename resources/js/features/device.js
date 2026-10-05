// Device class confirmation (BENCHMARK.md §7.1): show the detected signals and the suggestion; preselect the
// session class, else the class remembered on this device, else a single suggested match. The user confirms.
import { collectContext, suggestDeviceClass } from '../three/environment.js';

const STORAGE_KEY = 'device-class-id';

export default () => ({
    context: null,
    suggestion: null,
    selected: '',

    async init() {
        const config = JSON.parse(document.getElementById('device-config').textContent);
        this.context = await collectContext();
        this.suggestion = suggestDeviceClass(this.context, config.classes);

        let remembered = null;
        try {
            remembered = localStorage.getItem(STORAGE_KEY);
        } catch {
            remembered = null;
        }
        const valid = (id) => id && config.classes.some((c) => String(c.id) === String(id));
        this.selected = String([config.currentId, remembered, this.suggestion.classId].find(valid) ?? '');
    },

    remember() {
        try {
            localStorage.setItem(STORAGE_KEY, this.selected);
        } catch {
            // Storage blocked: the session still holds the confirmed class.
        }
    },

    get gpu() {
        return this.context?.gpu_renderer ?? 'Not exposed by the browser';
    },
});
