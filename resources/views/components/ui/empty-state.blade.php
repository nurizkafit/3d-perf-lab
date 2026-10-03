@props([
    'icon' => 'cube',
    'message',
])
{{-- Icon, one sentence, one primary action (DESIGN.md §5.1). --}}
<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <x-ui.icon :name="$icon" size="md" />
    <p>{{ $message }}</p>
    {{ $slot }}
</div>
