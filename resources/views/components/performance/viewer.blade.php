@props(['mode' => 'preview', 'state' => null, 'label' => null])
{{-- Shared viewer container. M1.2: placeholder only; the Three.js viewer mounts here from M3.1 (data-mode). --}}
<figure
    data-viewer
    data-mode="{{ $mode }}"
    {{ $attributes->merge(['class' => 'viewer-frame']) }}
    @if ($state === 'loading') aria-busy="true" @endif
>
    @if ($label)
        <figcaption class="viewer-label">{{ $label }}</figcaption>
    @endif
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
            <p>The 3D viewer arrives in a later milestone.</p>
        </div>
    @endif
</figure>
