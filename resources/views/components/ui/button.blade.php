@props([
    'variant' => 'primary',
    'size' => 'default',
    'icon' => null,
    'href' => null,
    'type' => 'button',
    'loading' => false,
    'disabled' => false,
])
@php
    $attrs = $attributes->merge([
        'class' => 'btn',
        'data-variant' => $variant,
        'data-size' => $size,
    ]);
    $inactive = $disabled || $loading;
@endphp
@if ($href && ! $inactive)
    <a href="{{ $href }}" {{ $attrs }}>
        @if ($icon)<x-ui.icon :name="$icon" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attrs }} @disabled($inactive) @if ($loading) aria-busy="true" @endif>
        @if ($loading)
            <x-ui.icon name="circle-notch" class="spin" />
        @elseif ($icon)
            <x-ui.icon :name="$icon" />
        @endif
        {{ $slot }}
    </button>
@endif
