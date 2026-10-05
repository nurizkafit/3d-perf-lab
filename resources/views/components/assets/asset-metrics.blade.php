@props(['asset'])
@php
    $m = $asset->metrics;
    $dimensions = collect($m?->texture_dimensions_json ?? [])->map(fn ($pair) => $pair[0].'×'.$pair[1])->join(', ');
@endphp
{{-- Asset metrics come from the file (BENCHMARK.md §3.1). Runtime metrics are shown separately, in test results. --}}
<div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,10rem),1fr))]">
    <x-performance.metric-card label="Format" :value="strtoupper($asset->format)" />
    <x-performance.metric-card label="File size" :value="\Illuminate\Support\Number::fileSize($asset->file_size, maxPrecision: 1)" :context="$asset->format === 'gltf' ? 'Including .bin and textures' : null" />
    @if ($m)
        <x-performance.metric-card label="Vertices" :value="number_format($m->vertices)" />
        <x-performance.metric-card label="Triangles" :value="number_format($m->triangles)" />
        <x-performance.metric-card label="Meshes" :value="number_format($m->meshes)" />
        <x-performance.metric-card label="Materials" :value="number_format($m->materials)" />
        <x-performance.metric-card label="Textures" :value="number_format($m->textures)" :context="$dimensions ? 'Dimensions: '.$dimensions : null" />
        <x-performance.metric-card label="Texture memory" :value="$m->texture_memory_estimate_bytes === null ? '—' : \Illuminate\Support\Number::fileSize($m->texture_memory_estimate_bytes, maxPrecision: 1)" context="Estimate, not measured" />
    @endif
</div>
@unless ($m)
    <p class="text-body-sm text-neutral-600">Geometry and texture counts were not recorded, because the browser could not read the model at upload.</p>
@endunless
