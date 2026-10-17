{{-- Crowd results (BENCHMARK.md §4.6): "Smooth up to N copies" per group and motion scenario with Most copies,
     the probe sequences, the explore viewer, and Technical details per probe. Selection order throughout. --}}
@php
    use App\Support\MetricDefinitions as Defs;
    use Illuminate\Support\Number;

    $run = $results['run'];
    $groups = $results['groups'];
    $perTool = $test->scenario === 'per_tool';
    $copies = fn ($n) => number_format($n).' '.($n == 1 ? 'copy' : 'copies');
    $num = fn ($value, $digits = 1) => $value === null ? '—' : number_format($value, $digits);
    $labelText = fn ($label) => $label ? Defs::SMOOTHNESS[$label]['label'] : 'Not measured';
@endphp

<section class="space-y-3" aria-labelledby="crowd-title">
    <x-ui.section-label id="crowd-title" as="h2">Smooth up to</x-ui.section-label>
    <p class="text-body-sm text-neutral-600">
        The largest number of copies that stayed Smooth at {{ $test->target_json['target_fps'] }} fps (Target {{ $test->target_json['name'] }}).
        {{ $perTool ? 'One copy = one instance of each of the '.$results['objects'].' objects.' : 'One copy = one instance of the asset.' }}
        Cap: {{ $copies($results['cap_copies']) }}. Median of {{ $test->configuration_json['repetitions'] }} {{ Str::plural('repetition', $test->configuration_json['repetitions']) }}.
    </p>
    @foreach ($results['motions'] as $motion)
        @php($marker = $results['markers'][$motion])
        <div class="card space-y-3" data-size="sm">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-h3 font-semibold">{{ Defs::MOTIONS[$motion]['label'] }}</h3>
                <span class="text-body-sm text-neutral-600">{{ Defs::MOTIONS[$motion]['help'] }}</span>
                @if ($marker['too_close'])<x-ui.badge variant="outline" icon="equals" title="The results overlap (or are equal), so no group gets the Most copies marker.">Too close to call</x-ui.badge>@endif
            </div>
            <ul class="space-y-3">
                @foreach ($groups as $key => $group)
                    @php($m = $group['motions'][$motion])
                    @php($median = $m['copies']['median'] ?? null)
                    <li class="grid gap-1 md:grid-cols-[minmax(0,12rem)_minmax(0,1fr)] md:items-center md:gap-4">
                        <span class="font-semibold">{{ $group['label'] }}</span>
                        <span class="space-y-1">
                            <span class="phase-bar" style="width: {{ $median === null ? 0 : max(1, round($median / $results['largest'] * 100, 2)) }}%" aria-hidden="true"><span data-phase="{{ $loop->iteration > 4 ? 4 : $loop->iteration }}" style="width: 100%"></span></span>
                            <span class="flex flex-wrap items-center gap-2 text-body-sm">
                                <span class="num font-semibold">{{ $median === null ? 'Not measured' : 'Smooth up to '.($m['at_cap'] ? '≥ ' : '').$copies((int) round($median)) }}</span>
                                @if ($median !== null)<span class="num text-neutral-600">{{ number_format($median * $results['objects']) }} instances</span>@endif
                                @if (($m['copies']['n'] ?? 0) > 1)<span class="num text-neutral-600">{{ $m['copies']['min'] }}–{{ $m['copies']['max'] }}</span>@endif
                                @if ($m['at_cap'])<x-ui.badge variant="outline" icon="info" title="The cap was Smooth; the device may show more.">Reached the cap</x-ui.badge>@endif
                                @if ($m['time_limit'])<x-ui.badge variant="warning" icon="warning" title="The search stopped after 3 minutes and kept the last Smooth count.">Time limit</x-ui.badge>@endif
                                @if (in_array($key, $marker['winners'], true))<x-ui.badge variant="outline" icon="trophy">Most copies</x-ui.badge>@endif
                            </span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</section>

{{-- Explore viewer (decision D64): not measured. Default copy count = the group's Smooth count for the motion. --}}
<section class="card space-y-4" aria-labelledby="explore-title" data-crowd-explorer>
    <script type="application/json" data-crowd-config>@json($results['explorer'])</script>
    <div>
        <h2 id="explore-title" class="text-h3 font-semibold">Explore</h2>
        <p class="text-body-sm text-neutral-600">Not measured. Choose a group, a motion scenario, and a copy count; it starts at the Smooth count.</p>
    </div>
    <x-performance.crowd-explorer :groups="collect($groups)->pluck('label')->values()->all()" :max-copies="$results['cap_copies']" name="explore-motion" />
</section>

<section class="space-y-3" aria-labelledby="probes-title">
    <x-ui.section-label id="probes-title" as="h2">Probe sequence</x-ui.section-label>
    <p class="flex flex-wrap gap-4 text-body-sm text-neutral-600">
        <span>● S = Smooth</span><span>■ M = Mostly smooth</span><span>▲ C = Choppy</span><span>Repetition 1; all probes are listed in Technical details.</span>
    </p>
    <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,24rem),1fr))]">
        @foreach ($groups as $group)
            @foreach ($results['motions'] as $motion)
                @php($probes = $group['motions'][$motion]['probes'][1] ?? [])
                <figure class="card min-w-0 space-y-2 overflow-hidden" data-size="sm" data-probe-chart>
                    <script type="application/json" data-probe-data>@json(['probes' => collect($probes)->map(fn ($p) => ['copies' => $p['copies'], 'label' => $p['label']])->values()])</script>
                    <figcaption class="text-body-sm"><span class="font-semibold">{{ $group['label'] }}</span> · {{ Defs::MOTIONS[$motion]['label'] }} · {{ count($probes) }} probes</figcaption>
                    <div data-probe-canvas aria-hidden="true"></div>
                    <p class="sr-only">{{ collect($probes)->map(fn ($p) => $copies($p['copies']).' '.$labelText($p['label']))->join(', ') }}</p>
                </figure>
            @endforeach
        @endforeach
    </div>
