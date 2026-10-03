@props([
    'variant' => 'neutral',
    'status' => null,
    'icon' => null,
])
@php
    // Budget outcomes always show an icon and a word, never color only (DESIGN.md §2.2).
    $statuses = [
        'pass' => ['success', 'check-circle', 'Pass'],
        'fail' => ['destructive', 'x-circle', 'Fail'],
        'not-measured' => ['outline', 'minus-circle', 'Not measured'],
        'warning' => ['warning', 'warning', 'Warning'],
    ];
    if ($status !== null) {
        [$variant, $icon, $word] = $statuses[$status];
    }
@endphp
<span {{ $attributes->merge(['class' => 'badge', 'data-variant' => $variant]) }}>
    @if ($icon)<x-ui.icon :name="$icon" />@endif
    {{ $slot->isEmpty() && isset($word) ? $word : $slot }}
</span>
