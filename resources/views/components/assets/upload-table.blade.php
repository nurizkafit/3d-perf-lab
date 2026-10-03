@props(['tools'])
{{-- Metadata table for staged files (PRD.md §7.1, DESIGN.md §9.1). Rendered by Alpine from the uploadTable rows. --}}
<div class="table-container upload-table" tabindex="0" role="region" aria-label="Uploaded files">
    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="w-10"><input type="checkbox" class="input" aria-label="Select all staged rows" :checked="allSelected" x-on:change="toggleAll($event.target.checked)"></th>
                <th scope="col" class="sticky-col">Asset</th>
                <th scope="col">File name</th>
                <th scope="col">Author</th>
                <th scope="col">Tool</th>
                <th scope="col">Time (h)</th>
                <th scope="col">Format</th>
                <th scope="col" data-numeric>Size</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <template x-for="row in rows" :key="row.id">
            <tbody>
                <tr :aria-selected="row.selected" :data-removed="row.status === 'removed'">
                    <td><input type="checkbox" class="input" :aria-label="'Select ' + row.file" x-model="row.selected" :disabled="row.status !== 'staged'"></td>
                    <td class="sticky-col">
                        <div class="flex items-center gap-3">
                            <span class="thumb"><x-ui.icon name="cube" /></span>
                            <input class="input min-w-36" :aria-label="'Asset name for ' + row.file" x-model="row.name" x-on:input="markEdited(row)" :disabled="row.status !== 'staged'">
                        </div>
                    </td>
                    <td class="text-neutral-600" x-text="row.file"></td>
                    <td><input class="input min-w-36" :class="row.flash.author && 'cell-flash'" :aria-label="'Author for ' + row.file" x-model="row.author" x-on:input="markEdited(row)" :disabled="row.status !== 'staged'"></td>
                    <td>
                        <select class="select min-w-32" :class="row.flash.tool && 'cell-flash'" :aria-label="'Tool for ' + row.file" x-model="row.tool" x-on:change="markEdited(row)" :disabled="row.status !== 'staged'">
                            <option value="">Select</option>
                            @foreach ($tools as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </td>
                    <td><input class="input w-24" type="number" min="0" step="0.25" :class="row.flash.hours && 'cell-flash'" :aria-label="'Production time for ' + row.file" x-model="row.hours" x-on:input="markEdited(row)" :disabled="row.status !== 'staged'"></td>
                    <td x-text="row.format"></td>
                    <td data-numeric x-text="row.size"></td>
                    <td>
                        <span class="badge" :data-variant="{ staged: 'info', invalid: 'destructive', removed: 'outline' }[row.status]" x-text="{ staged: 'Staged', invalid: 'Invalid', removed: 'Removed' }[row.status]"></span>
                        <span class="badge ms-1" data-variant="outline" x-show="row.edited" x-cloak>Edited</span>
                    </td>
                    <td>
                        <div class="flex gap-1">
                            <x-ui.button variant="ghost" size="icon" x-on:click="row.expanded = !row.expanded" x-bind:aria-expanded="row.expanded" x-bind:aria-label="'Description for ' + row.file" aria-label="Description"><x-ui.icon name="caret-down" /></x-ui.button>
                            <x-ui.button variant="ghost" size="icon" x-show="row.status !== 'removed'" x-on:click="remove(row)" x-bind:aria-label="'Remove ' + row.file" aria-label="Remove"><x-ui.icon name="trash" /></x-ui.button>
                            <x-ui.button variant="ghost" size="icon" x-show="row.status === 'removed'" x-cloak x-on:click="restore(row)" x-bind:aria-label="'Undo remove ' + row.file" aria-label="Undo remove"><x-ui.icon name="arrow-counter-clockwise" /></x-ui.button>
                        </div>
                    </td>
                </tr>
                <tr x-show="row.error" x-cloak>
                    <td></td>
                    <td colspan="9"><p class="field-error m-0"><x-ui.icon name="warning-circle" /><span x-text="row.error"></span></p></td>
                </tr>
                <tr x-show="row.expanded" x-cloak>
                    <td></td>
                    <td colspan="9" class="py-3">
                        <label :for="'desc-' + row.id" class="field-label">Description (optional)</label>
                        <textarea :id="'desc-' + row.id" class="textarea" x-model="row.description" x-on:input="markEdited(row)"></textarea>
                    </td>
                </tr>
            </tbody>
        </template>
    </table>
</div>
