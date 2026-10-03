@props([
    'name',
    'size' => 'sm',
    'label' => null,
])
@php
    $sizeClass = $size === 'md' ? 'size-[var(--icon-md)]' : 'size-[var(--icon-sm)]';
    $a11y = $label !== null ? ['role' => 'img', 'aria-label' => $label] : ['aria-hidden' => 'true'];
@endphp
{{-- Phosphor, Bold weight only (DESIGN.md §6). --}}
{{ svg('phosphor-'.$name.'-bold', trim($sizeClass.' shrink-0 '.$attributes->get('class')), array_merge($a11y, $attributes->except('class')->getAttributes())) }}
