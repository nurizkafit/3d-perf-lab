@php($tools = ['Blender' => 'Blender', 'Prisma3D' => 'Prisma3D', 'Spline' => 'Spline'])
<x-layouts::app title="Upload assets" description="Add GLB or GLTF files, complete the metadata, then register them.">
    <script type="application/json" id="upload-rows">@json($rows)</script>

    <x-assets.upload-dropzone />

    <div x-data="uploadTable" class="space-y-4">
        {{-- Bulk-apply bar (PRD.md §7.1). --}}
        <section class="card space-y-3" data-size="sm" aria-labelledby="bulk-label">
            <x-ui.section-label id="bulk-label">Apply to rows</x-ui.section-label>
            <div class="grid items-end gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
                <div>
                    <label for="bulk-author" class="field-label">Author</label>
                    <input id="bulk-author" class="input" x-model="bulk.author" placeholder="Leave empty to keep">
                </div>
                <div>
                    <label for="bulk-tool" class="field-label">Tool</label>
                    <select id="bulk-tool" class="select" x-model="bulk.tool">
                        <option value="">Keep current</option>
                        @foreach ($tools as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="bulk-hours" class="field-label">Avg. production time (h)</label>
                    <input id="bulk-hours" class="input" type="number" min="0" step="0.25" x-model="bulk.hours" placeholder="Leave empty to keep">
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button size="sm" variant="secondary" x-on:click="apply('selected')" x-bind:disabled="bulkEmpty || selectedRows.length === 0">
                    <span x-text="'Apply to selected (' + selectedRows.length + ')'">Apply to selected (0)</span>
                </x-ui.button>
                <x-ui.button size="sm" variant="secondary" x-on:click="apply('all')" x-bind:disabled="bulkEmpty">Apply to all</x-ui.button>
                <p class="text-body-sm text-neutral-600" role="status" x-text="message"></p>
                <x-ui.button size="sm" variant="outline" x-show="skipped.length > 0" x-cloak x-on:click="overwriteEdited()">Overwrite edited rows</x-ui.button>
            </div>
        </section>

        <x-assets.upload-table :tools="$tools" />

        <div class="upload-actions">
            <p class="text-body-sm"><span x-text="stagedCount"></span> staged · <span x-text="invalidCount"></span> invalid</p>
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-body-sm text-neutral-600">Registration arrives in M3.2.</span>
                <x-ui.button icon="check" disabled><span x-text="'Register ' + stagedCount + ' assets'">Register assets</span></x-ui.button>
            </div>
        </div>
    </div>
</x-layouts::app>
