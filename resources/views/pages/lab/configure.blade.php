@php
    $select = fn ($type) => $presets[$type] ?? ['options' => [], 'default' => null];
    $label = \App\Support\SampleReports::METHODS[$method]['label'];
    $intro = [
        'comparison' => 'N assets, N independent viewers, run one at a time. How does each asset load and render on its own?',
        'stress' => 'N instances in one scene, at fixed instance steps. How does performance change as the count increases?',
        'headroom' => 'Instances increase automatically until the frame budget fails. How many instances can this device hold inside the budget?',
    ][$method];
    $assetHelp = [
        'comparison' => 'Select 1 to 8 registered assets. The run order is shuffled; results keep this order.',
        'stress' => 'Repeated: one asset, cloned at every step. Mixed: 2 to 8 assets from one authoring tool, placed round-robin in selection order.',
        'headroom' => 'Repeated: each selected asset (1 to 8) gets its own search. Mixed: 2 to 8 assets from one authoring tool share one search, placed round-robin.',
    ][$method];
@endphp
<x-layouts::app :title="$label" :description="$intro" :sample="false">
    <script type="application/json" id="lab-config">@json($config)</script>

    <form x-data="labConfig" x-ref="form" x-on:assets-selected.window="onSelected($event)" x-on:submit.prevent="run()" class="space-y-6">
        <div class="max-w-md">
            <x-ui.input name="name" label="Test name" :value="$label.' — '.now()->format('Y-m-d H:i')" required maxlength="150" />
        </div>

        @if (in_array($method, ['stress', 'headroom']))
            <fieldset class="card space-y-3" data-size="sm">
                <legend class="field-label">Scenario</legend>
                <div class="flex flex-wrap gap-2" role="radiogroup">
                    <label class="nav-link cursor-pointer has-[:checked]:bg-primary-tint has-[:checked]:border-neutral-800 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-primary"><input type="radio" class="sr-only" name="scenario" value="repeated" x-model="scenario"> Repeated asset</label>
                    <label class="nav-link cursor-pointer has-[:checked]:bg-primary-tint has-[:checked]:border-neutral-800 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-primary"><input type="radio" class="sr-only" name="scenario" value="mixed" x-model="scenario"> Mixed assets (one tool)</label>
                </div>
            </fieldset>
        @endif

        <x-performance.step-card number="1" title="Assets" :description="$assetHelp">
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
                            <span class="block text-body-sm text-neutral-600" x-text="[asset.tool, asset.author, asset.format, asset.size, asset.triangles === null ? null : asset.triangles.toLocaleString() + ' triangles'].filter(Boolean).join(' · ')"></span>
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

        <x-performance.step-card number="2" title="Settings" description="Defaults follow the benchmark protocol (BENCHMARK.md §4.2).">
            <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
                <x-ui.input name="configuration[repetitions]" id="repetitions" label="Repetitions" type="number" min="1" max="5" value="3" hint="1–5" required />
                @if ($method === 'headroom')
                    <x-ui.input name="configuration[cap]" id="cap" label="Cap (instances)" type="number" min="1" max="4096" step="1" value="1024" hint="Default 1,024, at most 4,096. Reaching the cap records ≥ cap." required />
                    <p class="text-body-sm text-neutral-600 sm:col-span-2">Each probe: 1 s warm-up and a 4 s window. A search stops after 3 minutes and keeps the last passing count. A probe passes when its p95 frame interval and jank meet the budget.</p>
                @else
                    <x-ui.input name="configuration[warmup_s]" id="warmup" label="Warm-up (s)" type="number" min="1" max="10" step="1" value="2" hint="1–10" required />
                    <x-ui.input name="configuration[measurement_s]" id="measurement" label="Measurement (s)" type="number" min="5" max="30" step="1" value="10" hint="5–30" required />
                @endif
                @if ($method === 'stress')
                    <div class="sm:col-span-2">
                        <label for="steps-preset" class="field-label">Instance steps preset</label>
                        <select id="steps-preset" class="select" x-on:change="applyStepPreset($event.target.value)">
                            @foreach ($stepPresets as $preset)<option value="{{ $preset['id'] }}">{{ $preset['name'] }}</option>@endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="steps" class="field-label">Instance counts</label>
                        <input id="steps" class="input" x-model="steps" inputmode="numeric" :aria-invalid="!!stepsError" aria-describedby="steps-hint">
                        <p id="steps-hint" class="field-hint" x-show="!stepsError">Ascending, separated by commas, each 1–500. The camera frames the largest step and stays fixed.</p>
                        <p class="field-error" x-show="stepsError" x-text="stepsError" x-cloak></p>
                    </div>
                @endif
                @foreach (['resolution' => 'Resolution', 'camera' => 'Camera', 'lighting' => 'Lighting', 'background' => 'Background'] as $type => $label)
                    <x-ui.select :name="'configuration['.$type.'_id]'" :id="$type" :label="$label" :options="$select($type)['options']" :value="$select($type)['default']" />
                @endforeach
                @if ($method === 'comparison')
                    <label class="flex min-h-[var(--control-height)] items-center gap-2 self-end text-body-sm font-semibold">
                        <input type="checkbox" name="configuration[animation]" value="1" class="input"> Play animation (first clip)
                    </label>
                @endif
            </div>
        </x-performance.step-card>

        <x-performance.step-card number="3" title="Budget" :description="'Select a preset, then adjust values for this test. The test stores a copy. Only the items that apply to a '.$label.' are listed.'.($method === 'stress' ? ' Frame items are evaluated at each step.' : '')">
            <x-performance.budget-step :budget="$budget" />
        </x-performance.step-card>

        <x-performance.step-card number="4" title="Device" description="The run records the confirmed device class.">
            <div class="flex flex-wrap items-center gap-3">
                <x-performance.device-chip />
                <a href="{{ route('device.show', ['return_to' => '/lab/'.$method]) }}" class="link text-body-sm">Confirm or change</a>
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

    <x-assets.asset-selector :tools="$tools" :max="8" />
</x-layouts::app>