</section>

<section class="space-y-3" aria-labelledby="tested-title">
    <x-ui.section-label id="tested-title" as="h2">Tested assets</x-ui.section-label>
    <x-ui.table caption="Tested assets by group">
        <x-slot:head><tr><th scope="col">{{ $perTool ? 'Tool set' : 'Group' }}</th><th scope="col">Asset</th><th scope="col">Tool</th><th scope="col">Object</th><th scope="col">Author</th><th scope="col">Format</th><th scope="col" data-numeric>File size</th><th scope="col" data-numeric>Triangles</th></tr></x-slot:head>
        @foreach ($groups as $group)
            @foreach ($group['assets'] as $asset)
                <tr>
                    <td>{{ $loop->first ? $group['label'] : '' }}</td>
                    <th scope="row"><a class="link" href="{{ route('assets.show', $asset) }}">{{ $asset->name }}</a></th>
                    <td>{{ $asset->tool->name }}</td><td>{{ $asset->object?->name ?? '—' }}</td><td>{{ $asset->author }}</td>
                    <td><x-ui.badge>{{ strtoupper($asset->format) }}</x-ui.badge></td>
                    <td data-numeric>{{ Number::fileSize($asset->file_size, maxPrecision: 1) }}</td>
                    <td data-numeric>{{ $asset->metrics ? number_format($asset->metrics->triangles) : '—' }}</td>
                </tr>
            @endforeach
        @endforeach
    </x-ui.table>
</section>

<details class="card space-y-4">
    <summary class="cursor-pointer font-semibold">Technical details</summary>
    @foreach ($groups as $group)
        @foreach ($results['motions'] as $motion)
            <section class="space-y-2">
                <h3 class="text-h3 font-semibold">{{ $group['label'] }} · {{ Defs::MOTIONS[$motion]['label'] }}</h3>
                <x-ui.table :caption="$group['label'].' '.Defs::MOTIONS[$motion]['label'].' probes'">
                    <x-slot:head><tr><th scope="col" data-numeric>Repetition</th><th scope="col" data-numeric>Probe</th><th scope="col" data-numeric>Copies</th><th scope="col" data-numeric>Instances</th><th scope="col">Label</th><th scope="col" data-numeric>p95 interval (ms)</th><th scope="col" data-numeric>Stutter (%)</th><th scope="col" data-numeric>Render work p95 (ms)</th><th scope="col" data-numeric>Physics p95 (ms)</th><th scope="col" data-numeric>Draw calls</th></tr></x-slot:head>
                    @foreach ($group['motions'][$motion]['probes'] as $repetition => $probes)
                        @foreach ($probes as $p)
                            <tr>
                                <td data-numeric>{{ $repetition }}</td><td data-numeric>{{ $loop->iteration }}</td>
                                <td data-numeric>{{ number_format($p['copies']) }}</td><td data-numeric>{{ number_format($p['instances']) }}</td>
                                <td>{{ $labelText($p['label']) }}</td>
                                <td data-numeric>{{ $num($p['interval_p95_ms'], 2) }}</td><td data-numeric>{{ $num($p['jank_pct'], 2) }}</td>
                                <td data-numeric>{{ $num($p['work_p95_ms'], 2) }}</td><td data-numeric>{{ $num($p['physics_p95_ms'], 2) }}</td><td data-numeric>{{ $num($p['draw_calls'], 0) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </x-ui.table>
                <p class="text-body-sm text-neutral-600">
                    @foreach ($group['motions'][$motion]['repetitions'] as $r)
                        Repetition {{ $r['repetition'] }}: Smooth up to {{ $copies($r['smooth_copies']) }}, {{ $r['probes'] }} probes{{ $r['cap_reached'] ? ', cap reached' : '' }}{{ $r['time_limit_reached'] ? ', time limit reached' : '' }}.
                    @endforeach
                </p>
            </section>
        @endforeach
    @endforeach
    <section class="space-y-2">
        <h3 class="text-h3 font-semibold">Run environment</h3>
        <dl class="grid gap-x-6 gap-y-1 text-body-sm sm:grid-cols-[max-content_minmax(0,1fr)]">
            <dt class="text-neutral-600">Device class</dt><dd>{{ $run->deviceClass?->name }}</dd>
            @foreach (['gpu_renderer' => 'GPU (browser-reported)', 'user_agent' => 'Browser', 'refresh_hz_estimate' => 'Refresh rate estimate (Hz)', 'goal_frame_ms' => 'Goal frame time (ms)', 'resolution' => 'Drawing buffer', 'instance_mode' => 'Instance mode', 'three_revision' => 'Three.js revision', 'shuffle_seed' => 'Shuffle seed', 'orders' => 'Run order per repetition (group ids)', 'physics_seed' => 'Physics seed', 'collider_build_ms' => 'Physics colliders built in (ms)', 'cooldown_ms' => 'Cool-down (ms)', 'dom_mutations_during_windows' => 'DOM changes during windows'] as $key => $label)
                @php($value = $run->environment_json[$key] ?? null)
                @continue($value === null)
                <dt class="text-neutral-600">{{ $label }}</dt>
                <dd class="num break-all">{{ is_array($value) ? ($key === 'resolution' ? ($value['width'] ?? '?').' × '.($value['height'] ?? '?') : json_encode($value)) : $value }}</dd>
            @endforeach
        </dl>
    </section>
</details>
