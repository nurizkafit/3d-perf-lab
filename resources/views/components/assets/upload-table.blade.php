@props(['tools', 'objects', 'canSetAuthor'])
{{-- Metadata table for one upload session (PRD.md §7.1, DESIGN.md §9.1). Rows come from the uploadTable component. --}}
<div class="table-container upload-table" tabindex="0" role="region" aria-label="Uploaded files" x-show="rows.length > 0" x-cloak>
    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="w-10"><input type="checkbox" class="input" aria-label="Select all staged rows" :checked="allSelected" x-on:change="toggleAll($event.target.checked)"></th>
                <th scope="col" class="sticky-col">Asset</th>
                <th scope="col">File name</th>
                <th scope="col">Author</th>
                <th scope="col">Tool</th>
                <th scope="col">Object</th>
                <th scope="col">Time (h)</th>
                <th scope="col">Format</th>
                <th scope="col" data-numeric>Size</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <template x-for="row in rows" :key="row.key">
            <tbody>
                <tr :aria-selected="row.selected" :data-removed="row.status === 'removed'">
                    <td><input type="checkbox" class="input" :aria-label="'Select ' + row.file" x-model="row.selected" :disabled="row.status !== 'staged'"></td>
                    <td class="sticky-col">
                        <div class="flex items-center gap-3">
                            <span class="thumb overflow-hidden">
                                <img x-show="row.preview" :src="row.preview" alt="" class="size-full object-cover">
                                <span x-show="!row.preview"><x-ui.icon name="cube" /></span>
                            </span>
                            <div class="min-w-32">
                                <input class="input" :aria-label="'Asset name for ' + row.file" x-model="row.name" x-on:input="edit(row)"
                                       :disabled="row.status !== 'staged' && row.status !== 'uploading'" :aria-invalid="!!fieldError(row, 'name')">
                                <p class="field-error" x-show="fieldError(row, 'name')" x-text="fieldError(row, 'name')"></p>
                            </div>
                        </div>
                    </td>
                    <td class="text-neutral-600">
                        <span x-text="row.file"></span>
                        <span class="block text-label" x-show="row.dependents?.length" x-text="'+ ' + (row.dependents?.length ?? 0) + ' linked files'"></span>
                    </td>
                    <td>
                        @if ($canSetAuthor)
                            <input class="input min-w-28" :class="row.flash.author && 'cell-flash'" :aria-label="'Author for ' + row.file" x-model="row.author" x-on:input="edit(row)"
                                   :disabled="row.status !== 'staged' && row.status !== 'uploading'" :aria-invalid="!!fieldError(row, 'author')">
                            <p class="field-error" x-show="fieldError(row, 'author')" x-text="fieldError(row, 'author')"></p>
                        @else
                            {{-- Users cannot change Author; the server sets it to their display name. --}}
                            <span x-text="row.author"></span>
                        @endif
                    </td>
                    <td>
                        <select class="select min-w-28" :class="row.flash.tool_id && 'cell-flash'" :aria-label="'Tool for ' + row.file" x-model="row.tool_id" x-on:change="edit(row)"
                                :disabled="row.status !== 'staged' && row.status !== 'uploading'" :aria-invalid="!!fieldError(row, 'tool_id')">
                            <option value="">Select</option>
                            @foreach ($tools as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                        <p class="field-error" x-show="fieldError(row, 'tool_id')" x-text="fieldError(row, 'tool_id')"></p>
                    </td>
                    <td>
                        <select class="select min-w-28" :class="row.flash.object_id && 'cell-flash'" :aria-label="'Object for ' + row.file" x-model="row.object_id" x-on:change="edit(row)"
                                :disabled="row.status !== 'staged' && row.status !== 'uploading'" :aria-invalid="!!fieldError(row, 'object_id')">
                            <option value="">Select</option>
                            @foreach ($objects as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                        <p class="field-error" x-show="fieldError(row, 'object_id')" x-text="fieldError(row, 'object_id')"></p>
                    </td>
                    <td>
                        <input class="input w-20" type="number" min="0" step="0.25" :class="row.flash.production_time_hours && 'cell-flash'" :aria-label="'Production time for ' + row.file"
                               x-model="row.production_time_hours" x-on:input="edit(row)" :disabled="row.status !== 'staged' && row.status !== 'uploading'" :aria-invalid="!!fieldError(row, 'production_time_hours')">
                        <p class="field-error" x-show="fieldError(row, 'production_time_hours')" x-text="fieldError(row, 'production_time_hours')"></p>
                    </td>
                    <td x-text="row.format"></td>
                    <td data-numeric x-text="row.size"></td>
                    <td>
                        <span class="badge" :data-variant="statusVariant(row)" x-text="statusLabel(row)"></span>
                        <span class="badge ms-1" data-variant="outline" x-show="row.edited && row.status === 'staged'" x-cloak>Edited</span>
                        <div class="upload-progress" x-show="row.status === 'uploading'" role="progressbar" :aria-valuenow="Math.round(row.progress * 100)" aria-valuemin="0" aria-valuemax="100" :aria-label="'Upload progress for ' + row.file">
                            <span :style="'transform: scaleX(' + row.progress + ')'"></span>
                        </div>
                    </td>
                    <td>
                        <div class="flex gap-1">
                            <x-ui.button variant="ghost" size="icon" x-show="row.status === 'staged'" x-on:click="row.expanded = !row.expanded" x-bind:aria-expanded="row.expanded" x-bind:aria-label="'Description for ' + row.file" aria-label="Description"><x-ui.icon name="caret-down" /></x-ui.button>
                            <x-ui.button variant="ghost" size="icon" x-show="['staged', 'invalid', 'failed'].includes(row.status)" x-on:click="remove(row)" x-bind:aria-label="'Remove ' + row.file" aria-label="Remove"><x-ui.icon name="trash" /></x-ui.button>
                        </div>
                    </td>
                </tr>
                <tr x-show="row.error || row.warning || row.status === 'registered'" x-cloak>
                    <td></td>
                    <td colspan="10" class="cell-wrap">
                        <p class="field-error m-0" x-show="row.error"><x-ui.icon name="warning-circle" /><span x-text="row.error"></span></p>
                        <p class="field-hint m-0" x-show="row.warning"><x-ui.icon name="info" /><span x-text="row.warning"></span></p>
                        <p class="field-hint m-0" x-show="row.status === 'registered'"><x-ui.icon name="check-circle" class="text-success" /><span x-text="'Registered as ' + (row.asset_name ?? row.name) + '.'"></span></p>
                    </td>
                </tr>
                <tr x-show="row.expanded" x-cloak>
                    <td></td>
                    <td colspan="10" class="cell-wrap py-3">
                        <label :for="'desc-' + row.key" class="field-label">Description (optional)</label>
                        <textarea :id="'desc-' + row.key" class="textarea" x-model="row.description" x-on:input="edit(row)"></textarea>
                        <p class="field-error" x-show="fieldError(row, 'description')" x-text="fieldError(row, 'description')"></p>
                    </td>
                </tr>
            </tbody>
        </template>
    </table>
</div>
