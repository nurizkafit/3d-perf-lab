@php($method = \App\Support\SampleReports::METHODS[$test->method]['label'])
<x-layouts::benchmark title="Benchmark — {{ $test->name }}">
    <script type="application/json" id="run-config">@json($config)</script>
    <main class="flex min-h-svh flex-col gap-3 p-[var(--page-padding-inline)]" data-benchmark-run>
        {{-- Progress strip: updated between measurement windows only (BENCHMARK.md §4.1 step 6). --}}
        <div class="benchmark-strip" role="status" aria-live="off">
            <span class="font-semibold">{{ $method }}</span>
            <span>{{ $test->name }}</span>
            <span data-progress-text>Ready</span>
            <span class="ms-auto num" data-progress-count></span>
            {{-- Step-wise bar: set between measurement windows only, never animated (BENCHMARK.md §4.1 step 6). --}}
            <div class="benchmark-progress" role="progressbar" aria-label="Benchmark progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-progress-bar>
                <span style="transform: scaleX(0)"></span>
            </div>
        </div>

        <div class="grid flex-1 place-items-center">
            <div class="benchmark-stage" data-benchmark-stage></div>

            <section class="benchmark-panel space-y-4" data-preflight aria-labelledby="preflight-title">
                <h1 id="preflight-title" class="text-h2 font-bold">Before you start</h1>
                <ul class="list-disc space-y-1 ps-5">
                    <li>Close other tabs and apps.</li>
                    <li>Keep the screen on and this tab visible. Switching tabs or windows makes the run invalid.</li>
                    <li>On a phone or tablet, connect the charger.</li>
                    <li>Do not touch the page until the run ends.</li>
                </ul>
                <p class="text-body-sm">{{ $test->assets->count() }} {{ Str::plural('asset', $test->assets->count()) }} × {{ $test->configuration_json['repetitions'] }} {{ Str::plural('repetition', $test->configuration_json['repetitions']) }},
                    {{ $test->configuration_json['warmup_s'] }} s warm-up and {{ $test->configuration_json['measurement_s'] }} s measurement each. Device class: {{ $run->deviceClass->name }}.</p>
                <x-ui.button icon="play" data-start>Start</x-ui.button>
            </section>

            <section class="benchmark-panel space-y-4" data-outcome hidden role="alert">
                <h1 class="text-h2 font-bold" data-outcome-title></h1>
                <p data-outcome-message></p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button icon="arrows-clockwise" data-again hidden>Run again</x-ui.button>
                    <x-ui.button :href="route('lab.'.$test->method)" variant="secondary">Back to configuration</x-ui.button>
                </div>
            </section>
        </div>

        <div class="flex justify-center">
            <x-ui.button variant="secondary" icon="stop" data-cancel>Cancel run</x-ui.button>
        </div>
    </main>
</x-layouts::benchmark>
