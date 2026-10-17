@php
    $label = \App\Support\MetricDefinitions::METHODS['crowd']['label'];
@endphp
<x-layouts::app :title="$label" description="Copies fill one scene, step by step, until it stops being Smooth. You watch the crowd grow. How many copies can this device show smoothly?" :sample="false">
    <script type="application/json" id="lab-config">@json($config + ['defaults' => $defaults])</script>

    <form x-data="crowdTest" x-on:assets-selected.window="onSelected($event)" x-on:target-change="onTarget($event)" x-on:submit.prevent="run()" class="space-y-6">
        <div class="max-w-md">
            <x-ui.input name="name" label="Test name" :value="$label.' — '.now()->display()->format('Y-m-d H:i')" required maxlength="150" x-model="name" />
        </div>

        <x-performance.step-card number="1" title="Assets" description="Per asset: each asset gets its own crowd. Per tool: each tool's set of objects gets its own crowd, with the same objects in every tool.">
            <fieldset class="flex flex-wrap gap-2">
                <legend class="sr-only">Scenario</legend>
                @foreach (['per_asset' => 'Per asset', 'per_tool' => 'Per tool'] as $value => $text)
                    <label class="nav-link cursor-pointer has-[:checked]:bg-primary-tint has-[:checked]:border-neutral-800 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-primary">
                        <input type="radio" class="sr-only" name="scenario" value="{{ $value }}" :checked="scenario === '{{ $value }}'" x-on:change="setScenario('{{ $value }}')"> {{ $text }}
                    </label>
                @endforeach
            </fieldset>

            {{-- Per asset: 1–8 assets from the selector. --}}
            <div class="space-y-3" x-show="scenario === 'per_asset'">
                <ol class="space-y-2" x-show="assets.length" x-cloak>
                    <template x-for="(asset, index) in assets" :key="asset.id">
                        <li class="flex flex-wrap items-center gap-3 rounded-md border border-neutral-200 py-2 pe-2 ps-4">
                            <span class="num w-6 text-neutral-600" x-text="index + 1"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold" x-text="asset.name"></span>
                                <span class="block text-body-sm text-neutral-600" x-text="[asset.tool, asset.object, asset.format, asset.size, asset.triangles === null ? null : asset.triangles.toLocaleString() + ' triangles'].filter(Boolean).join(' · ')"></span>
                            </span>
                            <span class="flex gap-1">
                                <x-ui.button variant="ghost" size="icon" x-on:click="move(index, -1)" x-bind:disabled="index === 0" x-bind:aria-label="'Move ' + asset.name + ' up'" aria-label="Move up"><x-ui.icon name="arrow-up" /></x-ui.button>
                                <x-ui.button variant="ghost" size="icon" x-on:click="move(index, 1)" x-bind:disabled="index === assets.length - 1" x-bind:aria-label="'Move ' + asset.name + ' down'" aria-label="Move down"><x-ui.icon name="arrow-down" /></x-ui.button>
                                <x-ui.button variant="ghost" size="icon" x-on:click="remove(index)" x-bind:aria-label="'Remove ' + asset.name" aria-label="Remove"><x-ui.icon name="x" /></x-ui.button>
                            </span>
                        </li>
                    </template>
                </ol>
                <p class="text-body-sm text-neutral-600" x-show="!assets.length">No assets selected yet. Select 1 to 8.</p>
                <x-ui.button variant="secondary" icon="plus" x-on:click="openSelector()"><span x-text="assets.length ? 'Change assets' : 'Select assets'">Select assets</span></x-ui.button>
            </div>

            {{-- Per tool: tools, then the objects that exist in every selected tool (PRD.md §8.3). --}}
            <div class="space-y-4" x-show="scenario === 'per_tool'" x-cloak>
                <fieldset class="space-y-2">
                    <legend class="field-label">Authoring tools (two or more)</legend>
                    <div class="flex flex-wrap gap-4">
                        @foreach ($tools as $id => $toolName)
                            <label class="flex items-center gap-2 text-body-sm">
                                <input type="checkbox" class="input" value="{{ $id }}" data-tool-option data-tool-name="{{ $toolName }}" :checked="toolIds.includes({{ $id }})" x-on:change="toggleTool({{ $id }}, $event.target.checked)"> {{ $toolName }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <p class="text-body-sm text-neutral-600" x-show="toolIds.length < 2">Select at least two tools to see the objects they share.</p>
                <p class="text-body-sm" role="status" x-show="commonLoading">Finding the objects that every selected tool has…</p>
                <p class="field-error" role="alert" x-show="commonError" x-text="commonError"></p>

                <div x-show="toolIds.length >= 2 && !commonLoading" class="space-y-3">
                    <p class="text-body-sm text-neutral-600" x-show="common.objects.length === 0">No object exists in every selected tool.</p>
                    <div x-show="common.objects.length" class="table-container" role="region" aria-label="Objects in every selected tool" tabindex="0">
                        <table class="table">
                            <caption class="sr-only">Objects in every selected tool, and the asset used per tool</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Include</th>
                                    <th scope="col">Object</th>
                                    <template x-for="toolId in toolIds" :key="toolId"><th scope="col" x-text="toolNames[toolId]"></th></template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="object in common.objects" :key="object.id">
                                    <tr>
                                        <td><input type="checkbox" class="input" x-model="choices[object.id].included" x-on:change="mountExplorer()" :aria-label="'Include ' + object.name"></td>
                                        <th scope="row" x-text="object.name"></th>
                                        <template x-for="toolId in toolIds" :key="toolId">
                                            <td>
                                                <template x-if="(object.assets[toolId] ?? []).length > 1">
                                                    <select class="select" x-model.number="choices[object.id].assets[toolId]" x-on:change="mountExplorer()" :aria-label="object.name + ' in ' + toolNames[toolId]">
                                                        <template x-for="asset in object.assets[toolId]" :key="asset.id"><option :value="asset.id" x-text="asset.name + ' · ' + asset.registered"></option></template>
                                                    </select>
                                                </template>
                                                <span class="text-body-sm" x-show="(object.assets[toolId] ?? []).length === 1" x-text="candidate(object, toolId)?.name + ' · ' + candidate(object, toolId)?.size"></span>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-body-sm" x-show="common.objects.length"><span class="num" x-text="includedObjects.length"></span> of <span class="num" x-text="common.objects.length"></span> objects included (at most {{ $config['maxObjects'] }}). Instances are placed round-robin in this order.</p>
                    <div x-show="common.missing.length" class="space-y-1">
                        <p class="field-label">Not compared: missing in a selected tool</p>
                        <ul class="list-disc space-y-1 ps-5 text-body-sm">
                            <template x-for="object in common.missing" :key="object.id"><li><span class="font-semibold" x-text="object.name"></span> — missing in <span x-text="object.missing_tools.join(', ')"></span></li></template>
                        </ul>
                    </div>
                </div>
            </div>
        </x-performance.step-card>

        {{-- Look phase (decision D64): the explore viewer. Not measured. --}}
        <section class="card space-y-4" aria-labelledby="look-title" x-show="groups.length" x-cloak x-ref="look">
            <div>
                <h2 id="look-title" class="text-h3 font-semibold">Look</h2>
                <p class="text-body-sm text-neutral-600">Not measured. Choose an asset or tool set, a motion scenario, and a number of copies.</p>
            </div>
            <x-performance.crowd-explorer alpine name="look-motion" />
        </section>

        <x-performance.step-card number="2" title="Settings" description="Each probe: 1 s warm-up (Physics 2 s), then a 4 s window. Each search stops after 3 minutes (BENCHMARK.md §4.6).">
            <fieldset class="space-y-2">
                <legend class="field-label">Motion scenarios</legend>
                <p class="field-hint">Each scenario gets its own search. At least one stays selected.</p>
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
                <x-ui.input name="repetitions" id="repetitions" label="Repetitions" type="number" min="1" max="3" hint="1–3. Default 1: a search can take up to 3 minutes." required x-model.number="repetitions" />
                <div class="min-w-0">
                    <label for="cap" class="field-label">Cap (instances)</label>
                    <input id="cap" name="cap" class="input" type="number" min="1" max="{{ $config['maxCap'] }}" step="1" required x-model.number="cap" aria-describedby="cap-hint">
                    <p id="cap-hint" class="field-hint">Default 1,024, at most 4,096. Per crowd: <span class="num" x-text="capCopies"></span> copies. Reaching the cap records "≥ cap".</p>
                </div>
                @foreach (['resolution' => 'Resolution', 'camera' => 'Camera', 'lighting' => 'Lighting', 'background' => 'Background'] as $type => $typeLabel)
                    <div>
                        <label for="preset-{{ $type }}" class="field-label">{{ $typeLabel }}</label>
                        <select id="preset-{{ $type }}" class="select" x-model="presets.{{ $type }}">
                            @foreach ($presets[$type] ?? [] as $id => $presetName)
                                <option value="{{ $id }}" @selected($id === $defaults[$type])>{{ $presetName }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
            <p class="flex items-center gap-2 text-body-sm" role="status"><x-ui.icon name="timer" /> Estimated run time: <span class="num font-semibold" x-text="estimate"></span></p>
        </x-performance.step-card>

        <x-performance.step-card number="3" title="Target" description="The Crowd uses the Target frame rate as its pass criterion: a probe passes when it is Smooth at that rate. The test stores a copy.">
            <x-performance.target-step :target="$target" />
        </x-performance.step-card>

        <x-performance.step-card number="4" title="Device" description="The run records the confirmed device class.">
            <div class="flex flex-wrap items-center gap-3">
                <x-performance.device-chip />
                <a href="{{ route('device.show', ['return_to' => '/lab/crowd']) }}" class="link text-body-sm">Confirm or change</a>
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

    <x-assets.asset-selector :tools="$tools" :objects="$objects" :max="8" />
</x-layouts::app>
