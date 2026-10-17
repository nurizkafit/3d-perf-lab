@props(['groups' => [], 'maxCopies' => 64, 'alpine' => false, 'name' => 'crowd-motion'])
{{-- Crowd explore viewer (BENCHMARK.md §4.6, decision D64): one asset or tool set, a motion scenario, a copy count.
     Not measured. Wired by three/crowd-viewer.js mountCrowdExplorer(). With alpine = true the group options and the
     slider maximum come from the surrounding Alpine component (groups, capCopies). --}}
<div {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <div class="pane-toolbar">
        <div class="min-w-0">
            <label for="{{ $name }}-group" class="sr-only">Asset or tool set</label>
            <select id="{{ $name }}-group" class="select" data-crowd-group>
                @if ($alpine)
                    <template x-for="(group, index) in groups" :key="index"><option :value="index" x-text="group.label"></option></template>
                @else
                    @foreach ($groups as $index => $label)<option value="{{ $index }}">{{ $label }}</option>@endforeach
                @endif
            </select>
        </div>
        <fieldset>
            <legend class="sr-only">Motion</legend>
            @foreach (\App\Support\MetricDefinitions::MOTIONS as $key => $motion)
                <label class="nav-link cursor-pointer has-[:checked]:bg-primary-tint has-[:checked]:border-neutral-800 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-primary" title="{{ $motion['help'] }}">
                    <input type="radio" class="sr-only" name="{{ $name }}" value="{{ $key }}" data-crowd-motion @checked($loop->first)> {{ $motion['label'] }}
                </label>
            @endforeach
        </fieldset>
        <div class="flex min-w-0 flex-wrap items-center gap-2">
            <label for="{{ $name }}-copies" class="field-label m-0">Copies</label>
            <input id="{{ $name }}-copies" type="range" min="1" value="16" class="w-40" data-crowd-copies
                   @if ($alpine) :max="capCopies" @else max="{{ max(1, $maxCopies) }}" @endif>
            <output for="{{ $name }}-copies" class="num text-body-sm" data-crowd-copies-value></output>
        </div>
        <x-ui.button variant="secondary" size="sm" icon="arrows-out-cardinal" data-crowd-shake disabled>Shake</x-ui.button>
        <x-ui.button variant="secondary" size="sm" icon="arrows-clockwise" data-crowd-reset>Reset view</x-ui.button>
        <x-ui.button variant="secondary" size="sm" icon="corners-out" data-crowd-fullscreen>Fullscreen</x-ui.button>
    </div>
    <div class="viewer-frame pane-view" data-crowd-frame data-state="loading">
        <div class="viewer-stage" data-crowd-stage></div>
        <div class="viewer-message viewer-overlay" data-show-when="loading" role="status">
            <x-ui.icon name="circle-notch" size="md" class="spin" />
            <p>Loading the 3D models…</p>
        </div>
        <div class="viewer-message viewer-overlay" data-show-when="error" role="alert">
            <x-ui.icon name="warning-circle" size="md" />
            <p data-crowd-error>The models could not be shown.</p>
        </div>
    </div>
    <p class="text-body-sm text-neutral-600">
        <span class="hint-pointer">Not measured. Drag to orbit · Right-drag to pan · Scroll to zoom · Arrow keys pan, Shift + arrows orbit. The slider applies when you release it.</span>
        <span class="hint-touch">Not measured. Drag to orbit · Two fingers to pan and zoom.</span>
    </p>
</div>
