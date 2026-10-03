@props([
    'href' => null,
    'size' => 'default',
])
@php
    $attrs = $attributes->merge(['class' => 'card'])->merge($size === 'sm' ? ['data-size' => 'sm'] : []);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attrs->merge(['data-clickable' => true]) }}>{{ $slot }}</a>
@else
    <div {{ $attrs }}>{{ $slot }}</div>
@endif
