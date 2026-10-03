@props(['id', 'hint' => null, 'error' => null])
@if ($error)
    <p id="{{ $id }}-error" class="field-error"><x-ui.icon name="warning-circle" />{{ $error }}</p>
@elseif ($hint)
    <p id="{{ $id }}-hint" class="field-hint">{{ $hint }}</p>
@endif
