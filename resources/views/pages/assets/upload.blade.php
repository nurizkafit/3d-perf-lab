@php
    $config = [
        'sessionId' => $session?->id,
        'items' => $items,
        'canSetAuthor' => $canSetAuthor,
        'authorDefault' => auth()->user()->name,
        'maxBytes' => $maxBytes,
        'endpoints' => [
            'sessions' => route('api.uploads.sessions.store'),
            'files' => route('api.uploads.files.store', '__SESSION__'),
            'register' => route('api.uploads.register', '__SESSION__'),
            'item' => route('api.uploads.items.update', '__ITEM__'),
        ],
    ];
    $maxLabel = \Illuminate\Support\Number::fileSize($maxBytes, maxPrecision: 1);
    $open = ! $session || $session->status === 'open';
@endphp
<x-layouts::app :title="$session ? 'Upload session' : 'Upload assets'" :description="$session ? 'Started '.$session->created_at->display()->format('Y-m-d H:i').'. Every file of this session with its result. Staged rows can still be completed and registered.' : 'Add GLB or GLTF files, complete the metadata, then register them.'" :sample="false">
    <x-slot:actions>
        @if ($session)
            <x-ui.button :href="route('assets.upload')" variant="secondary" icon="plus">New upload</x-ui.button>
        @endif
    </x-slot:actions>

    <script type="application/json" id="upload-config">@json($config)</script>

    <div x-data="uploadTable" class="space-y-4">
        @if ($open)
            <x-assets.upload-dropzone :max-label="$maxLabel" />
        @else
            <p class="alert-box" role="status"><x-ui.icon name="info" />This session is {{ $session->status }}. Start a new upload to add more files.</p>
        @endif

        <section class="card space-y-3" data-size="sm" aria-labelledby="bulk-label" x-show="stagedRows.length > 0" x-cloak>
            <x-ui.section-label id="bulk-label">Apply to rows</x-ui.section-label>
            <div class="grid items-end gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
                @if ($canSetAuthor)
                    <div>
                        <label for="bulk-author" class="field-label">Author</label>
                        <input id="bulk-author" class="input" x-model="bulk.author" placeholder="Leave empty to keep">
                    </div>
                @endif
                <div>
                    <label for="bulk-tool" class="field-label">Tool</label>
                    <select id="bulk-tool" class="select" x-model="bulk.tool_id">
                        <option value="">Keep current</option>
                        @foreach ($tools as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="bulk-object" class="field-label">Object</label>
                    <select id="bulk-object" class="select" x-model="bulk.object_id">
                        <option value="">Keep current</option>
                        @foreach ($objects as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="bulk-hours" class="field-label">Avg. production time (h)</label>
                    <input id="bulk-hours" class="input" type="number" min="0" step="0.25" x-model="bulk.production_time_hours" placeholder="Leave empty to keep">
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

        <x-assets.upload-table :tools="$tools" :objects="$objects" :can-set-author="$canSetAuthor" />

        <div class="upload-actions" x-show="rows.length > 0" x-cloak>
            <p class="text-body-sm" role="status">
                <span x-text="stagedRows.length"></span> staged ·
                <span x-text="invalidCount"></span> invalid<span x-show="uploadingCount > 0"> · <span x-text="uploadingCount"></span> uploading</span>
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-body-sm" role="status" x-text="resultMessage"></p>
                <x-ui.button icon="check" x-on:click="register()" x-bind:disabled="stagedRows.length === 0 || uploadingCount > 0 || registering" x-bind:aria-busy="registering">
                    <span x-text="'Register ' + stagedRows.length + (stagedRows.length === 1 ? ' asset' : ' assets')">Register assets</span>
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layouts::app>
