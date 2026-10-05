import Alpine from 'alpinejs';
import 'basecoat-css/all';
import appShell from './features/app-shell';
import uploadTable from './features/upload';
import assetBulk from './features/asset-bulk';
import deviceCheck from './features/device';
import budgetStep from './features/budget-step';
import assetSelector from './features/asset-selector';
import labConfig from './features/lab-config';

window.Alpine = Alpine;
Alpine.data('appShell', appShell);
Alpine.data('uploadTable', uploadTable);
Alpine.data('assetBulk', assetBulk);
Alpine.data('deviceCheck', deviceCheck);
Alpine.data('budgetStep', budgetStep);
Alpine.data('assetSelector', assetSelector);
Alpine.data('labConfig', labConfig);
Alpine.start();

// Three.js stays out of the entry bundle: the viewer loads only on pages that show a model.
const viewerElements = document.querySelectorAll('[data-viewer][data-src]');
if (viewerElements.length) {
    import('./three/viewer.js').then(({ mountViewerElement }) => viewerElements.forEach(mountViewerElement));
}

const engineSelfTest = document.querySelector('[data-engine-self-test]');
if (engineSelfTest) {
    import('./features/engine-self-test.js').then(({ initEngineSelfTest }) => initEngineSelfTest(engineSelfTest));
}

const resultsCharts = document.querySelector('[data-results-charts]');
if (resultsCharts) {
    import('./features/results.js').then(({ initResults }) => initResults(resultsCharts));
}

const benchmarkRun = document.querySelector('[data-benchmark-run]');
if (benchmarkRun) {
    import('./features/benchmark-run.js').then(({ initBenchmarkRun }) => initBenchmarkRun(benchmarkRun));
}

const viewerLab = document.querySelector('[data-viewer-lab]');
if (viewerLab) {
    import('./features/viewer-lab.js').then(({ initViewerLab }) => initViewerLab(viewerLab));
}

// Open a <dialog> from any trigger: <button data-dialog-open="dialog-id">.
document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-dialog-open]');
    if (!trigger) return;
    document.getElementById(trigger.dataset.dialogOpen)?.showModal();
});

// Close the surrounding dialog: <button data-dialog-close>.
document.addEventListener('click', (event) => {
    event.target.closest('[data-dialog-close]')?.closest('dialog')?.close();
});

// Close a modal dialog when the backdrop (the <dialog> element itself) is clicked.
document.addEventListener('click', (event) => {
    if (event.target instanceof HTMLDialogElement && event.target.open) event.target.close();
});

// Keep Tab inside an open modal dialog (native dialogs let focus leave to the browser UI).
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab') return;
    const dialog = document.querySelector('dialog[open]');
    if (!dialog) return;

    const focusable = [...dialog.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')];
    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
});

const TOAST_ICONS = { success: 'check-circle', error: 'x-circle', warning: 'warning', info: 'info' };

/**
 * Show a toast. Text is set with textContent, so user data is never parsed as HTML.
 * Errors stay until closed; other toasts close after 4 s (DESIGN.md §5.1).
 */
window.toast = ({ category = 'info', title = '', description = '' } = {}) => {
    const toaster = document.getElementById('toaster');
    if (!toaster) return;

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.dataset.category = category;
    toast.dataset.duration = category === 'error' ? '-1' : '4000';
    toast.setAttribute('role', category === 'error' ? 'alert' : 'status');
    toast.setAttribute('aria-atomic', 'true');

    const content = document.createElement('div');
    content.className = 'toast-content';

    const icon = document.querySelector(`template[data-icon="${TOAST_ICONS[category]}"]`);
    if (icon) content.append(icon.content.cloneNode(true));

    const section = document.createElement('section');
    const heading = document.createElement('h2');
    heading.textContent = title;
    section.append(heading);
    if (description) {
        const text = document.createElement('p');
        text.textContent = description;
        section.append(text);
    }
    content.append(section);

    const footer = document.createElement('footer');
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn';
    close.dataset.variant = 'outline';
    close.dataset.size = 'sm';
    close.dataset.toastCancel = '';
    close.textContent = 'Close';
    footer.append(close);
    content.append(footer);

    toast.append(content);
    toaster.append(toast);
};
