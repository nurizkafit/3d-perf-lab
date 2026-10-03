@php
    $view = request('view') === 'table' ? 'table' : 'grid';
    $tools = ['Blender' => 'Blender', 'Prisma3D' => 'Prisma3D', 'Spline' => 'Spline'];
@endphp
<x-layouts::app title="Asset library" description="Registered 3D assets. Only registered assets can be used in tests.">
    <x-slot:actions>
        <x-ui.button :href="route('assets.upload')" icon="upload-simple">Upload assets</x-ui.button>
    </x-slot:actions>

    {{-- Plain GET form: filters live in the URL and work without JavaScript (PRD.md §7.2). --}}
    <form method="GET" role="search" class="card space-y-4" data-size="sm">
        <input type="hidden" name="view" value="{{ $view }}">
        <div class="grid items-end gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
            <x-ui.input name="q" label="Search" type="search" placeholder="Name, author, description" />
            <x-ui.select name="tool" label="Tool" :options="$tools" placeholder="All tools" />
            <x-ui.select name="format" label="Format" :options="['glb' => 'GLB', 'gltf' => 'GLTF']" placeholder="All formats" />
            <x-ui.select name="uploaded_by" label="Uploaded by" :options="['me' => 'Me', 'others' => 'Others']" placeholder="Anyone" />
            <x-ui.select name="sort" label="Sort" :options="['date' => 'Upload date', 'name' => 'Name', 'size' => 'File size', 'triangles' => 'Triangle count']" value="date" />
        </div>
        <details class="space-y-4">
            <summary class="link w-fit cursor-pointer text-body-sm">More filters</summary>
            <div class="grid items-end gap-4 pt-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
                <x-ui.input name="author" label="Author" />
                <x-ui.input name="size_min" label="Min size (MB)" type="number" />
                <x-ui.input name="size_max" label="Max size (MB)" type="number" />
                <x-ui.input name="from" label="Uploaded from" type="date" />
                <x-ui.input name="to" label="Uploaded to" type="date" />
                <label class="flex min-h-[var(--control-height)] items-center gap-2 text-body-sm font-semibold"><input type="checkbox" name="deleted" value="1" class="input"> Show deleted</label>
            </div>
        </details>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-ui.button type="submit" icon="funnel-simple">Apply filters</x-ui.button>
            <nav aria-label="View" class="flex gap-1">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" class="nav-link" @if ($view === 'grid') aria-current="page" @endif><x-ui.icon name="squares-four" /> <span class="ms-2">Grid</span></a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}" class="nav-link" @if ($view === 'table') aria-current="page" @endif><x-ui.icon name="list" /> <span class="ms-2">Table</span></a>
            </nav>
        </div>
    </form>

    <p class="text-body-sm text-neutral-600" role="status">{{ count($assets) }} assets</p>

    @if ($view === 'grid')
        <ul class="grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(min(100%,14rem),1fr))]">
            @foreach ($assets as $asset)
                <li><x-assets.asset-card :asset="$asset" /></li>
            @endforeach
        </ul>
    @else
        <x-ui.table caption="Assets">
            <x-slot:head>
                <tr><th scope="col">Asset</th><th scope="col">Tool</th><th scope="col">Author</th><th scope="col">Format</th><th scope="col" data-numeric>Size</th><th scope="col" data-numeric>Triangles</th><th scope="col">Uploaded by</th><th scope="col" data-numeric>Uploaded</th></tr>
            </x-slot:head>
            @foreach ($assets as $asset)
                <tr>
                    <th scope="row"><a class="link flex items-center gap-3" href="{{ route('assets.show', $asset['id']) }}"><span class="thumb"><x-ui.icon name="cube" /></span>{{ $asset['name'] }}</a></th>
                    <td>{{ $asset['tool'] }}</td>
                    <td>{{ $asset['author'] }}</td>
                    <td><x-ui.badge>{{ $asset['format'] }}</x-ui.badge></td>
                    <td data-numeric>{{ $asset['size'] }}</td>
                    <td data-numeric>{{ number_format($asset['triangles']) }}</td>
                    <td>{{ $asset['uploaded_by'] }}</td>
                    <td data-numeric>{{ $asset['uploaded'] }}</td>
                </tr>
            @endforeach
        </x-ui.table>
    @endif
</x-layouts::app>
