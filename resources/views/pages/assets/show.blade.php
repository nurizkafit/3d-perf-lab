<x-layouts::app :title="$item['name']" description="Asset detail: preview, metadata, and derived metrics.">
    <x-slot:actions>
        <x-ui.button variant="secondary" icon="download-simple" disabled>Download</x-ui.button>
        <x-ui.button :href="route('assets.edit', $item['id'])" variant="secondary" icon="pencil-simple">Edit</x-ui.button>
        <x-ui.button variant="destructive" icon="trash" disabled>Delete</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <x-performance.viewer mode="preview" />

        <section class="card space-y-4" aria-labelledby="meta">
            <x-ui.section-label id="meta">Metadata</x-ui.section-label>
            <dl class="grid grid-cols-[minmax(0,auto)_1fr] gap-x-4 gap-y-2 text-body-sm">
                <dt class="text-neutral-600">Name</dt><dd>{{ $item['name'] }}</dd>
                <dt class="text-neutral-600">Tool</dt><dd>{{ $item['tool'] }}</dd>
                <dt class="text-neutral-600">Author</dt><dd>{{ $item['author'] }}</dd>
                <dt class="text-neutral-600">Uploaded by</dt><dd>{{ $item['uploaded_by'] }}</dd>
                <dt class="text-neutral-600">Production time</dt><dd class="num">{{ $item['hours'] }} h</dd>
                <dt class="text-neutral-600">Uploaded</dt><dd class="num">{{ $item['uploaded'] }}</dd>
            </dl>
            <p class="text-body-sm">{{ $item['description'] }}</p>
        </section>
    </div>

    {{-- Asset metrics and runtime metrics stay in separate sections (PRD.md §7.3). --}}
    <section class="space-y-3" aria-labelledby="asset-metrics">
        <x-ui.section-label id="asset-metrics">Asset metrics (from the file)</x-ui.section-label>
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,10rem),1fr))]">
            <x-performance.metric-card label="Format" :value="$item['format']" />
            <x-performance.metric-card label="File size" :value="$item['size']" />
            <x-performance.metric-card label="Vertices" :value="number_format($item['vertices'])" />
            <x-performance.metric-card label="Triangles" :value="number_format($item['triangles'])" />
            <x-performance.metric-card label="Meshes" :value="$item['meshes']" />
            <x-performance.metric-card label="Materials" :value="$item['materials']" />
            <x-performance.metric-card label="Textures" :value="$item['textures']" :context="'Dimensions: '.$item['texture_dimensions']" />
            <x-performance.metric-card label="Texture memory" :value="$item['texture_memory']" context="Estimate" />
        </div>
    </section>

    <section class="space-y-3" aria-labelledby="used-in">
        <x-ui.section-label id="used-in">Tests that used this asset</x-ui.section-label>
        <x-ui.table caption="Tests that used this asset">
            <x-slot:head><tr><th scope="col">Test</th><th scope="col">Method</th><th scope="col" data-numeric>Date</th></tr></x-slot:head>
            @foreach ($item['tests'] as $test)
                <tr><th scope="row"><a class="link" href="{{ route('results.show', 1) }}">{{ $test['name'] }}</a></th><td>{{ $test['method'] }}</td><td data-numeric>{{ $test['date'] }}</td></tr>
            @endforeach
        </x-ui.table>
    </section>
</x-layouts::app>
