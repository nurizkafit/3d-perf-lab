{{-- Look controls of the linked panes (BENCHMARK.md §4.3): one motion switch for all panes, Shake (Physics only),
     reset view, fullscreen. Wired by three/panes.js wireToolbar(). Not measured. --}}
<div class="pane-toolbar" role="group" aria-label="Look controls">
    <fieldset>
        <legend class="sr-only">Motion in all panes</legend>
        @foreach (\App\Support\MetricDefinitions::MOTIONS as $key => $motion)
            <label class="nav-link cursor-pointer has-[:checked]:bg-primary-tint has-[:checked]:border-neutral-800 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-primary" title="{{ $motion['help'] }}">
                <input type="radio" class="sr-only" name="{{ $attributes->get('name', 'look-motion') }}" value="{{ $key }}" data-panes-motion @checked($loop->first)> {{ $motion['label'] }}
            </label>
        @endforeach
    </fieldset>
    <x-ui.button variant="secondary" size="sm" icon="arrows-out-cardinal" data-panes-shake disabled>Shake</x-ui.button>
    <x-ui.button variant="secondary" size="sm" icon="arrows-clockwise" data-panes-reset>Reset view</x-ui.button>
    <x-ui.button variant="secondary" size="sm" icon="corners-out" data-panes-fullscreen>Fullscreen</x-ui.button>
    <p class="text-body-sm text-neutral-600">
        <span class="hint-pointer">Drag to orbit · Right-drag to pan · Scroll to zoom · Arrow keys pan, Shift + arrows orbit. All panes move together.</span>
        <span class="hint-touch">Drag to orbit · Two fingers to pan and zoom. All panes move together.</span>
    </p>
</div>
