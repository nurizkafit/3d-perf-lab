@props(['report', 'methods'])
@php($method = $methods[$report['method']])
{{-- Gallery card: title, method, device class, date, asset count, question. No winner markers (DESIGN.md §12). --}}
<x-ui.card :href="route('reports.show', $report['slug'])" class="flex h-full flex-col gap-3">
    <div class="flex flex-wrap items-center gap-2">
        <x-ui.badge variant="primary" :icon="$method['icon']">{{ $method['label'] }}</x-ui.badge>
        <x-ui.badge variant="sample">Sample data</x-ui.badge>
    </div>
    <h2 class="text-h3 font-semibold">{{ $report['title'] }}</h2>
    <p class="line-clamp-2 text-body-sm text-neutral-600">{{ $report['question'] }}</p>
    <dl class="mt-auto grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-body-sm">
        <dt class="text-neutral-600">Device</dt><dd>{{ $report['device_class'] }}</dd>
        <dt class="text-neutral-600">Assets</dt><dd>{{ count($report['assets']) }}</dd>
        <dt class="text-neutral-600">Published</dt><dd><time datetime="{{ $report['published_at'] }}">{{ \Illuminate\Support\Carbon::parse($report['published_at'])->display()->format('j M Y') }}</time></dd>
    </dl>
</x-ui.card>
