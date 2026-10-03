@props([
    'id',
    'label',
    'icon' => 'caret-down',
    'variant' => 'outline',
    'align' => 'start',
])
{{-- Basecoat dropdown menu. Put <button role="menuitem" class="..."> or <a role="menuitem"> items in the slot. --}}
<div id="{{ $id }}" {{ $attributes->merge(['class' => 'dropdown-menu']) }}>
    <button
        type="button"
        id="{{ $id }}-trigger"
        class="btn"
        data-variant="{{ $variant }}"
        data-size="default"
        aria-haspopup="menu"
        aria-controls="{{ $id }}-menu"
        aria-expanded="false"
    >
        {{ $label }}
        <x-ui.icon :name="$icon" />
    </button>
    <div id="{{ $id }}-popover" data-popover data-align="{{ $align }}" aria-hidden="true" class="min-w-48">
        <div role="menu" id="{{ $id }}-menu" aria-labelledby="{{ $id }}-trigger">
            {{ $slot }}
        </div>
    </div>
</div>
