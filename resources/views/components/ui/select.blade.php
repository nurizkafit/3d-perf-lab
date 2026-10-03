@props([
    'name',
    'label',
    'options' => [],
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
])
@php
    $id ??= $name;
    $error ??= $errors->first($name) ?: null;
    $selected = (string) old($name, $value);
    $describedBy = $error ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'min-w-0']) }}>
    <label for="{{ $id }}" class="field-label">{{ $label }}</label>
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->except('class')->merge(['class' => 'select']) }}
    >
        @if ($placeholder)
            <option value="" @selected($selected === '')>{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    <x-ui.field-messages :id="$id" :hint="$hint" :error="$error" />
</div>
