@props(['asset'])
<x-ui.card :href="route('assets.show', $asset['id'])" size="sm" class="flex h-full flex-col gap-3">
    <div class="grid aspect-[4/3] place-items-center rounded-md border border-neutral-200 bg-neutral-100 text-neutral-600">
        <x-ui.icon name="cube" size="md" label="Thumbnail placeholder" />
    </div>
    <div class="space-y-1">
        <h2 class="font-semibold">{{ $asset['name'] }}</h2>
        <p class="text-body-sm text-neutral-600">{{ $asset['tool'] }} · {{ $asset['author'] }}</p>
    </div>
    <div class="mt-auto flex flex-wrap items-center gap-2">
        <x-ui.badge>{{ $asset['format'] }}</x-ui.badge>
        <span class="num text-neutral-600">{{ $asset['size'] }} · {{ number_format($asset['triangles']) }} tris</span>
    </div>
</x-ui.card>
