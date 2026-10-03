@props([
    'name',
    'label',
    'id' => null,
    'value' => null,
    'hint' => null,
    'error' => null,
])
@php
    $id ??= $name;
    $error ??= $errors->first($name) ?: null;
    $describedBy = $error ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'min-w-0']) }}>
    <label for="{{ $id }}" class="field-label">{{ $label }}</label>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->except('class')->merge(['class' => 'textarea']) }}
    >{{ old($name, $value) }}</textarea>
    <x-ui.field-messages :id="$id" :hint="$hint" :error="$error" />
</div>
