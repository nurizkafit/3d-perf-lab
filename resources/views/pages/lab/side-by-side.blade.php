@php
    $label = \App\Support\MetricDefinitions::METHODS['side_by_side']['label'];
@endphp
<x-layouts::app :title="$label" description="2–4 assets next to each other. Look at them together; measure them one at a time. Which version loads faster and runs smoother?" :sample="false">
    <script type="application/json" id="lab-config">@json($config + ['defaults' => $defaults])</script>

    <form x-data="sideBySide" x-on:assets-selected.window="onSelected($event)" x-on:target-change="onTarget($event)" x-on:submit.prevent="run()" class="space-y-6">
        <div class="max-w-md">
            <x-ui.input name="name" label="Test name" :value="$label.' — '.now()->display()->format('Y-m-d H:i')" required maxlength="150" x-model="name" />
        </div>

        <x-performance.step-card number="1" title="Assets" description="Select 2 to 4 registered assets. The panes and the results keep this order; the run order is shuffled.">
            <ol class="space-y-2" x-show="assets.length" x-cloak>
                <template x-for="(asset, index) in assets" :key="asset.id">
                    <li class="flex flex-wrap items-center gap-3 rounded-md border border-neutral-200 py-2 pe-2 ps-4">
                        <span class="num w-6 text-neutral-600" x-text="index + 1"></span>
                        <span class="thumb overflow-hidden">
                            <img x-show="asset.thumbnail_url" :src="asset.thumbnail_url" alt="" class="size-full object-cover">
                            <span x-show="!asset.thumbnail_url"><x-ui.icon name="cube" /></span>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold" x-text="asset.name"></span>
                            <span class="block text-body-sm text-neutral-600" x-text="[asset.tool, asset.object, asset.author, asset.format, asset.size, asset.triangles === null ? null : asset.triangles.toLocaleString() + ' triangles'].filter(Boolean).join(' · ')"></span>
                        </span>
                        <span class="flex gap-1">
                            <x-ui.button variant="ghost" size="icon" x-on:click="move(index, -1)" x-bind:disabled="index === 0" x-bind:aria-label="'Move ' + asset.name + ' up'" aria-label="Move up"><x-ui.icon name="arrow-up" /></x-ui.button>
                            <x-ui.button variant="ghost" size="icon" x-on:click="move(index, 1)" x-bind:disabled="index === assets.length - 1" x-bind:aria-label="'Move ' + asset.name + ' down'" aria-label="Move down"><x-ui.icon name="arrow-down" /></x-ui.button>
                            <x-ui.button variant="ghost" size="icon" x-on:click="remove(index)" x-bind:aria-label="'Remove ' + asset.name" aria-label="Remove"><x-ui.icon name="x" /></x-ui.button>
                        </span>
                    </li>
                </template>
            </ol>
            <p class="text-body-sm text-neutral-600" x-show="!assets.length">No assets selected yet.</p>
            <x-ui.button variant="secondary" icon="plus" x-on:click="openSelector()"><span x-text="assets.length ? 'Change assets' : 'Select assets'">Select assets</span></x-ui.button>
        </x-performance.step-card>

        {{-- Look phase (BENCHMARK.md §4.3): linked panes, one shared camera. Not measured. --}}
        <section class="card space-y-4" aria-labelledby="look-title" x-show="assets.length" x-cloak x-ref="look">
            <div>
                <h2 id="look-title" class="text-h3 font-semibold">Look</h2>
                <p class="text-body-sm text-neutral-600">Not measured. Orbit, pan, or zoom in one pane to move all panes. Assets keep their real size.</p>
            </div>
            <x-performance.pane-toolbar />
            <div class="pane-grid">
                <template x-for="asset in assets.slice(0, config.maxAssets)" :key="asset.id">
                    <figure class="pane" :data-pane="asset.id">
                        <figcaption class="pane-meta">
                            <span class="font-semibold" x-text="asset.name"></span>
                            <span class="text-neutral-600" x-text="[asset.tool, asset.object, asset.size, asset.triangles === null ? null : asset.triangles.toLocaleString() + ' triangles'].filter(Boolean).join(' · ')"></span>
                        </figcaption>
                        <div class="viewer-frame pane-view" data-pane-frame data-state="loading">
                            <div class="viewer-stage" data-pane-stage></div>
                            <div class="viewer-message viewer-overlay" data-show-when="loading" role="status">
                                <x-ui.icon name="circle-notch" size="md" class="spin" />
                                <p>Loading the 3D model…</p>
                            </div>
                            <div class="viewer-message viewer-overlay" data-show-when="error" role="alert">
                                <x-ui.icon name="warning-circle" size="md" />
                                <p data-pane-error>The model could not be shown.</p>
                            </div>
                        </div>
                    </figure>
                </template>
            </div>
        </section>

        <x-performance.step-card number="2" title="Settings" description="Defaults follow the benchmark protocol (BENCHMARK.md §4.1–§4.3).">
            <fieldset class="space-y-2">
                <legend class="field-label">Motion scenarios</legend>
                <p class="field-hint">Measured in this order. At least one stays selected.</p>
                <div class="flex flex-col gap-2">
                    @foreach (\App\Support\MetricDefinitions::MOTIONS as $key => $motion)
                        <label class="flex items-start gap-2 text-body-sm">
                            <input type="checkbox" class="input mt-1" value="{{ $key }}" :checked="motions.includes('{{ $key }}')" x-on:change="toggleMotion('{{ $key }}', $event.target.checked); $event.target.checked = motions.includes('{{ $key }}')">
                            <span><span class="font-semibold">{{ $motion['label'] }}</span> <span class="text-neutral-600">— {{ $motion['help'] }}</span></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
                <x-ui.input name="repetitions" id="repetitions" label="Repetitions" type="number" min="1" max="5" hint="1–5" required x-model.number="repetitions" />
                <x-ui.input name="warmup" id="warmup" label="Warm-up (s)" type="number" min="1" max="10" step="1" hint="1–10. Physics: at least 2 s." required x-model.number="warmup" />
                <x-ui.input name="measurement" id="measurement" label="Measurement (s)" type="number" min="5" max="30" step="1" hint="5–30, per motion scenario" required x-model.number="measurement" />
                @foreach (['resolution' => 'Resolution', 'camera' => 'Camera', 'lighting' => 'Lighting', 'background' => 'Background'] as $type => $typeLabel)
                    <div>
                        <label for="preset-{{ $type }}" class="field-label">{{ $typeLabel }}</label>
                        <select id="preset-{{ $type }}" class="select" x-model="presets.{{ $type }}">
                            @foreach ($presets[$type] ?? [] as $id => $name)
                                <option value="{{ $id }}" @selected($id === $defaults[$type])>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <label class="flex min-h-[var(--control-height)] items-center gap-2 self-end text-body-sm font-semibold">
                    <input type="checkbox" class="input" x-model="animation"> Play animation (first clip)
                </label>
            </div>
            <p class="flex items-center gap-2 text-body-sm" role="status"><x-ui.icon name="timer" /> Estimated run time: <span class="num font-semibold" x-text="estimate"></span></p>
        </x-performance.step-card>

        <x-performance.step-card number="3" title="Target" description="Select a Target, then adjust its values for this test. The test stores a copy.">
            <x-performance.target-step :target="$target" />
        </x-performance.step-card>

        <x-performance.step-card number="4" title="Device" description="The run records the confirmed device class.">
            <div class="flex flex-wrap items-center gap-3">
                <x-performance.device-chip />
                <a href="{{ route('device.show', ['return_to' => '/lab/side-by-side']) }}" class="link text-body-sm">Confirm or change</a>
            </div>
        </x-performance.step-card>

        <div class="card flex flex-wrap items-center gap-3" data-size="sm">
            <x-ui.button type="submit" icon="play" x-bind:disabled="!!blocker || running" x-bind:aria-busy="running">
                <span x-text="running ? 'Starting…' : 'Run test'">Run test</span>
            </x-ui.button>
            <p class="flex items-center gap-2 text-body-sm text-neutral-600" x-show="blocker" role="status"><x-ui.icon name="info" /><span x-text="blocker"></span></p>
            <p class="field-error m-0" x-show="error" x-cloak role="alert"><x-ui.icon name="warning-circle" /><span x-text="error"></span></p>
        </div>
    </form>

    <x-assets.asset-selector :tools="$tools" :objects="$objects" :max="4" />
</x-layouts::app>
