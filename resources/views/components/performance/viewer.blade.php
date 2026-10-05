@props([
    'mode' => 'preview',
    'src' => null,
    'config' => [],
    'state' => null,
    'label' => null,
    'placeholder' => 'No model to show.',
])
{{-- Shared viewer container (AGENT.md §6.2). No Three.js in Blade: resources/js/three/viewer.js mounts
     into [data-viewer-stage] when data-src is set. Without a source it shows a placeholder or a given state. --}}
<figure
    data-viewer
    data-mode="{{ $mode }}"
    @if ($src) data-src="{{ $src }}" data-state="loading" aria-busy="true" @elseif ($state) data-state="{{ $state }}" @endif
    {{ $attributes->merge(['class' => 'viewer-frame']) }}
>
    @if ($src)
        <script type="application/json" data-viewer-config>@json($config)</script>
        <div class="viewer-stage" data-viewer-stage></div>
        @if ($label)<figcaption class="viewer-label">{{ $label }}</figcaption>@endif

        <div class="viewer-message viewer-overlay" data-show-when="loading" role="status">
            <x-ui.icon name="circle-notch" size="md" class="spin" />
            <p>Loading the 3D model…</p>
        </div>
        <div class="viewer-message viewer-overlay" data-show-when="error" role="alert">
            <x-ui.icon name="warning-circle" size="md" />
            <p data-viewer-status>The model could not be shown.</p>
        </div>

        <div class="viewer-controls" data-show-when="ready">
            <x-ui.button variant="secondary" size="icon" data-viewer-reset aria-label="Reset view" title="Reset view"><x-ui.icon name="arrows-clockwise" /></x-ui.button>
            <x-ui.button variant="secondary" size="icon" data-viewer-fullscreen aria-label="Fullscreen" title="Fullscreen"><x-ui.icon name="corners-out" /></x-ui.button>
            <x-ui.button variant="secondary" size="icon" data-viewer-gizmo aria-pressed="true" aria-label="Show axis gizmo" title="Axis gizmo"><x-ui.icon name="crosshair" /></x-ui.button>
        </div>
        <p class="viewer-hint" data-show-when="ready">
            <span class="hint-pointer">Drag to orbit · Right-drag to pan · Scroll to zoom</span>
            <span class="hint-touch">Drag to orbit · Two fingers to pan and zoom</span>
        </p>
    @else
        @if ($label)<figcaption class="viewer-label">{{ $label }}</figcaption>@endif
        @if ($state === 'loading')
            <div class="viewer-message" role="status">
                <x-ui.icon name="circle-notch" size="md" class="spin" />
                <p>Loading the 3D scene…</p>
            </div>
        @elseif ($state === 'error')
            <div class="viewer-message" role="alert">
                <x-ui.icon name="warning-circle" size="md" />
                <p>The 3D scene could not be loaded. The recorded results below are not affected.</p>
            </div>
        @else
            <div class="viewer-message">
                <x-ui.icon name="cube-transparent" size="md" />
                <p>{{ $placeholder }}</p>
            </div>
        @endif
    @endif
</figure>
