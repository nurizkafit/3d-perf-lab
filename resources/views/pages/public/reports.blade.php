<x-layouts::public title="Reports" description="Published 3D render test reports.">
    <div class="page-inline page-block mx-auto max-w-[var(--content-max-width)] space-y-6">
        <header class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-h1 font-bold">Reports</h1>
            </div>
            <p class="max-w-[var(--readable-width)] text-neutral-600">Each report answers one question with measured evidence from one device class.</p>
        </header>

        {{-- Plain GET form: works without JavaScript, state lives in the URL. --}}
        <form method="GET" action="{{ route('reports.index') }}" role="search" class="card grid items-end gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,14rem),1fr))]" data-size="sm">
            <x-ui.input name="q" label="Search" type="search" :value="$filters['q']" placeholder="Title or question" />
            <x-ui.select name="method" label="Method" :options="collect($methods)->map(fn ($m) => $m['label'])->all()" :value="$filters['method']" placeholder="All methods" />
            <x-ui.select name="device" label="Device class" :options="array_combine($deviceClasses, $deviceClasses)" :value="$filters['device']" placeholder="All device classes" />
            <div class="flex gap-2">
                <x-ui.button type="submit" icon="magnifying-glass">Apply</x-ui.button>
                @if (array_filter($filters))
                    <x-ui.button :href="route('reports.index')" variant="ghost">Clear</x-ui.button>
                @endif
            </div>
        </form>

        @if ($reports->isEmpty())
            <x-ui.empty-state icon="magnifying-glass" :message="array_filter($filters) ? 'No published report matches these filters.' : 'No reports are published yet.'">
                <x-ui.button :href="route('reports.index')">Show all reports</x-ui.button>
            </x-ui.empty-state>
        @else
            <p class="text-body-sm text-neutral-600" role="status">{{ $reports->count() }} {{ Str::plural('report', $reports->count()) }}</p>
            <ul class="grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(min(100%,20rem),1fr))]">
                @foreach ($reports as $report)
                    <li><x-reports.report-card :report="$report" :methods="$methods" /></li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts::public>
