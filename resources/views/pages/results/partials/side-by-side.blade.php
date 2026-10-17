{{-- Side-by-Side results (BENCHMARK.md §4.3–§4.5). --}}
@php
    use App\Support\BestMarkers;
    use App\Support\MetricDefinitions as Defs;
    use App\Support\SideBySideResults;
    use Illuminate\Support\Number;

    $methods = Defs::METHODS;
    $run = $results['run'] ?? $latest;
    $ms = fn ($value, $digits = 1) => $value === null ? '—' : number_format($value, $digits);
    $seconds = fn ($value) => $value === null ? '—' : number_format($value / 1000, 2).' s';
    $spread = fn ($summary, $format) => $summary === null ? '' : ($summary['n'] > 1 ? $format($summary['min']).'–'.$format($summary['max']) : '');
    $fps = fn ($value) => $value === null ? '—' : number_format($value, 0).' fps';
    $checkValue = fn ($check) => match ($check['check_key']) {
        'load' => ($check['measured_value'] === null ? '—' : number_format($check['measured_value'], 2).' s').' / ≤ '.rtrim(rtrim(number_format($check['limit_value'], 2), '0'), '.').' s',
        'file_size' => ($check['measured_value'] === null ? '—' : number_format($check['measured_value'], 2).' MB').' / ≤ '.rtrim(rtrim(number_format($check['limit_value'], 2), '0'), '.').' MB',
        default => $fps($check['measured_value']).' at p95 / '.number_format($check['limit_value'], 0).' fps',
    };
    $checkLabel = fn ($check) => Defs::TARGET_CHECKS[$check['check_key']]['label'].($check['motion'] ? ' · '.Defs::MOTIONS[$check['motion']]['label'] : '');
    $marker = fn ($result, $id) => in_array($id, $result['winners'], true);
