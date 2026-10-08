@props(['asset'])
<x-ui.card :href="route('assets.show', $asset)" size="sm" class="flex h-full flex-col gap-3">
    <div class="grid aspect-[4/3] place-items-center overflow-hidden rounded-md border border-neutral-200 bg-neutral-100 text-neutral-600">
        @if ($asset->thumbnail_path)
            <img src="{{ route('assets.thumbnail', $asset) }}" alt="" loading="lazy" class="size-full object-contain">
        @else
            <x-ui.icon name="cube" size="md" />
        @endif
    </div>
    <div class="space-y-1">
        <h2 class="font-semibold">{{ $asset->name }}</h2>
        <p class="text-body-sm text-neutral-600">{{ $asset->tool->name }}@if ($asset->object) · {{ $asset->object->name }}@endif · {{ $asset->author }}</p>
    </div>
    <div class="mt-auto flex flex-wrap items-center gap-2">
        <x-ui.badge>{{ strtoupper($asset->format) }}</x-ui.badge>
        @if ($asset->trashed())<x-ui.badge variant="warning" icon="trash">Deleted</x-ui.badge>@endif
        <span class="num text-neutral-600">{{ \Illuminate\Support\Number::fileSize($asset->file_size, maxPrecision: 1) }}@if ($asset->metrics) · {{ number_format($asset->metrics->triangles) }} tris @endif</span>
    </div>
</x-ui.card>
