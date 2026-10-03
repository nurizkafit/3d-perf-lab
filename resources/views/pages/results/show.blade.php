<x-layouts::app :title="$report['title']" :description="'Comparison Test · '.$report['device_class'].' · '.$report['budget_preset']">
    <x-slot:actions>
        <x-ui.button variant="secondary" icon="download-simple" disabled>JSON</x-ui.button>
        <x-ui.button variant="secondary" icon="download-simple" disabled>CSV</x-ui.button>
        <x-ui.button :href="route('results.report', 1)" icon="article">Report</x-ui.button>
    </x-slot:actions>

    <section class="space-y-3" aria-labelledby="tested">
        <x-ui.section-label id="tested">Tested assets</x-ui.section-label>
        <x-assets.tested-assets :assets="$assets" />
        <p class="text-body-sm text-neutral-600">Selection order. The run order was shuffled with a recorded seed; results are shown in this order.</p>
    </section>

    <section class="space-y-3" aria-labelledby="budget">
        <x-ui.section-label id="budget">Budget result</x-ui.section-label>
        <x-performance.budget-table :items="$budgetItems" :assets="$report['assets']" :budget="$report['budget']" :device-class="$report['device_class']" />
    </section>

    <section class="space-y-3" aria-labelledby="metrics">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.section-label id="metrics">Runtime metrics</x-ui.section-label>
            <x-ui.badge status="warning">Unstable</x-ui.badge>
        </div>
        <x-ui.table caption="Runtime metrics, median of repetitions (min–max)">
            <x-slot:head>
                <tr>
                    <th scope="col">Metric</th>
                    @foreach ($report['assets'] as $asset)
                        <th scope="col" data-numeric class="align-bottom">
                            <span class="block text-body-sm font-semibold text-neutral-950">{{ $asset['name'] }}</span>
                            <span class="block font-normal">{{ $asset['tool'] }}</span>
                        </th>
                    @endforeach
                </tr>
            </x-slot:head>
            @foreach ([
                'interval_p95_ms' => ['16.9 (16.7–17.4)', '17.2 (16.8–23.1)', '16.8 (16.7–17.0)'],
                'interval_p50_ms' => ['16.7', '16.7', '16.7'],
                'work_p95_ms' => ['1.9', '2.4', '0.8'],
                'gpu_p95_ms' => ['—', '—', '—'],
                'jank_pct' => ['0.4', '0.7', '—'],
                'ttff_cold_ms' => ['412.3', '388.1', '5,201.7'],
                'draw_calls' => ['3', '5', '1'],
            ] as $key => $values)
                @php($metric = $metrics[$key])
                <tr>
                    <th scope="row" class="cell-wrap">
                        <span class="block font-semibold">{{ $metric['label'] }}@if ($metric['unit']) ({{ $metric['unit'] }})@endif</span>
                        <span class="block max-w-80 text-body-sm font-normal text-neutral-600">{{ $metric['help'] }}</span>
                    </th>
                    @foreach ($values as $value)<td data-numeric>{{ $value }}</td>@endforeach
                </tr>
            @endforeach
        </x-ui.table>
        <div class="card space-y-2 text-body-sm" data-size="sm">
            <p class="font-semibold">How to read this table</p>
            <ul class="list-disc space-y-1 ps-5 text-neutral-600">
                <li>Each value is the median of the repetitions; the range in brackets is min–max.</li>
                <li>Frame interval is what the viewer sees and is capped by the display refresh rate. Render work time is the cost and is not capped.</li>
                <li>— means the browser did not expose the value (here: GPU time). It is never estimated.</li>
                <li>Unstable: the spread of p95 frame interval for Chicken is more than 25% of its median.</li>
            </ul>
        </div>
    </section>

    <section class="space-y-3" aria-labelledby="charts">
        <x-ui.section-label id="charts">Charts</x-ui.section-label>
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,20rem),1fr))]">
            @foreach (['Load phases (stacked bar)', 'Frame-interval distribution'] as $chart)
                <div class="viewer-frame min-h-56" role="img" aria-label="{{ $chart }} placeholder">
                    <div class="viewer-message"><x-ui.icon name="chart-bar" size="md" /><p>{{ $chart }}. Charts arrive in M4.1.</p></div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="space-y-3" aria-labelledby="env">
        <x-ui.section-label id="env">Environment</x-ui.section-label>
        <dl class="card grid grid-cols-[minmax(0,auto)_1fr] gap-x-4 gap-y-2 text-body-sm sm:grid-cols-[minmax(0,auto)_1fr_minmax(0,auto)_1fr]" data-size="sm">
            <dt class="text-neutral-600">Device class</dt><dd>{{ $report['device_class'] }}</dd>
            @foreach ($report['environment'] as $key => $value)
                <dt class="text-neutral-600">{{ $key }}</dt><dd>{{ $value }}</dd>
            @endforeach
        </dl>
    </section>
</x-layouts::app>
