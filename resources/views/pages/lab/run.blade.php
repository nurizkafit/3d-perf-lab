@php
    $method = \App\Support\MetricDefinitions::METHODS[$test->method]['label'];
    $c = $test->configuration_json;
    $motionLabels = collect($c['motions'])->map(fn ($m) => \App\Support\MetricDefinitions::MOTIONS[$m]['label'])->join(', ');
@endphp
<x-layouts::benchmark title="Benchmark — {{ $test->name }}">
    <script type="application/json" id="run-config">@json($config)</script>
    <main class="flex min-h-svh flex-col gap-3 p-[var(--page-padding-inline)]" data-benchmark-run>
        {{-- Progress strip: updated between measurement windows only (BENCHMARK.md §4.1 step 6). --}}
        <div class="benchmark-strip" role="status" aria-live="off">
            <span class="font-semibold">{{ $method }}</span>
            <span>{{ $test->name }}</span>
            <span data-progress-text>Ready</span>
            <span class="ms-auto num" data-progress-count></span>
            {{-- Step-wise bar: set between measurement windows only, never animated (DESIGN.md §9.3). --}}
            <div class="benchmark-progress" role="progressbar" aria-label="Benchmark progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-progress-bar>
                <span style="transform: scaleX(0)"></span>
            </div>
        </div>

        @if ($test->method === 'crowd')
            {{-- One canvas shows the crowd during the whole search (BENCHMARK.md §4.6). --}}
            <div class="grid flex-1 place-items-center">
                <div class="benchmark-stage" data-benchmark-stage></div>
            </div>
        @else
            {{-- The panes keep their grid positions. Before an asset is measured its pane shows the stored thumbnail;
                 after, the last measured frame. Only the measured pane renders (DESIGN.md §9.3). --}}
            <div class="pane-grid benchmark-panes">
                @foreach ($test->assets as $asset)
                    <figure class="pane" data-pane="{{ $asset->id }}">
                        <figcaption class="pane-meta">
                            <span class="font-semibold">{{ $asset->name }}</span>
                            <span>{{ collect([$asset->tool->name, $asset->object?->name, \Illuminate\Support\Number::fileSize($asset->file_size, maxPrecision: 1)])->filter()->join(' · ') }}</span>
                            <x-ui.badge variant="primary" class="pane-measuring">Measuring</x-ui.badge>
                        </figcaption>
                        <div class="viewer-frame pane-view">
                            <div class="viewer-stage grid place-items-center" data-pane-stage>
                                @if ($asset->thumbnail_path)
                                    <img src="{{ route('assets.thumbnail', $asset) }}" alt="{{ $asset->name }}: thumbnail">
                                @else
                                    <p class="text-body-sm">Waiting to be measured</p>
                                @endif
                            </div>
                        </div>
                    </figure>
                @endforeach
            </div>
        @endif

        <div class="grid place-items-center">
            <section class="benchmark-panel space-y-4" data-preflight aria-labelledby="preflight-title">
                <h1 id="preflight-title" class="text-h2 font-bold">Before you start</h1>
                <ul class="list-disc space-y-1 ps-5">
                    <li>Close other tabs and apps.</li>
                    <li>Keep the screen on and this tab visible. Switching tabs or windows makes the run invalid.</li>
                    <li>On a phone or tablet, connect the charger.</li>
                    <li>Do not touch the page until the run ends.</li>
                </ul>
                @if ($test->method === 'crowd')
                    <p class="text-body-sm">
                        {{ count($groups) }} {{ $test->scenario === 'per_tool' ? Str::plural('tool set', count($groups)).' ('.count($c['object_ids']).' objects each)' : Str::plural('asset', count($groups)) }}
                        × {{ $c['repetitions'] }} {{ Str::plural('repetition', $c['repetitions']) }} · {{ $motionLabels }} · cap {{ number_format($c['cap']) }} instances ·
                        each probe {{ $c['probe_warmup_s'] }} s warm-up (Physics {{ $c['physics_probe_warmup_s'] }} s) and a {{ $c['probe_window_s'] }} s window; each search stops after {{ intdiv($c['time_limit_s'], 60) }} min · Device class: {{ $run->deviceClass->name }}.
                    </p>
                    <p class="text-body-sm">Estimated run time: <span class="num font-semibold">about {{ max(1, (int) ceil($typicalSeconds / 60)) }} min, at most {{ max(1, (int) ceil($estimateSeconds / 60)) }} min</span>.</p>
                @else
                    <p class="text-body-sm">
                        {{ $test->assets->count() }} assets × {{ $c['repetitions'] }} {{ Str::plural('repetition', $c['repetitions']) }} · {{ $motionLabels }} ·
                        {{ $c['warmup_s'] }} s warm-up and {{ $c['measurement_s'] }} s measurement per motion scenario · Device class: {{ $run->deviceClass->name }}.
                    </p>
                    <p class="text-body-sm">Estimated run time: <span class="num font-semibold">about {{ max(1, (int) ceil($estimateSeconds / 60)) }} min</span>.</p>
                @endif
                <x-ui.button icon="play" data-start>Start</x-ui.button>
            </section>

            <section class="benchmark-panel space-y-4" data-outcome hidden role="alert">
                <h1 class="text-h2 font-bold" data-outcome-title></h1>
                <p data-outcome-message></p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button icon="arrows-clockwise" data-again hidden>Run again</x-ui.button>
                    <x-ui.button :href="route($test->method === 'crowd' ? 'lab.crowd' : 'lab.side-by-side')" variant="secondary">Back to configuration</x-ui.button>
                </div>
            </section>
        </div>

        <div class="flex justify-center">
            <x-ui.button variant="secondary" icon="stop" data-cancel>Cancel run</x-ui.button>
        </div>
    </main>
</x-layouts::benchmark>