@endphp

    @php($assets = $results['assets'])
    @php($markers = $results['markers'])

    {{-- Results on the panes (BENCHMARK.md §4.3). The look phase works again here; it is not measured. --}}
    <section class="space-y-4" aria-labelledby="panes-title" data-linked-panes>
        <script type="application/json" data-panes-config>@json($viewer)</script>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <x-ui.section-label id="panes-title" as="h2">Results on the panes</x-ui.section-label>
                <p class="text-body-sm text-neutral-600">Selection order. Target checks, smoothness per motion scenario, and the best markers each asset earned. The 3D view is not measured.</p>
            </div>
        </div>
        <x-performance.pane-toolbar name="results-motion" />
        <div class="pane-grid">
            @foreach ($assets as $id => $entry)
                @php($asset = $entry['asset'])
                <figure class="pane card" data-size="sm" data-pane="{{ $id }}">
                    <figcaption class="pane-meta">
                        <span class="font-semibold">{{ $asset->name }}</span>
                        <span class="text-neutral-600">{{ collect([$asset->tool->name, $asset->object?->name, Number::fileSize($asset->file_size, maxPrecision: 1), $asset->metrics ? number_format($asset->metrics->triangles).' triangles' : null])->filter()->join(' · ') }}</span>
                    </figcaption>
                    <div class="viewer-frame pane-view" data-pane-frame data-state="loading">
                        <div class="viewer-stage" data-pane-stage></div>
                        <div class="viewer-message viewer-overlay" data-show-when="loading" role="status">
                            <x-ui.icon name="circle-notch" size="md" class="spin" />
                            <p>Loading the 3D model…</p>
                        </div>
                        <div class="viewer-message viewer-overlay" data-show-when="error" role="alert">
                            <x-ui.icon name="warning-circle" size="md" />
                            <p data-pane-error>The model could not be shown. The recorded results are not affected.</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        @if ($entry['markers'])
                            <ul class="flex flex-wrap gap-2" aria-label="Best markers">
                                @foreach ($entry['markers'] as $name)
                                    <li><x-ui.badge variant="outline" icon="trophy">{{ $name }}</x-ui.badge></li>
                                @endforeach
                            </ul>
                        @endif
                        <ul class="flex flex-wrap gap-2" aria-label="Smoothness per motion scenario">
                            @foreach ($results['motions'] as $motion)
                                @php($label = $entry['labels'][$motion] ?? null)
                                <li title="{{ $label && $label['label'] ? Defs::SMOOTHNESS[$label['label']]['help'] : 'Not measured' }}">
                                    <x-ui.badge>{{ Defs::MOTIONS[$motion]['label'] }}: {{ $label && $label['label'] ? Defs::SMOOTHNESS[$label['label']]['label'].' · '.$fps($label['fps']) : 'Not measured' }}</x-ui.badge>
                                </li>
                            @endforeach
                        </ul>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-body-sm">
                            <dt class="text-neutral-600">Cold load (median)</dt><dd class="num">{{ $seconds($entry['cold']['median'] ?? null) }}</dd>
                            <dt class="text-neutral-600">File size</dt><dd class="num">{{ Number::fileSize($asset->file_size, maxPrecision: 2) }}</dd>
                        </dl>
                        <ul class="space-y-1" aria-label="Target checks">
                            @foreach ($entry['checks'] as $check)
                                <li class="flex flex-wrap items-center gap-2 text-body-sm">
                                    <x-ui.badge :status="str_replace('_', '-', $check['outcome'])" />
                                    <span class="font-semibold">{{ $checkLabel($check) }}</span>
                                    <span class="num text-neutral-600">{{ $checkValue($check) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="text-body-sm font-semibold">Meets all Target checks: {{ $entry['meets_all'] ? 'Yes' : 'No' }}</p>
                    </div>
                </figure>
            @endforeach
        </div>
    </section>

    {{-- Plain values per asset, selection order (BENCHMARK.md §4.3). Markers never re-sort the columns. --}}
    <section class="space-y-3" aria-labelledby="values-title">
        <x-ui.section-label id="values-title" as="h2">Values</x-ui.section-label>
        <x-ui.table caption="Values per asset in selection order">
            <x-slot:head>
                <tr>
                    <th scope="col">Value</th>
                    @foreach ($assets as $entry)
                        <th scope="col" data-numeric>{{ $entry['asset']->name }}<span class="block font-normal text-neutral-600">{{ $entry['asset']->tool->name }} · {{ strtoupper($entry['asset']->format) }} · {{ $entry['asset']->author }}</span></th>
                    @endforeach
                </tr>
            </x-slot:head>
            <tr>
                <th scope="row">Time to first frame, cold (s)<span class="block text-body-sm font-normal text-neutral-600">{{ Defs::METRICS['ttff_cold_ms']['help'] }} Median of the repetitions; min–max below.</span>
                    @if ($markers['fastest_load']['too_close'])<x-ui.badge variant="outline" icon="equals" title="The min–max spreads overlap, so no asset gets the marker.">Too close to call</x-ui.badge>@endif
                </th>
                @foreach ($assets as $id => $entry)
                    <td data-numeric>{{ $seconds($entry['cold']['median'] ?? null) }}<span class="block text-neutral-600">{{ $spread($entry['cold'], $seconds) }}</span>
                        @if ($marker($markers['fastest_load'], $id))<x-ui.badge variant="outline" icon="trophy">Fastest load</x-ui.badge>@endif</td>
                @endforeach
            </tr>
            <tr>
                <th scope="row">Time to first frame, warm (s)<span class="block text-body-sm font-normal text-neutral-600">The same file again from the browser cache.</span></th>
                @foreach ($assets as $entry)
                    <td data-numeric>{{ $seconds($entry['warm']['median'] ?? null) }}</td>
                @endforeach
            </tr>
            <tr>
                <th scope="row">File size<span class="block text-body-sm font-normal text-neutral-600">{{ Defs::TARGET_FIELDS['max_file_size_mb']['help'] }}</span></th>
                @foreach ($assets as $id => $entry)
                    <td data-numeric>{{ Number::fileSize($entry['asset']->file_size, maxPrecision: 2) }}
                        @if ($marker($markers['smallest_file'], $id))<x-ui.badge variant="outline" icon="trophy">Smallest file</x-ui.badge>@endif</td>
                @endforeach
            </tr>
            @foreach ($results['motions'] as $motion)
                @php($lightest = $markers['lightest'][$motion])
                <tr>
                    <th scope="row">{{ Defs::MOTIONS[$motion]['label'] }}: smoothness<span class="block text-body-sm font-normal text-neutral-600">Label at the Target frame rate ({{ $test->target_json['target_fps'] }} fps), with the frame rate that 95% of frames reach.</span></th>
                    @foreach ($assets as $entry)
                        @php($label = $entry['labels'][$motion] ?? null)
                        <td data-numeric>{{ $label && $label['label'] ? Defs::SMOOTHNESS[$label['label']]['label'] : 'Not measured' }}<span class="block text-neutral-600">{{ $fps($label['fps'] ?? null) }}</span>
                            @if ($entry['unstable'][$motion] ?? false)<x-ui.badge variant="warning" icon="warning" title="The spread of the p95 frame interval across repetitions is more than 25% of the median.">Unstable</x-ui.badge>@endif</td>
                    @endforeach
                </tr>
                <tr>
                    <th scope="row">{{ Defs::MOTIONS[$motion]['label'] }}: render work p95 (ms)<span class="block text-body-sm font-normal text-neutral-600">{{ Defs::METRICS['work_p95_ms']['help'] }}</span>
                        @if ($lightest['too_close'])<x-ui.badge variant="outline" icon="equals" title="The min–max spreads overlap, so no asset gets the marker.">Too close to call</x-ui.badge>@endif
                    </th>
                    @foreach ($assets as $id => $entry)
                        @php($work = $entry['metrics'][$motion]['work_p95_ms'] ?? null)
                        <td data-numeric>{{ $ms($work['median'] ?? null, 2) }}<span class="block text-neutral-600">{{ $spread($work, fn ($v) => $ms($v, 2)) }}</span>
                            @if ($marker($lightest, $id))<x-ui.badge variant="outline" icon="trophy">Lightest to draw</x-ui.badge>@endif</td>
                    @endforeach
                </tr>
            @endforeach
        </x-ui.table>
    </section>

    {{-- Load phases of the cold load, median per phase (DESIGN.md §10: stacked bar, selection order). --}}
    <section class="space-y-3" aria-labelledby="phases-title">
        <x-ui.section-label id="phases-title" as="h2">Load phases (cold, median)</x-ui.section-label>
        <p class="flex flex-wrap gap-4 text-body-sm">
            @foreach (SideBySideResults::LOAD_PHASES as $label)
                <span><span class="phase-key" data-phase="{{ $loop->iteration }}" aria-hidden="true"></span> {{ $label }}</span>
            @endforeach
        </p>
        @php($longest = max(1, collect($assets)->map(fn ($e) => array_sum(array_filter($e['phases'])))->max()))
        <ul class="space-y-3">
            @foreach ($assets as $entry)
                @php($phaseTotal = array_sum(array_filter($entry['phases'])))
                <li class="grid gap-1 md:grid-cols-[minmax(0,12rem)_minmax(0,1fr)] md:items-center md:gap-4">
                    <span class="font-semibold">{{ $entry['asset']->name }}</span>
                    <span class="space-y-1">
                        <span class="phase-bar" style="width: {{ round($phaseTotal / $longest * 100, 2) }}%" aria-hidden="true">
                            @foreach (SideBySideResults::LOAD_PHASES as $key => $label)
                                @if (($entry['phases'][$key] ?? 0) > 0)<span data-phase="{{ $loop->iteration }}" style="width: {{ round($entry['phases'][$key] / max($phaseTotal, 0.001) * 100, 2) }}%"></span>@endif
                            @endforeach
                        </span>
                        <span class="num block text-body-sm text-neutral-600">{{ collect(SideBySideResults::LOAD_PHASES)->map(fn ($label, $key) => $label.' '.$ms($entry['phases'][$key] ?? null).' ms')->join(' · ') }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="space-y-3" aria-labelledby="tested-title">
        <x-ui.section-label id="tested-title" as="h2">Tested assets</x-ui.section-label>
        <x-assets.tested-assets :assets="collect($assets)->map(fn ($e) => [
            'id' => $e['asset']->id, 'name' => $e['asset']->name, 'tool' => $e['asset']->tool->name, 'object' => $e['asset']->object?->name,
            'author' => $e['asset']->author, 'format' => strtoupper($e['asset']->format), 'size' => Number::fileSize($e['asset']->file_size, maxPrecision: 1),
            'vertices' => $e['asset']->metrics?->vertices ?? 0, 'triangles' => $e['asset']->metrics?->triangles ?? 0,
            'hours' => $e['asset']->production_time_hours, 'uploaded_by' => $e['asset']->uploader?->name ?? '—',
        ])->values()->all()" />
    </section>

    {{-- Technical details: every §3.3 metric per motion scenario, median and min–max (BENCHMARK.md §4.3). --}}
    <details class="card space-y-4">
        <summary class="cursor-pointer font-semibold">Technical details</summary>
        @foreach ($results['motions'] as $motion)
            <section class="space-y-2">
                <h3 class="text-h3 font-semibold">{{ Defs::MOTIONS[$motion]['label'] }}</h3>
                <x-ui.table :caption="Defs::MOTIONS[$motion]['label'].' technical metrics'">
                    <x-slot:head>
                        <tr><th scope="col">Metric</th>@foreach ($assets as $entry)<th scope="col" data-numeric>{{ $entry['asset']->name }}</th>@endforeach</tr>
                    </x-slot:head>
                    @foreach (SideBySideResults::DETAIL_KEYS as $key)
                        <tr>
                            <th scope="row">{{ Defs::METRICS[$key]['label'] ?? str_replace('_', ' ', $key) }}@isset(Defs::METRICS[$key])<span class="block text-body-sm font-normal text-neutral-600">{{ Defs::METRICS[$key]['help'] }}</span>@endisset</th>
                            @foreach ($assets as $entry)
                                @php($value = $entry['metrics'][$motion][$key] ?? null)
                                <td data-numeric>{{ $value === null ? '—' : $ms($value['median'], 2) }}<span class="block text-neutral-600">{{ $spread($value, fn ($v) => $ms($v, 2)) }}</span></td>
                            @endforeach
                        </tr>
                    @endforeach
                </x-ui.table>
            </section>
        @endforeach
        <section class="space-y-2">
            <h3 class="text-h3 font-semibold">Run environment</h3>
            <dl class="grid gap-x-6 gap-y-1 text-body-sm sm:grid-cols-[max-content_minmax(0,1fr)]">
                <dt class="text-neutral-600">Device class</dt><dd>{{ $run->deviceClass?->name }}</dd>
                @foreach (['gpu_renderer' => 'GPU (browser-reported)', 'user_agent' => 'Browser', 'refresh_hz_estimate' => 'Refresh rate estimate (Hz)', 'goal_frame_ms' => 'Goal frame time (ms)', 'resolution' => 'Drawing buffer', 'three_revision' => 'Three.js revision', 'shuffle_seed' => 'Shuffle seed', 'orders' => 'Run order per repetition (asset ids)', 'physics_seed' => 'Physics seed', 'cooldown_ms' => 'Cool-down (ms)', 'animation' => 'Animation', 'memory_clean_after_dispose' => 'Memory back to baseline after dispose', 'dom_mutations_during_windows' => 'DOM changes during windows', 'max_canvases_during_windows' => 'Rendering panes during a window'] as $key => $label)
                    @php($value = $run->environment_json[$key] ?? null)
                    @continue($value === null)
                    <dt class="text-neutral-600">{{ $label }}</dt>
                    <dd class="num break-all">{{ is_array($value) ? ($key === 'resolution' ? ($value['width'] ?? '?').' × '.($value['height'] ?? '?') : json_encode($value)) : (is_bool($value) ? ($value ? 'Yes' : 'No') : $value) }}</dd>
                @endforeach
                @foreach ($assets as $entry)
                    @continue(! $entry['collider'])
                    <dt class="text-neutral-600">Physics collider · {{ $entry['asset']->name }}</dt>
                    <dd class="num">{{ $entry['collider']['points'] }} points, {{ $entry['collider']['faces'] }} faces, built in {{ $ms($entry['collider']['collider_build_ms']) }} ms</dd>
                @endforeach
            </dl>
        </section>
    </details>
