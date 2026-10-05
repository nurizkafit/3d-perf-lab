@php
    use App\Support\MetricDefinitions;
    use App\Support\TestResultPresenter as P;
    $group = $result->stressGroups()[0] ?? null;
    $stepItems = array_intersect_key(MetricDefinitions::BUDGET_ITEMS, array_flip(\App\Services\RunResultService::STEP_ITEMS));
    $summaryItems = array_diff_key(MetricDefinitions::budgetItemsFor('stress'), $stepItems);
    $metricRows = ['interval_p95_ms' => 'p95 frame interval (ms)', 'interval_p50_ms' => 'p50 frame interval (ms)', 'work_p95_ms' => 'Render work p95 (ms)', 'gpu_p95_ms' => 'GPU time p95 (ms)', 'jank_pct' => 'Jank (%)', 'draw_calls' => 'Draw calls', 'rendered_triangles' => 'Rendered triangles', 'fps_avg' => 'Average fps (familiarity only)'];
@endphp
@if ($group)
    <section class="space-y-3" aria-labelledby="stress-summary">
        <x-ui.section-label id="stress-summary">Budget result · {{ $group['label'] }}</x-ui.section-label>
        <x-ui.table caption="Budget items evaluated once">
            <x-slot:head><tr><th scope="col">Budget item</th><th scope="col" data-numeric>Limit</th><th scope="col">Result</th></tr></x-slot:head>
            @foreach ($summaryItems as $key => $item)
                @continue(! isset($group['summary_budget'][$key]))
                @php([$outcome, $measured, $limit] = $group['summary_budget'][$key])
                <tr>
                    <th scope="row" class="cell-wrap"><span class="block font-semibold">{{ $item['label'] }}@if ($item['unit']) ({{ $item['unit'] }})@endif</span><span class="block max-w-96 text-body-sm font-normal text-neutral-600">{{ $item['help'] }}</span></th>
                    <td data-numeric>{{ $item['rule'] }} {{ $limit }}</td>
                    <td><span class="flex items-center gap-2"><x-ui.badge :status="$outcome" /><span class="num">{{ $measured ?? '—' }}</span></span></td>
                </tr>
            @endforeach
        </x-ui.table>
    </section>

    <section class="space-y-3" aria-labelledby="stress-steps">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.section-label id="stress-steps">Budget at each step</x-ui.section-label>
            @if (collect($group['steps'])->contains('unstable', true))<x-ui.badge status="warning">Unstable</x-ui.badge>@endif
        </div>
        <x-ui.table caption="Budget at each instance step">
            <x-slot:head>
                <tr>
                    <th scope="col" data-numeric>Instances</th>
                    @foreach ($stepItems as $key => $item)<th scope="col">{{ $item['label'] }} ({{ $item['rule'] }} {{ $group['steps'][0]['budget'][$key][2] ?? '—' }}{{ $item['unit'] ? ' '.$item['unit'] : '' }})</th>@endforeach
                </tr>
            </x-slot:head>
            @foreach ($group['steps'] as $step)
                <tr>
                    <th scope="row" data-numeric class="font-semibold">{{ number_format($step['instances']) }}</th>
                    @foreach ($stepItems as $key => $item)
                        <td>
                            @isset($step['budget'][$key])
                                <span class="flex items-center gap-2"><x-ui.badge :status="$step['budget'][$key][0]" /><span class="num">{{ $step['budget'][$key][1] ?? '—' }}</span></span>
                            @else
                                <span class="text-neutral-600">—</span>
                            @endisset
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </x-ui.table>
    </section>

    <section class="space-y-3" aria-labelledby="stress-metrics">
        <x-ui.section-label id="stress-metrics">Metrics per step (median of repetitions, min–max)</x-ui.section-label>
        <x-ui.table caption="Metrics per instance step">
            <x-slot:head>
                <tr><th scope="col">Metric</th>@foreach ($group['steps'] as $step)<th scope="col" data-numeric>{{ number_format($step['instances']) }}</th>@endforeach</tr>
            </x-slot:head>
            @foreach ($metricRows as $key => $label)
                <tr>
                    <th scope="row" class="font-semibold">{{ $label }}</th>
                    @foreach ($group['steps'] as $step)<td data-numeric>{{ P::spread($step['summary'][$key] ?? null, in_array($key, ['draw_calls', 'rendered_triangles']) ? 0 : 2) }}</td>@endforeach
                </tr>
            @endforeach
        </x-ui.table>
        <p class="text-body-sm text-neutral-600">— means the browser did not expose the value. Frame interval is capped by the display refresh rate; render work time is not, so it shows the cost of more instances.</p>
    </section>

    <section class="space-y-3" aria-labelledby="stress-chart" data-results-charts>
        <script type="application/json" id="results-charts">@json(['stress' => $result->stressChart()])</script>
        <x-ui.section-label id="stress-chart">Chart</x-ui.section-label>
        <figure class="card space-y-2" data-size="sm">
            <figcaption class="text-label font-semibold">p95 frame interval and render work p95 against instance count</figcaption>
            <div data-chart="stress" role="img" aria-label="Line chart: p95 frame interval and render work time against instance count, with the budget frame time as a dashed line"></div>
        </figure>
    </section>
@endif
