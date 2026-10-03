@php
    // Desktop — Standard seed values (BENCHMARK.md §6.2).
    $limits = ['max_file_size_mb' => 20, 'max_ttff_cold_ms' => 3000, 'max_ttff_warm_ms' => 1000, 'max_p95_frame_ms' => 16.7, 'max_jank_pct' => 1, 'max_draw_calls' => 500, 'max_texture_memory_mb' => 512];
@endphp
<x-layouts::app title="Comparison Test" description="N assets, N independent viewers, run one at a time.">
    <div class="space-y-6">
        <x-performance.step-card number="1" title="Assets" description="Select 1 to 8 registered assets. The run order is shuffled; results keep this order.">
            <ol class="space-y-2">
                @foreach ($assets as $i => $asset)
                    <li class="flex items-center gap-3 rounded-md border border-neutral-200 py-2 pe-3 ps-4">
                        <span class="num w-6 text-neutral-600">{{ $i + 1 }}</span>
                        <span class="thumb"><x-ui.icon name="cube" /></span>
                        <span class="min-w-0">
                            <span class="block font-semibold">{{ $asset['name'] }}</span>
                            <span class="block text-body-sm text-neutral-600">{{ $asset['tool'] }} · {{ $asset['author'] }} · {{ $asset['format'] }} · {{ $asset['size'] }} · {{ number_format($asset['triangles']) }} triangles</span>
                        </span>
                    </li>
                @endforeach
            </ol>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="secondary" icon="plus" disabled>Select assets</x-ui.button>
                <span class="text-body-sm text-neutral-600">The asset selector arrives in M4.2.</span>
            </div>
        </x-performance.step-card>

        <x-performance.step-card number="2" title="Settings" description="Defaults follow the benchmark protocol.">
            <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
                <x-ui.input name="repetitions" label="Repetitions" type="number" min="1" max="5" value="3" hint="1–5" />
                <x-ui.input name="warmup" label="Warm-up (s)" type="number" min="1" max="10" value="2" hint="1–10" />
                <x-ui.input name="measurement" label="Measurement (s)" type="number" min="5" max="30" value="10" hint="5–30" />
                <x-ui.select name="resolution" label="Resolution" :options="['1280x720' => '1280×720', '1920x1080' => '1920×1080', '720x1280' => '720×1280']" value="1280x720" />
                <x-ui.select name="camera" label="Camera" :options="['auto' => 'Auto-frame']" value="auto" />
                <x-ui.select name="lighting" label="Lighting" :options="['studio' => 'Studio']" value="studio" />
                <x-ui.select name="background" label="Background" :options="['neutral' => 'Neutral']" value="neutral" />
                <label class="flex min-h-[var(--control-height)] items-center gap-2 self-end text-body-sm font-semibold"><input type="checkbox" name="animation" class="input"> Play animation</label>
            </div>
        </x-performance.step-card>

        <x-performance.step-card number="3" title="Budget" description="Select a preset, then adjust values for this test. The test stores a copy. Only the items that apply to a Comparison Test are listed.">
            <div class="max-w-sm">
                <x-ui.select name="budget_preset" label="Budget preset" :options="['desktop' => 'Desktop — Standard', 'mid' => 'Mobile — Mid-range', 'entry' => 'Mobile — Entry', 'flagship' => 'Mobile — Flagship']" value="desktop" />
            </div>
            <x-ui.table caption="Budget items for this test">
                <x-slot:head><tr><th scope="col">Enabled</th><th scope="col">Budget item</th><th scope="col">Rule</th><th scope="col">Limit</th></tr></x-slot:head>
                @foreach ($budgetItems as $key => $item)
                    <tr>
                        <td><input type="checkbox" class="input" checked aria-label="Enable {{ $item['label'] }}"></td>
                        <th scope="row" class="cell-wrap">
                            <span class="block font-semibold">{{ $item['label'] }}</span>
                            <span class="block max-w-96 text-body-sm font-normal text-neutral-600">{{ $item['help'] }}</span>
                        </th>
                        <td>{{ $item['rule'] }}</td>
                        <td><span class="flex items-center gap-2"><input class="input w-28" type="number" value="{{ $limits[$key] }}" aria-label="Limit for {{ $item['label'] }}"><span class="text-body-sm text-neutral-600">{{ $item['unit'] }}</span></span></td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-performance.step-card>

        <x-performance.step-card number="4" title="Device" description="The run records the confirmed device class.">
            <div class="flex flex-wrap items-center gap-3">
                <x-performance.device-chip />
                <a href="{{ route('device.show') }}" class="link text-body-sm">Confirm or change</a>
            </div>
        </x-performance.step-card>

        <div class="card flex flex-wrap items-center gap-3" data-size="sm">
            <x-ui.button icon="play" disabled>Run test</x-ui.button>
            <p class="flex items-center gap-2 text-body-sm text-neutral-600"><x-ui.icon name="info" /> Confirm the device class to run. Running arrives in M4.2.</p>
            <a href="{{ route('lab.run', 1) }}" class="link ms-auto text-body-sm">Preview benchmark mode</a>
        </div>
    </div>
</x-layouts::app>
