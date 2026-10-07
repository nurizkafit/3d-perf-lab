@php
    $v = $filters->values;
    $sorts = collect(\App\Support\AssetFilters::SORTS)->map(fn ($sort) => $sort[2])->all();
@endphp
<x-layouts::app title="Asset library" description="Registered 3D assets. Only registered assets can be used in tests." :sample="false">
    <x-slot:actions>
        @if ($canBulk)
            @if ($v['bulk'])
                <x-ui.button :href="request()->fullUrlWithQuery(['bulk' => null, 'page' => null])" variant="secondary" icon="x">Done</x-ui.button>
            @else
                <x-ui.button :href="request()->fullUrlWithQuery(['bulk' => 1, 'view' => 'table', 'page' => null])" variant="secondary" icon="trash">Bulk delete</x-ui.button>
            @endif
        @endif
        <x-ui.button :href="route('assets.upload')" icon="upload-simple">Upload assets</x-ui.button>
    </x-slot:actions>

    {{-- Plain GET form: filters live in the URL and work without JavaScript (PRD.md §7.2). --}}
    <form method="GET" action="{{ route('assets.index') }}" role="search" class="card space-y-4" data-size="sm">
        <input type="hidden" name="view" value="{{ $v['view'] }}">
        @if ($v['bulk'])<input type="hidden" name="bulk" value="1">@endif
        <div class="grid items-end gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
            <x-ui.input name="q" label="Search" type="search" :value="$v['q']" placeholder="Name, author, description" />
            <x-ui.select name="tool" label="Tool" :options="$tools" :value="$v['tool']" placeholder="All tools" />
            <x-ui.select name="format" label="Format" :options="['glb' => 'GLB', 'gltf' => 'GLTF']" :value="$v['format']" placeholder="All formats" />
            <x-ui.select name="uploaded_by" label="Uploaded by" :options="$uploaders" :value="$v['uploaded_by']" placeholder="Anyone" />
            <x-ui.select name="sort" label="Sort" :options="$sorts" :value="$v['sort']" />
        </div>
        <details class="space-y-4" @if ($v['author'] !== '' || $v['size_min'] !== null || $v['size_max'] !== null || $v['from'] || $v['to'] || $v['deleted']) open @endif>
            <summary class="link w-fit cursor-pointer text-body-sm">More filters</summary>
            <div class="grid items-end gap-4 pt-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
                <x-ui.input name="author" label="Author" :value="$v['author']" />
                <x-ui.input name="size_min" label="Min size (MB)" type="number" step="0.1" min="0" :value="$v['size_min']" />
                <x-ui.input name="size_max" label="Max size (MB)" type="number" step="0.1" min="0" :value="$v['size_max']" />
                <x-ui.input name="from" label="Uploaded from" type="date" :value="$v['from']" />
                <x-ui.input name="to" label="Uploaded to" type="date" :value="$v['to']" />
                <label class="flex min-h-[var(--control-height)] items-center gap-2 text-body-sm font-semibold"><input type="checkbox" name="deleted" value="1" class="input" @checked($v['deleted'])> Show deleted assets only</label>
            </div>
        </details>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <x-ui.button type="submit" icon="funnel-simple">Apply filters</x-ui.button>
                @if ($filters->active())
                    <x-ui.button :href="route('assets.index', ['view' => $v['view']])" variant="ghost">Clear filters</x-ui.button>
                @endif
            </div>
            <nav aria-label="View" class="flex gap-1">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'grid', 'page' => null]) }}" class="nav-link" @if ($v['view'] === 'grid') aria-current="page" @endif><x-ui.icon name="squares-four" /><span class="ms-2">Grid</span></a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'table', 'page' => null]) }}" class="nav-link" @if ($v['view'] === 'table') aria-current="page" @endif><x-ui.icon name="list" /><span class="ms-2">Table</span></a>
            </nav>
        </div>
    </form>

    <p class="text-body-sm text-neutral-600" role="status">
        {{ $assets->total() }} {{ Str::plural('asset', $assets->total()) }}{{ $v['deleted'] ? ' (deleted)' : '' }}
    </p>

    @if ($assets->isEmpty())
        @if ($filters->active())
            <x-ui.empty-state icon="magnifying-glass" message="No asset matches these filters.">
                <x-ui.button :href="route('assets.index', ['view' => $v['view']])">Clear filters</x-ui.button>
            </x-ui.empty-state>
        @else
            <x-ui.empty-state icon="cube" message="No assets yet. Upload GLB or GLTF files to start.">
                <x-ui.button :href="route('assets.upload')" icon="upload-simple">Upload assets</x-ui.button>
            </x-ui.empty-state>
        @endif
    @elseif ($v['view'] === 'grid')
        <ul class="grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(min(100%,14rem),1fr))]">
            @foreach ($assets as $asset)
                <li><x-assets.asset-card :asset="$asset" /></li>
            @endforeach
        </ul>
    @else
        <div x-data="assetBulk" class="space-y-3" @if ($v['bulk']) x-on:change="refresh()" @endif>
            @if ($v['bulk'])
                {{-- Bulk bar (decision D34). Buttons open a confirm dialog; the dialog submits the form. --}}
                <div class="upload-actions" role="region" aria-label="Bulk delete">
                    <p class="text-body-sm" role="status"><span x-text="selected">0</span> selected</p>
                    <div class="flex flex-wrap items-center gap-2">
                        @unless ($v['deleted'])
                            <x-ui.button variant="destructive" icon="trash" x-on:click="confirm('bulk-delete')" x-bind:disabled="selected === 0" disabled>
                                <span x-text="'Delete (' + selected + ')'">Delete</span>
                            </x-ui.button>
                        @endunless
                        @if ($canForceDelete)
                            <x-ui.button variant="outline" icon="trash" x-on:click="confirm('bulk-force-delete')" x-bind:disabled="selectedUnused === 0" disabled>
                                <span x-text="'Delete permanently (' + selectedUnused + ')'">Delete permanently</span>
                            </x-ui.button>
                            <p class="text-body-sm text-neutral-600" x-show="selected > selectedUnused" x-cloak>
                                <span x-text="selected - selectedUnused"></span> selected <span x-text="selected - selectedUnused === 1 ? 'asset is' : 'assets are'"></span> used in tests and cannot be deleted permanently.
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            <form id="bulk-form" method="POST" action="{{ route('assets.bulk-destroy') }}">
                @csrf
                @method('DELETE')
                <x-ui.table caption="Assets">
                    <x-slot:head>
                        <tr>
                            @if ($v['bulk'])
                                <th scope="col" class="w-10"><input type="checkbox" class="input" aria-label="Select all assets on this page" :checked="allSelected" x-on:change.stop="toggleAll($event.target.checked)"></th>
                            @endif
                            <th scope="col">Asset</th><th scope="col">Tool</th><th scope="col">Author</th><th scope="col">Format</th><th scope="col" data-numeric>Size</th><th scope="col" data-numeric>Triangles</th><th scope="col">Uploaded by</th><th scope="col" data-numeric>Uploaded</th>
                            @if ($v['bulk'])<th scope="col">Tests</th>@endif
                        </tr>
                    </x-slot:head>
                    @foreach ($assets as $asset)
                        @php
                            $used = $asset->test_sessions_count > 0;
                            $selectable = $v['deleted'] ? auth()->user()->can('forceDelete', $asset) : auth()->user()->can('delete', $asset);
                        @endphp
                        <tr>
                            @if ($v['bulk'])
                                <td>
                                    <input type="checkbox" class="input" name="ids[]" value="{{ $asset->id }}" data-used="{{ $used ? 1 : 0 }}"
                                           aria-label="Select {{ $asset->name }}" @disabled(! $selectable || ($v['deleted'] && $used))>
                                </td>
                            @endif
                            <th scope="row">
                                <a class="link flex items-center gap-3" href="{{ route('assets.show', $asset) }}">
                                    <span class="thumb overflow-hidden">
                                        @if ($asset->thumbnail_path)<img src="{{ route('assets.thumbnail', $asset) }}" alt="" loading="lazy" class="size-full object-cover">@else<x-ui.icon name="cube" />@endif
                                    </span>
                                    {{ $asset->name }}
                                </a>
                            </th>
                            <td>{{ $asset->tool->name }}</td>
                            <td>{{ $asset->author }}</td>
                            <td><x-ui.badge>{{ strtoupper($asset->format) }}</x-ui.badge></td>
                            <td data-numeric>{{ \Illuminate\Support\Number::fileSize($asset->file_size, maxPrecision: 1) }}</td>
                            <td data-numeric>{{ $asset->metrics ? number_format($asset->metrics->triangles) : '—' }}</td>
                            <td>{{ $asset->uploader->name }}</td>
                            <td data-numeric>{{ $asset->created_at->display()->format('Y-m-d') }}</td>
                            @if ($v['bulk'])
                                <td>
                                    @if ($used)
                                        <span class="badge" data-variant="outline" title="Cannot be deleted permanently"><x-ui.icon name="lock-simple" />Used in {{ $asset->test_sessions_count }} {{ Str::plural('test', $asset->test_sessions_count) }}</span>
                                    @elseif (! $selectable)
                                        <span class="text-body-sm text-neutral-600">Not yours</span>
                                    @else
                                        <span class="text-body-sm text-neutral-600">—</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </x-ui.table>
            </form>

            @if ($v['bulk'])
                <x-ui.dialog id="bulk-delete" title="Delete the selected assets?" description="They are hidden from the library and from new tests. Old results keep them. You can restore them later.">
                    <x-slot:footer>
                        <div class="flex justify-end gap-2">
                            <x-ui.button variant="outline" data-dialog-close>Cancel</x-ui.button>
                            <x-ui.button variant="destructive" type="submit" form="bulk-form" formaction="{{ route('assets.bulk-destroy') }}" icon="trash">
                                <span x-text="'Delete ' + selected + (selected === 1 ? ' asset' : ' assets')">Delete</span>
                            </x-ui.button>
                        </div>
                    </x-slot:footer>
                </x-ui.dialog>
                @if ($canForceDelete)
                    <x-ui.dialog id="bulk-force-delete" title="Delete permanently?" description="This removes the asset records and their files. It cannot be undone. Assets used in tests are skipped and stay as they are.">
                        <x-slot:footer>
                            <div class="flex justify-end gap-2">
                                <x-ui.button variant="outline" data-dialog-close>Cancel</x-ui.button>
                                <x-ui.button variant="destructive" type="submit" form="bulk-form" formaction="{{ route('assets.bulk-force-destroy') }}" icon="trash">
                                    <span x-text="'Delete ' + selectedUnused + ' permanently'">Delete permanently</span>
                                </x-ui.button>
                            </div>
                        </x-slot:footer>
                    </x-ui.dialog>
                @endif
            @endif
        </div>
    @endif

    @if ($assets->hasPages())
        <nav aria-label="Pages" class="flex flex-wrap items-center justify-between gap-3">
            <x-ui.button :href="$assets->previousPageUrl()" variant="secondary" :disabled="$assets->onFirstPage()" icon="arrow-left">Previous</x-ui.button>
            <p class="text-body-sm text-neutral-600">Page {{ $assets->currentPage() }} of {{ $assets->lastPage() }}</p>
            <x-ui.button :href="$assets->nextPageUrl()" variant="secondary" :disabled="! $assets->hasMorePages()" icon="arrow-right">Next</x-ui.button>
        </nav>
    @endif
</x-layouts::app>
