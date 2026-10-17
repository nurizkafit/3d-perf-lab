{{-- Public report (PRD.md §10.3, DESIGN.md §12). Data comes only from PublicReportPresenter (SITEMAP.md §7). --}}
@php
    use App\Support\BestMarkers;
    use App\Support\MetricDefinitions as Defs;
    use App\Support\SideBySideResults;

    $method = $methods[$report['method']];
    $results = $report['results'];
    $assets = collect($report['assets'])->keyBy('key');
    $target = $report['target'];
    $seconds = fn ($ms) => $ms === null ? '—' : number_format($ms / 1000, 2).' s';
    $ms = fn ($v, $d = 2) => $v === null ? '—' : number_format($v, $d);
    $fps = fn ($v) => $v === null ? '—' : number_format($v, 0).' fps';
    $copies = fn ($n) => number_format($n).' '.($n == 1 ? 'copy' : 'copies');
    $limit = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
    $checkValue = fn ($c) => match ($c['check_key']) {
        'load' => ($c['measured_value'] === null ? '—' : number_format($c['measured_value'], 2).' s').' / ≤ '.$limit($c['limit_value']).' s',
        'file_size' => ($c['measured_value'] === null ? '—' : number_format($c['measured_value'], 2).' MB').' / ≤ '.$limit($c['limit_value']).' MB',
        default => $fps($c['measured_value']).' at p95 / '.number_format($c['limit_value'], 0).' fps',
    };
