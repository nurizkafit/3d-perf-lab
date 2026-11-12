<x-layouts::app :title="'Edit '.$asset->name" description="Edit metadata only. The model file cannot be replaced, because a changed file would make old results invalid; upload a new asset instead." :sample="false">
    <form class="card max-w-3xl space-y-4" method="POST" action="{{ route('assets.update', $asset) }}">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input name="name" label="Asset name" :value="$asset->name" required />
            <x-ui.select name="tool_id" label="Tool" :options="$tools" :value="$asset->tool_id" />
            <x-ui.select name="object_id" label="Object" :options="$objects" :value="$asset->object_id" placeholder="Select an object" />
            @can('setAuthor', \App\Models\Asset::class)
                <x-ui.input name="author" label="Author" :value="$asset->author" required />
            @else
                <div>
                    <p class="field-label">Author</p>
                    <p class="flex min-h-[var(--control-height)] items-center">{{ $asset->author }}</p>
                    <p class="field-hint">Only an administrator can change the author.</p>
                </div>
            @endcan
            <x-ui.input name="production_time_minutes" label="Production time (min)" type="number" min="0" step="any" :value="(float) $asset->production_time_minutes" required />
        </div>
        <x-ui.textarea name="description" label="Description" :value="$asset->description" hint="Optional." />
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button type="submit" icon="floppy-disk">Save changes</x-ui.button>
            <x-ui.button :href="route('assets.show', $asset)" variant="outline">Cancel</x-ui.button>
        </div>
    </form>
</x-layouts::app>
