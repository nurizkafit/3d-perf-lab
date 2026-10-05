// Library bulk delete (decision D34): counts the selected rows and opens the confirm dialogs.
// The form posts the checked ids; the server checks every asset again.
export default () => ({
    selected: 0,
    selectedUnused: 0,
    selectable: 0,

    init() {
        this.refresh();
    },

    boxes() {
        return [...this.$root.querySelectorAll('input[name="ids[]"]:not(:disabled)')];
    },

    refresh() {
        const boxes = this.boxes();
        const checked = boxes.filter((box) => box.checked);
        this.selectable = boxes.length;
        this.selected = checked.length;
        this.selectedUnused = checked.filter((box) => box.dataset.used === '0').length;
    },

    toggleAll(checked) {
        this.boxes().forEach((box) => (box.checked = checked));
        this.refresh();
    },

    get allSelected() {
        return this.selectable > 0 && this.selected === this.selectable;
    },

    confirm(id) {
        document.getElementById(id)?.showModal();
    },
});
