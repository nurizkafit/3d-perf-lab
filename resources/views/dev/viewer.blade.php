<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Viewer lab — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-100 text-neutral-950">
<main class="mx-auto max-w-[var(--content-max-width)] space-y-8 px-[var(--page-padding-inline)] py-[var(--page-padding-block)]" data-viewer-lab>
    <header class="space-y-2">
        <p class="section-label">Local only · M3.1</p>
        <h1 class="text-h1 font-bold">Viewer lab</h1>
        <p class="max-w-[var(--readable-width)] text-neutral-600">Every file in <code>fixtures/models/</code> in the shared viewer (preview mode), with the metrics from <code>inspect.js</code> and a rendered thumbnail. Development fixtures only.</p>
    </header>

    @forelse ($fixtures as $fixture)
        <section class="card space-y-4" aria-labelledby="fx-{{ $loop->index }}" data-fixture="{{ $fixture }}">
            <h2 id="fx-{{ $loop->index }}" class="text-h3 font-semibold">{{ $fixture }}</h2>
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                <x-performance.viewer :src="route('dev.fixture', $fixture)" :config="$config" />
                <div class="space-y-3">
                    <x-ui.table caption="Metrics for {{ $fixture }}">
                        <x-slot:head><tr><th scope="col">Metric</th><th scope="col" data-numeric>Value</th></tr></x-slot:head>
                        @foreach (['vertices', 'triangles', 'meshes', 'materials', 'textures', 'texture_dimensions', 'texture_memory_estimate', 'fetch_ms', 'parse_ms'] as $key)
                            <tr><th scope="row" class="font-semibold">{{ $key }}</th><td data-numeric data-metric="{{ $key }}">…</td></tr>
                        @endforeach
                    </x-ui.table>
                    <figure class="space-y-1">
                        <img data-thumbnail alt="Thumbnail of {{ $fixture }}" width="160" height="160" class="aspect-square w-40 rounded-md border border-neutral-200 bg-neutral-0">
                        <figcaption class="text-body-sm text-neutral-600" data-thumbnail-info>Thumbnail: waiting for the model.</figcaption>
                    </figure>
                </div>
            </div>
        </section>
    @empty
        <x-ui.empty-state icon="cube" message="No .glb or .gltf files in fixtures/models/." />
    @endforelse

    <section class="card space-y-3" aria-labelledby="memory">
        <h2 id="memory" class="text-h3 font-semibold">Mount / unmount test</h2>
        <p class="text-body-sm text-neutral-600">Mounts a viewer, loads the first fixture, and disposes it, 20 times. Then loads and clears one model 20 times in one viewer and compares <code>renderer.info.memory</code> with the empty baseline.</p>
        <x-ui.button variant="secondary" icon="play" data-memory-test>Run test</x-ui.button>
        <div class="viewer-frame min-h-48" data-memory-stage></div>
        <pre class="num whitespace-pre-wrap" data-memory-result role="status"></pre>
    </section>
</main>
</body>
</html>
