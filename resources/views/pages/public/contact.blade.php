<x-layouts::public title="Contact">
    <div class="page-inline page-block mx-auto max-w-[var(--readable-width)] space-y-6">
        <header class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-h1 font-bold">Contact</h1>
                <x-ui.badge variant="warning" icon="warning">Placeholder</x-ui.badge>
            </div>
        </header>
        {{-- Never invent contact data (DESIGN.md §12). --}}
        <x-ui.empty-state icon="envelope-simple" message="Contact methods have not been added yet. The site owner will publish them here." />
    </div>
</x-layouts::public>
