@props([
    'id',
    'tabs' => [],
    'selected' => null,
])
{{-- Basecoat tabs. $tabs maps a key to a label; each key needs a named slot with the panel content. --}}
@php
    $selected ??= array_key_first($tabs);
@endphp
<div id="{{ $id }}" {{ $attributes->merge(['class' => 'tabs']) }}>
    <nav role="tablist" aria-orientation="horizontal">
        @foreach ($tabs as $key => $tabLabel)
            <button
                type="button"
                role="tab"
                id="{{ $id }}-tab-{{ $key }}"
                aria-controls="{{ $id }}-panel-{{ $key }}"
                aria-selected="{{ $key === $selected ? 'true' : 'false' }}"
                tabindex="{{ $key === $selected ? '0' : '-1' }}"
            >{{ $tabLabel }}</button>
        @endforeach
    </nav>
    @foreach ($tabs as $key => $tabLabel)
        <div
            role="tabpanel"
            id="{{ $id }}-panel-{{ $key }}"
            aria-labelledby="{{ $id }}-tab-{{ $key }}"
            tabindex="-1"
            @if ($key !== $selected) hidden @endif
        >{{ ${$key} ?? '' }}</div>
    @endforeach
</div>