@endphp
<x-layouts::public :title="$report['title']" :description="$report['question']">
    <article class="page-inline page-block mx-auto max-w-[var(--content-max-width)] space-y-10">
        <header class="space-y-4">
            <a href="{{ route('reports.index') }}" class="link inline-flex items-center gap-1 text-body-sm"><x-ui.icon name="arrow-left" /> All reports</a>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge variant="primary" :icon="$method['icon']">{{ $method['label'] }}</x-ui.badge>
                @if ($report['scenario'])<x-ui.badge variant="outline">{{ $report['scenario'] === 'per_tool' ? 'Per tool' : 'Per asset' }}</x-ui.badge>@endif
                @if ($report['device_class'])<x-ui.badge variant="outline" icon="devices">{{ $report['device_class'] }}</x-ui.badge>@endif
            </div>
            <h1 class="text-h1 font-bold">{{ $report['title'] }}</h1>
            {{-- The question is the page lead (DESIGN.md §12). --}}
            <p class="max-w-[var(--readable-width)] text-h3 text-neutral-950">{{ $report['question'] }}</p>
            <p class="text-body-sm text-neutral-600">Published <time datetime="{{ $report['published_at']->toIso8601String() }}">{{ $report['published_at']->display()->format('j F Y') }}</time> ·
                Target {{ $target['name'] }}: loads in ≤ {{ $limit($target['max_load_s']) }} s, Smooth at {{ $target['target_fps'] }} fps, file ≤ {{ $limit($target['max_file_size_mb']) }} MB</p>
        </header>

        @if (! $results)
            <x-ui.empty-state icon="chart-line" message="This report has no completed results." />
        @elseif ($report['method'] === 'side_by_side')
            <x-reports.report-section id="target" label="Target result" title="Does each asset meet the Target?">
                <x-ui.table caption="Target checks per asset, in selection order">
                    <x-slot:head><tr><th scope="col">Target check</th>@foreach ($results['assets'] as $r)<th scope="col">{{ $assets[$r['key']]['name'] }}<span class="block font-normal text-neutral-600">{{ $assets[$r['key']]['tool'] }} · {{ $assets[$r['key']]['author'] }}</span></th>@endforeach</tr></x-slot:head>
                    @php($rows = collect($results['assets'][0]['checks'] ?? [])->map(fn ($c) => [$c['check_key'], $c['motion']]))
                    @foreach ($rows as [$key, $motion])
                        <tr>
                            <th scope="row">{{ Defs::TARGET_CHECKS[$key]['label'] }}{{ $motion ? ' · '.Defs::MOTIONS[$motion]['label'] : '' }}<span class="block text-body-sm font-normal text-neutral-600">{{ Defs::TARGET_CHECKS[$key]['help'] }}</span></th>
                            @foreach ($results['assets'] as $r)
                                @php($c = collect($r['checks'])->first(fn ($c) => $c['check_key'] === $key && $c['motion'] === $motion))
                                <td>@if ($c)<x-ui.badge :status="str_replace('_', '-', $c['outcome'])" /> <span class="num block text-body-sm text-neutral-600">{{ $checkValue($c) }}</span>@if ($c['label'])<span class="block text-body-sm">{{ Defs::SMOOTHNESS[$c['label']]['label'] }}</span>@endif @else — @endif</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr><th scope="row">Meets all Target checks</th>@foreach ($results['assets'] as $r)<td class="font-semibold">{{ $r['meets_all'] ? 'Yes' : 'No' }}</td>@endforeach</tr>
                    <tr><th scope="row">Best markers</th>@foreach ($results['assets'] as $r)<td>@forelse ($r['markers'] as $m)<x-ui.badge variant="outline" icon="trophy">{{ $m }}</x-ui.badge> @empty — @endforelse</td>@endforeach</tr>
                </x-ui.table>
                <p class="text-body-sm text-neutral-600">Values are medians of the repetitions on {{ $report['device_class'] }}. Assets are shown in selection order, not sorted by result.</p>
            </x-reports.report-section>

            <x-reports.report-section id="reproduction" label="Interactive reproduction">
                <div class="space-y-4" data-linked-panes>
                    <script type="application/json" data-panes-config>@json($report['reproduction'])</script>
                    @if ($report['reproduction']['assets'])<x-performance.pane-toolbar name="public-motion" />@endif
                    <div class="pane-grid">
                        @foreach ($report['assets'] as $asset)
                            <figure class="pane" @if ($asset['approved']) data-pane="{{ $asset['key'] }}" @endif>
                                <figcaption class="pane-meta"><span class="font-semibold">{{ $asset['name'] }}</span><span class="text-neutral-600">{{ $asset['tool'] }} · {{ $asset['file_size'] }}</span></figcaption>
                                <div class="viewer-frame pane-view" @if ($asset['approved']) data-pane-frame data-state="loading" @endif>
                                    <span class="viewer-label">Interactive reproduction</span>
                                    @if ($asset['approved'])
                                        <div class="viewer-stage" data-pane-stage></div>
                                        <div class="viewer-message viewer-overlay" data-show-when="loading" role="status"><x-ui.icon name="circle-notch" size="md" class="spin" /><p>Loading the 3D model…</p></div>
                                        <div class="viewer-message viewer-overlay" data-show-when="error" role="alert"><x-ui.icon name="warning-circle" size="md" /><p data-pane-error>The model could not be shown. The recorded results are not affected.</p></div>
                                    @else
                                        <div class="viewer-message"><x-ui.icon name="cube-transparent" size="md" /><p>The author did not publish this model. Its recorded results are shown on this page.</p></div>
                                    @endif
                                </div>
                            </figure>
                        @endforeach
                    </div>
                </div>
                <p class="text-body-sm text-neutral-600">This scene rebuilds the test setup for viewing only. Your device is not measured; the numbers on this page are the recorded results.</p>
            </x-reports.report-section>

            <x-reports.report-section id="evidence" label="Evidence">
                <x-ui.table caption="Measured values per asset">
                    <x-slot:head><tr><th scope="col">Value</th>@foreach ($results['assets'] as $r)<th scope="col" data-numeric>{{ $assets[$r['key']]['name'] }}</th>@endforeach</tr></x-slot:head>
                    <tr><th scope="row">Time to first frame, cold<span class="block text-body-sm font-normal text-neutral-600">{{ Defs::METRICS['ttff_cold_ms']['help'] }}</span>@if ($results['markers']['fastest_load']['too_close'])<x-ui.badge variant="outline" icon="equals">Too close to call</x-ui.badge>@endif</th>
                        @foreach ($results['assets'] as $r)<td data-numeric>{{ $seconds($r['cold']['median'] ?? null) }}@if (($r['cold']['n'] ?? 0) > 1)<span class="block text-neutral-600">{{ $seconds($r['cold']['min']) }}–{{ $seconds($r['cold']['max']) }}</span>@endif</td>@endforeach</tr>
                    <tr><th scope="row">Time to first frame, warm</th>@foreach ($results['assets'] as $r)<td data-numeric>{{ $seconds($r['warm_median']) }}</td>@endforeach</tr>
                    @foreach ($report['motions'] as $motion)
                        <tr><th scope="row">{{ Defs::MOTIONS[$motion]['label'] }}: p95 frame interval (ms)<span class="block text-body-sm font-normal text-neutral-600">{{ Defs::METRICS['interval_p95_ms']['help'] }}</span></th>
                            @foreach ($results['assets'] as $r)<td data-numeric>{{ $ms($r['interval_p95'][$motion]['median'] ?? null) }}@if ($r['unstable'][$motion] ?? false) <x-ui.badge variant="warning" icon="warning">Unstable</x-ui.badge>@endif</td>@endforeach</tr>
                        <tr><th scope="row">{{ Defs::MOTIONS[$motion]['label'] }}: render work p95 (ms)<span class="block text-body-sm font-normal text-neutral-600">{{ Defs::METRICS['work_p95_ms']['help'] }}</span>@if ($results['markers']['lightest'][$motion]['too_close'] ?? false)<x-ui.badge variant="outline" icon="equals">Too close to call</x-ui.badge>@endif</th>
                            @foreach ($results['assets'] as $r)<td data-numeric>{{ $ms($r['work_p95'][$motion]['median'] ?? null) }}</td>@endforeach</tr>
                    @endforeach
                </x-ui.table>
                <p class="text-body-sm font-semibold">Load phases (cold, median)</p>
                <ul class="space-y-2">
                    @foreach ($results['assets'] as $r)
                        <li class="text-body-sm"><span class="font-semibold">{{ $assets[$r['key']]['name'] }}:</span> <span class="num">{{ collect(SideBySideResults::LOAD_PHASES)->map(fn ($label, $key) => $label.' '.$ms($r['phases'][$key] ?? null, 1).' ms')->join(' · ') }}</span></li>
                    @endforeach
                </ul>
            </x-reports.report-section>
        @else
            <x-reports.report-section id="target" label="Result" title="How many copies stay Smooth?">
                <p class="text-body-sm text-neutral-600">The largest number of copies that stayed Smooth at {{ $target['target_fps'] }} fps on {{ $report['device_class'] }}. Cap: {{ $copies($results['cap_copies']) }}. Groups in selection order.</p>
                @foreach ($report['motions'] as $motion)
                    @php($marker = $results['markers'][$motion])
                    <div class="card space-y-3" data-size="sm">
                        <div class="flex flex-wrap items-center gap-2"><h3 class="text-h3 font-semibold">{{ Defs::MOTIONS[$motion]['label'] }}</h3>@if ($marker['too_close'])<x-ui.badge variant="outline" icon="equals">Too close to call</x-ui.badge>@endif</div>
                        <ul class="space-y-3">
                            @foreach ($results['groups'] as $g)
                                @php($m = $g['motions'][$motion])
                                @php($median = $m['copies']['median'] ?? null)
                                <li class="grid gap-1 md:grid-cols-[minmax(0,12rem)_minmax(0,1fr)] md:items-center md:gap-4">
                                    <span class="font-semibold">{{ $g['label'] }}</span>
                                    <span class="space-y-1">
                                        <span class="phase-bar" style="width: {{ $median === null ? 0 : max(1, round($median / $results['largest'] * 100, 2)) }}%" aria-hidden="true"><span data-phase="{{ min(4, $loop->iteration) }}" style="width: 100%"></span></span>
                                        <span class="flex flex-wrap items-center gap-2 text-body-sm">
                                            <span class="num font-semibold">{{ $median === null ? 'Not measured' : 'Smooth up to '.($m['at_cap'] ? '≥ ' : '').$copies((int) round($median)) }}</span>
                                            @if ($m['time_limit'])<x-ui.badge variant="warning" icon="warning">Time limit</x-ui.badge>@endif
                                            @if (in_array($g['key'], $marker['winners'], true))<x-ui.badge variant="outline" icon="trophy">Most copies</x-ui.badge>@endif
                                        </span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </x-reports.report-section>

            <x-reports.report-section id="reproduction" label="Interactive reproduction">
                @if ($report['reproduction']['groups'])
                    <div class="card space-y-4" data-crowd-explorer>
                        <script type="application/json" data-crowd-config>@json($report['reproduction'])</script>
                        <x-performance.crowd-explorer :groups="collect($report['reproduction']['groups'])->pluck('label')->all()" :max-copies="$results['cap_copies']" name="public-crowd-motion" />
                    </div>
                @else
                    <div class="viewer-frame"><span class="viewer-label">Interactive reproduction</span><div class="viewer-message"><x-ui.icon name="cube-transparent" size="md" /><p>The author did not publish the models of this test. The recorded results are shown on this page.</p></div></div>
                @endif
                <p class="text-body-sm text-neutral-600">This scene rebuilds the crowd for viewing only; it starts at each group's Smooth count. Your device is not measured; the numbers on this page are the recorded results.</p>
            </x-reports.report-section>

            <x-reports.report-section id="evidence" label="Evidence: probe sequence">
                <p class="flex flex-wrap gap-4 text-body-sm text-neutral-600"><span>● S = Smooth</span><span>■ M = Mostly smooth</span><span>▲ C = Choppy</span><span>Repetition 1.</span></p>
                <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,24rem),1fr))]">
                    @foreach ($results['groups'] as $g)
                        @foreach ($report['motions'] as $motion)
                            @php($probes = $g['motions'][$motion]['probes'])
                            <figure class="card min-w-0 space-y-2 overflow-hidden" data-size="sm" data-probe-chart>
                                <script type="application/json" data-probe-data>@json(['probes' => $probes])</script>
                                <figcaption class="text-body-sm"><span class="font-semibold">{{ $g['label'] }}</span> · {{ Defs::MOTIONS[$motion]['label'] }} · {{ count($probes) }} probes</figcaption>
                                <div data-probe-canvas aria-hidden="true"></div>
                                <p class="sr-only">{{ collect($probes)->map(fn ($p) => $copies($p['copies']).' '.($p['label'] ? Defs::SMOOTHNESS[$p['label']]['label'] : 'Not measured'))->join(', ') }}</p>
                            </figure>
                        @endforeach
                    @endforeach
                </div>
            </x-reports.report-section>
        @endif

        <x-reports.report-section id="assets" label="Asset characteristics">
            <x-ui.table caption="Asset characteristics">
                <x-slot:head><tr><th scope="col">Asset</th><th scope="col">Tool</th><th scope="col">Object</th><th scope="col">Author</th><th scope="col">Format</th><th scope="col" data-numeric>File size</th><th scope="col" data-numeric>Vertices</th><th scope="col" data-numeric>Triangles</th></tr></x-slot:head>
                @foreach ($report['assets'] as $asset)
                    <tr>
                        <th scope="row" class="font-semibold">{{ $asset['name'] }}</th><td>{{ $asset['tool'] }}</td><td>{{ $asset['object'] ?? '—' }}</td><td>{{ $asset['author'] }}</td>
                        <td>{{ $asset['format'] }}</td><td data-numeric>{{ $asset['file_size'] }}</td>
                        <td data-numeric>{{ $asset['vertices'] === null ? '—' : number_format($asset['vertices']) }}</td><td data-numeric>{{ $asset['triangles'] === null ? '—' : number_format($asset['triangles']) }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-reports.report-section>

        <div class="grid gap-10 lg:grid-cols-2">
            <x-reports.report-section id="configuration" label="Method and configuration">
                <dl class="card grid grid-cols-[minmax(0,auto)_1fr] gap-x-4 gap-y-2 text-body-sm" data-size="sm">
                    @foreach ($report['configuration'] as $key => $value)<dt class="text-neutral-600">{{ $key }}</dt><dd>{{ $value }}</dd>@endforeach
                    <dt class="text-neutral-600">Target</dt><dd>{{ $target['name'] }} (≤ {{ $limit($target['max_load_s']) }} s, {{ $target['target_fps'] }} fps, ≤ {{ $limit($target['max_file_size_mb']) }} MB)</dd>
                </dl>
            </x-reports.report-section>
            <x-reports.report-section id="environment" label="Environment">
                <dl class="card grid grid-cols-[minmax(0,auto)_minmax(0,1fr)] gap-x-4 gap-y-2 text-body-sm" data-size="sm">
                    <dt class="text-neutral-600">Device class</dt><dd>{{ $report['device_class'] ?? '—' }}</dd>
                    @foreach ($report['environment'] as $key => $value)<dt class="text-neutral-600">{{ $key }}</dt><dd class="break-words">{{ $value }}</dd>@endforeach
                </dl>
            </x-reports.report-section>
        </div>

        <x-reports.report-section id="limitations" label="Limitations">
            <ul class="card list-disc space-y-1 ps-10 text-body-sm">
                @foreach ($report['limitations'] as $limitation)<li>{{ $limitation }}</li>@endforeach
            </ul>
        </x-reports.report-section>

        <x-reports.report-section id="conclusion" label="Author's conclusion">
            @if ($report['conclusion'])
                <p class="max-w-[var(--readable-width)] whitespace-pre-line">{{ $report['conclusion'] }}</p>
            @else
                <p class="text-neutral-600">The author did not add a conclusion.</p>
            @endif
        </x-reports.report-section>
    </article>
</x-layouts::public>
