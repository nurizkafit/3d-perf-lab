<x-layouts::public :title="$report['title']" :description="$report['question']">
    <article class="page-inline page-block mx-auto max-w-[var(--content-max-width)] space-y-10">
        <header class="space-y-4">
            <a href="{{ route('reports.index') }}" class="link inline-flex items-center gap-1 text-body-sm"><x-ui.icon name="arrow-left" /> All reports</a>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge variant="primary" :icon="$method['icon']">{{ $method['label'] }}</x-ui.badge>
                @if ($report['scenario'])<x-ui.badge variant="outline">{{ ucfirst($report['scenario']) }} asset</x-ui.badge>@endif
                <x-ui.badge variant="outline" icon="device-mobile">{{ $report['device_class'] }}</x-ui.badge>
                <x-ui.badge variant="sample">Sample data</x-ui.badge>
            </div>
            <h1 class="text-h1 font-bold">{{ $report['title'] }}</h1>
            {{-- The question is the page lead (DESIGN.md §12). --}}
            <p class="max-w-[var(--readable-width)] text-h3 text-neutral-950">{{ $report['question'] }}</p>
            <p class="text-body-sm text-neutral-600">Published <time datetime="{{ $report['published_at'] }}">{{ \Illuminate\Support\Carbon::parse($report['published_at'])->format('j F Y') }}</time> · Budget preset: {{ $report['budget_preset'] }}</p>
        </header>

        <x-reports.report-section id="budget" label="Budget result" title="Does each asset meet the budget?">
            <x-performance.budget-table :items="$budgetItems" :assets="$report['assets']" :budget="$report['budget']" :device-class="$report['device_class']" />
            <p class="text-body-sm text-neutral-600">Measured values are medians of the repetitions. Assets are shown in selection order, not sorted by result.</p>
        </x-reports.report-section>

        <x-reports.report-section id="reproduction" label="Interactive reproduction">
            <x-performance.viewer mode="reproduction" label="Interactive reproduction" :state="$previewState" placeholder="The interactive reproduction arrives with published reports (M4.5)." />
            <p class="text-body-sm text-neutral-600">This scene rebuilds the test setup for viewing only. Your device is not measured; the numbers on this page are the recorded results.</p>
        </x-reports.report-section>

        <x-reports.report-section id="evidence" label="Evidence">
            <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,14rem),1fr))]">
                @foreach ($report['assets'] as $index => $asset)
                    @php($p95 = $report['budget']['max_p95_frame_ms'][$index + 1][1] ?? null)
                    <div class="card space-y-1" data-size="sm">
                        <p class="text-label font-semibold">{{ $asset['name'] }} · p95 frame interval</p>
                        <p class="font-heading text-metric font-bold">{{ $p95 !== null ? $p95.' ms' : '—' }}</p>
                        <p class="text-body-sm text-neutral-600">{{ $asset['tool'] }} · {{ $asset['author'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="viewer-frame min-h-48" role="img" aria-label="Chart placeholder">
                <div class="viewer-message"><x-ui.icon name="chart-line" size="md" /><p>Charts arrive with the chart library in M4.1.</p></div>
            </div>
        </x-reports.report-section>

        <x-reports.report-section id="assets" label="Asset characteristics">
            <x-ui.table caption="Asset characteristics">
                <x-slot:head>
                    <tr><th scope="col">Asset</th><th scope="col">Tool</th><th scope="col">Author</th><th scope="col" data-numeric>File size (MB)</th><th scope="col" data-numeric>Triangles</th></tr>
                </x-slot:head>
                @foreach ($report['assets'] as $asset)
                    <tr>
                        <th scope="row" class="font-semibold">{{ $asset['name'] }}</th>
                        <td>{{ $asset['tool'] }}</td>
                        <td>{{ $asset['author'] }}</td>
                        <td data-numeric>{{ $asset['file_size_mb'] }}</td>
                        <td data-numeric>{{ number_format($asset['triangles']) }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-reports.report-section>

        <div class="grid gap-10 lg:grid-cols-2">
            <x-reports.report-section id="configuration" label="Method and configuration">
                <dl class="card grid grid-cols-[minmax(0,auto)_1fr] gap-x-4 gap-y-2 text-body-sm" data-size="sm">
                    @foreach ($report['configuration'] as $key => $value)
                        <dt class="text-neutral-600">{{ $key }}</dt><dd>{{ $value }}</dd>
                    @endforeach
                </dl>
            </x-reports.report-section>

            <x-reports.report-section id="environment" label="Environment">
                <dl class="card grid grid-cols-[minmax(0,auto)_1fr] gap-x-4 gap-y-2 text-body-sm" data-size="sm">
                    <dt class="text-neutral-600">Device class</dt><dd>{{ $report['device_class'] }}</dd>
                    @foreach ($report['environment'] as $key => $value)
                        <dt class="text-neutral-600">{{ $key }}</dt><dd>{{ $value }}</dd>
                    @endforeach
                </dl>
            </x-reports.report-section>
        </div>

        <x-reports.report-section id="limitations" label="Limitations">
            <ul class="card list-disc space-y-1 ps-10 text-body-sm">
                @foreach ([...$standardLimitations, ...$report['limitations']] as $limitation)
                    <li>{{ $limitation }}</li>
                @endforeach
            </ul>
        </x-reports.report-section>

        <x-reports.report-section id="conclusion" label="Author's conclusion">
            @if ($report['conclusion'])
                <p class="max-w-[var(--readable-width)]">{{ $report['conclusion'] }}</p>
            @else
                <p class="text-neutral-600">The author did not add a conclusion.</p>
            @endif
        </x-reports.report-section>
    </article>
</x-layouts::public>
