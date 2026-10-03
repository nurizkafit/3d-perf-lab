// App shell state: desktop sidebar collapse (remembered) and the off-canvas drawer on small screens.
const STORAGE_KEY = 'sidebar-collapsed';

export default () => ({
    collapsed: false,
    mobileOpen: false,

    init() {
        try {
            this.collapsed = localStorage.getItem(STORAGE_KEY) === 'true';
        } catch {
            this.collapsed = false;
        }
    },

    toggleCollapsed() {
        this.collapsed = !this.collapsed;
        try {
            localStorage.setItem(STORAGE_KEY, String(this.collapsed));
        } catch {
            // Storage blocked: keep the state for this page only.
        }
    },

    openMobile() {
        this.mobileOpen = true;
        this.$nextTick(() => document.querySelector('#app-sidebar a, #app-sidebar summary')?.focus());
    },

    closeMobile() {
        if (!this.mobileOpen) return;
        this.mobileOpen = false;
        document.querySelector('[aria-controls="app-sidebar"][aria-label="Open navigation"]')?.focus();
    },
});
