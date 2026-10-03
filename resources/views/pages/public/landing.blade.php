<x-layouts::public>
    {{-- One viewport (DESIGN.md §12): name, description, three points, links. No metrics. --}}
    <div class="page-inline mx-auto flex min-h-[calc(100svh-var(--header-height))] max-w-[var(--content-max-width)] flex-col justify-center gap-8 py-10">
        <div class="max-w-[var(--readable-width)] space-y-4">
            <p class="section-label">3D Render Test Dashboard</p>
            <h1 class="font-heading text-display font-bold">Render Test</h1>
            <p class="text-h3 text-neutral-600">Measure how 3D web assets load and render in real browsers, on real devices, against a clear performance budget.</p>
        </div>

        <ul class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,16rem),1fr))]">
            <li class="card space-y-2">
                <x-ui.icon name="cube" size="md" />
                <h2 class="text-h3 font-semibold">Register models</h2>
                <p class="text-body-sm text-neutral-600">Upload GLB and GLTF files with their tool, author, and production time.</p>
            </li>
            <li class="card space-y-2">
                <x-ui.icon name="flask" size="md" />
                <h2 class="text-h3 font-semibold">Run repeatable tests</h2>
                <p class="text-body-sm text-neutral-600">Comparison, Stress, and Headroom tests run in the browser with a fixed protocol.</p>
            </li>
            <li class="card space-y-2">
                <x-ui.icon name="check-circle" size="md" />
                <h2 class="text-h3 font-semibold">Check the budget</h2>
                <p class="text-body-sm text-neutral-600">Every result shows Pass or Fail for each budget item. No scores, no rankings.</p>
            </li>
        </ul>

        <div class="flex flex-wrap gap-3">
            <x-ui.button :href="route('reports.index')" icon="article">Browse reports</x-ui.button>
            <x-ui.button :href="route('about')" variant="secondary">About the method</x-ui.button>
        </div>
    </div>
</x-layouts::public>
