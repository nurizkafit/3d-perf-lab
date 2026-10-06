@php
    $methods = \App\Support\SampleReports::METHODS;
    $run = $result->run;
    $aggregates = $result->aggregates();
    $assets = $result->assetModels();
    $loadRows = ['ttff_ms' => 'Time to first frame', 'fetch_ms' => 'Download', 'parse_ms' => 'Parse', 'gpu_upload_ms' => 'GPU upload', 'first_frame_ms' => 'First frame', 'transfer_size' => 'Transfer size (bytes)'];
@endphp
<x-layouts::app :title="$test->name" :description="$methods[$test->method]['label'].' · '.($run?->deviceClass?->name ?? 'No run yet').' · '.($test->budget_json['preset_name'] ?? '')" :sample="false">
    <x-slot:actions>
        <x-ui.button variant="secondary" icon="download-simple" disabled title="Export arrives in M4.5">JSON</x-ui.button>
        <x-ui.button variant="secondary" icon="download-simple" disabled title="Export arrives in M4.5">CSV</x-ui.button>
    </x-slot:actions>

    @if (! $run)
        <p class="alert-box" role="status"><x-ui.icon name="info" />This test has not been run yet.</p>
    @elseif (! $result->completed())
        <p class="alert-box" data-error role="status"><x-ui.icon name="warning-circle" />
            The last run is {{ $run->status }}{{ $run->invalid_reason ? ': '.$run->invalid_reason : '.' }} Invalid and cancelled runs never produce budget results.</p>
    @else
        @if ($test->method === 'headroom')
            <p class="text-body-sm" role="status">Completed {{ $run->completed_at->format('Y-m-d H:i') }}. Headroom: {{ collect($result->headroomGroups())->map(fn ($g) => $g['label'].' '.$g['display'])->join(' · ') }}.</p>
        @elseif ($test->method === 'stress')
            <p class="text-body-sm" role="status">Completed {{ $run->completed_at->format('Y-m-d H:i') }}. Instances at budget: {{ $run->evaluations->firstWhere('item_key', 'min_instances_at_budget')?->measured_value ?? \App\Support\TestResultPresenter::largestPassingStep($run) ?? '—' }}.</p>
        @else
            <p class="text-body-sm" role="status">{{ $result->meetingAll() }} of {{ $result->assetCount() }} {{ Str::plural('asset', $result->assetCount()) }} meet all enabled budget items. Completed {{ $run->completed_at->format('Y-m-d H:i') }}.</p>
        @endif
    @endif

    <section class="space-y-3" aria-labelledby="tested">
        <x-ui.section-label id="tested">Tested assets</x-ui.section-label>
        <x-assets.tested-assets :assets="$result->assets()" />
        @if ($test->method === 'comparison')
            <p class="text-body-sm text-neutral-600">Selection order. The run order was shuffled with a recorded seed; results are shown in this order.</p>
        @elseif ($test->scenario === 'mixed')
            <p class="text-body-sm text-neutral-600">Selection order. Instances are assigned round-robin in this order.</p>
        @endif
    </section>

    @if ($result->completed() && $test->method === 'stress')
        @include('pages.results.partials.stress')
    @elseif ($result->completed() && $test->method === 'headroom')
        @include('pages.results.partials.headroom')
    @elseif ($result->completed())
        @php($budgetAssets = $assets->map(fn ($a) => ['name' => $a->name, 'tool' => $a->tool->name, 'format' => strtoupper($a->format), 'author' => $a->author])->all())
        <section class="space-y-3" aria-labelledby="budget">
            <x-ui.section-label id="budget">Budget result</x-ui.section-label>
            <x-performance.budget-table :items="$budgetItems" :assets="$budgetAssets" :budget="$result->budget()" :device-class="$run->deviceClass->name" />
        </section>

        <section class="space-y-3" aria-labelledby="metrics">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.section-label id="metrics">Runtime metrics</x-ui.section-label>
                @if (collect($aggregates)->contains('unstable', true))<x-ui.badge status="warning">Unstable</x-ui.badge>@endif
            </div>
            <x-ui.table caption="Runtime metrics, median of repetitions (min–max)">
                <x-slot:head>
                    <tr>
                        <th scope="col">Metric</th>
                        @foreach ($assets as $asset)
                            <th scope="col" data-numeric class="align-bottom"><span class="block text-body-sm font-semibold text-neutral-950">{{ $asset->name }}</span><span class="block font-normal">{{ $asset->tool->name }}</span></th>
                        @endforeach
                    </tr>
                </x-slot:head>
                @foreach (\App\Support\TestResultPresenter::RUNTIME_ROWS as $key)
                    @php($metric = $metrics[$key])
                    <tr>
                        <th scope="row" class="cell-wrap">
                            <span class="block font-semibold">{{ $metric['label'] }}@if ($metric['unit']) ({{ $metric['unit'] }})@endif</span>
                            <span class="block max-w-80 text-body-sm font-normal text-neutral-600">{{ $metric['help'] }}</span>
                        </th>
                        @foreach ($assets as $asset)
                            <td data-numeric>{{ \App\Support\TestResultPresenter::spread($aggregates[$asset->id]['summary'][$key] ?? null, $key === 'draw_calls' ? 0 : 2) }}</td>
                        @endforeach
                    </tr>
                @endforeach
                <tr>
                    <th scope="row" class="cell-wrap"><span class="block font-semibold">Average frame rate (fps)</span><span class="block max-w-80 text-body-sm font-normal text-neutral-600">For familiarity only; never used for a budget.</span></th>
                    @foreach ($assets as $asset)<td data-numeric>{{ \App\Support\TestResultPresenter::spread($aggregates[$asset->id]['summary']['fps_avg'] ?? null) }}</td>@endforeach
                </tr>
                <tr>
                    <th scope="row" class="cell-wrap"><span class="block font-semibold">JS heap (bytes)</span><span class="block max-w-80 text-body-sm font-normal text-neutral-600">Browser-reported, JS heap only (Chromium).</span></th>
                    @foreach ($assets as $asset)<td data-numeric>{{ \App\Support\TestResultPresenter::spread($aggregates[$asset->id]['summary']['js_heap_used'] ?? null, 0) }}</td>@endforeach
                </tr>
            </x-ui.table>
            <div class="card space-y-2 text-body-sm" data-size="sm">
                <p class="font-semibold">How to read this table</p>
                <ul class="list-disc space-y-1 ps-5 text-neutral-600">
                    <li>Each value is the median of the repetitions; the range in brackets is min–max.</li>
                    <li>Frame interval is what the viewer sees and is capped by the display refresh rate. Render work time is the cost and is not capped.</li>
                    <li>— means the browser did not expose the value. It is never estimated.</li>
                    @foreach ($assets as $asset)
                        @if ($aggregates[$asset->id]['unstable'])<li>Unstable: the spread of p95 frame interval for {{ $asset->name }} is more than 25% of its median.</li>@endif
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="space-y-3" aria-labelledby="load">
            <x-ui.section-label id="load">Load phases (median, cold · warm)</x-ui.section-label>
            <x-ui.table caption="Load phases">
                <x-slot:head>
                    <tr><th scope="col">Phase (ms)</th>@foreach ($assets as $asset)<th scope="col" data-numeric>{{ $asset->name }}</th>@endforeach</tr>
                </x-slot:head>
                @foreach ($loadRows as $key => $label)
                    <tr>
                        <th scope="row" class="font-semibold">{{ $label }}</th>
                        @foreach ($assets as $asset)
                            @php($load = $aggregates[$asset->id]['load'])
                            <td data-numeric>{{ \App\Support\TestResultPresenter::spread($load['cold'][$key] ?? null, $key === 'transfer_size' ? 0 : 1) }} · {{ \App\Support\TestResultPresenter::spread($load['warm'][$key] ?? null, $key === 'transfer_size' ? 0 : 1) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </x-ui.table>
        </section>

        <section class="space-y-3" aria-labelledby="charts" data-results-charts>
            <script type="application/json" id="results-charts">@json($result->charts())</script>
            <x-ui.section-label id="charts">Charts</x-ui.section-label>
            <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr))]">
                <figure class="card space-y-2" data-size="sm">
                    <figcaption class="text-label font-semibold">Load phases, cold (median ms)</figcaption>
                    <div data-chart="load-cold" role="img" aria-label="Stacked bars: download, parse, GPU upload, and first frame per asset, cold load"></div>
                </figure>
                <figure class="card space-y-2" data-size="sm">
                    <figcaption class="text-label font-semibold">Load phases, warm (median ms)</figcaption>
                    <div data-chart="load-warm" role="img" aria-label="Stacked bars: download, parse, GPU upload, and first frame per asset, warm load"></div>
                </figure>
            </div>
            <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr))]">
                @foreach ($assets as $index => $asset)
                    <figure class="card space-y-2" data-size="sm">
                        <figcaption class="text-label font-semibold">{{ $asset->name }} · frame-interval distribution (all repetitions)</figcaption>
                        <div data-chart="histogram" data-index="{{ $index }}" role="img" aria-label="Histogram of frame intervals for {{ $asset->name }}"></div>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="space-y-3" aria-labelledby="config">
            <x-ui.section-label id="config">Method and configuration</x-ui.section-label>
            <dl class="card grid grid-cols-[minmax(0,auto)_1fr] gap-x-4 gap-y-2 text-body-sm" data-size="sm">
                <dt class="text-neutral-600">Method</dt><dd>{{ $methods[$test->method]['label'] }}</dd>
                @foreach ($result->configuration() as $label => $value)<dt class="text-neutral-600">{{ $label }}</dt><dd>{{ $value }}</dd>@endforeach
            </dl>
        </section>
        @if ($run)
            <section class="space-y-3" aria-labelledby="env">
                <x-ui.section-label id="env">Environment</x-ui.section-label>
                <dl class="card grid grid-cols-[minmax(0,auto)_1fr] gap-x-4 gap-y-2 text-body-sm" data-size="sm">
                    @foreach ($result->environment() as $label => $value)<dt class="text-neutral-600">{{ $label }}</dt><dd class="break-words">{{ $value }}</dd>@endforeach
                </dl>
            </section>
        @endif
    </div>
</x-layouts::app>
