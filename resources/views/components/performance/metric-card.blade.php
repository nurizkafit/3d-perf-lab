@props(['label', 'value', 'context' => null])
{{-- Static card: label above value above context. Never shows rank or a "best" marker (DESIGN.md §5.1). --}}
<div {{ $attributes->merge(['class' => 'card space-y-1', 'data-size' => 'sm']) }}>
    <p class="text-label font-semibold">{{ $label }}</p>
    <p class="font-heading text-metric font-bold">{{ $value }}</p>
    @if ($context)<p class="text-body-sm text-neutral-600">{{ $context }}</p>@endif
</div>
