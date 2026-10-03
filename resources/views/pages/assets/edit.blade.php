<x-layouts::app :title="'Edit '.$item['name']" description="Edit metadata only. The model file cannot be replaced; upload a new asset instead.">
    <form class="card max-w-3xl space-y-4" method="POST" action="#">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input name="name" label="Asset name" :value="$item['name']" />
            <x-ui.select name="tool" label="Tool" :options="['Blender' => 'Blender', 'Prisma3D' => 'Prisma3D', 'Spline' => 'Spline']" :value="$item['tool']" />
            <x-ui.input name="author" label="Author" :value="$item['author']" hint="Admins can change the author. Users see it read-only." />
            <x-ui.input name="production_time_hours" label="Production time (h)" type="number" min="0" step="0.25" :value="$item['hours']" />
        </div>
        <x-ui.textarea name="description" label="Description" :value="$item['description']" hint="Optional." />
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button type="submit" icon="floppy-disk" disabled>Save changes</x-ui.button>
            <x-ui.button :href="route('assets.show', $item['id'])" variant="outline">Cancel</x-ui.button>
            <span class="text-body-sm text-neutral-600">Saving arrives in M3.3.</span>
        </div>
    </form>
</x-layouts::app>
