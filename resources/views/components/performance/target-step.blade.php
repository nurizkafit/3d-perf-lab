@props(['target'])
{{-- Target step shared by both methods (PRD.md §6.3): select a Target, adjust its three values for this test.
     The test stores a copy of the final values. Dispatches "target-change" with the current values. --}}
<div x-data="targetStep" class="space-y-4">
    <script type="application/json" data-target-config>@json($target)</script>
    <div class="max-w-sm">
        <label for="target-select" class="field-label">Target</label>
        <select id="target-select" name="target[target_id]" class="select" x-model="targetId" x-on:change="applyTarget()">
            @foreach ($target['targets'] as $option)
                <option value="{{ $option['target_id'] }}" @selected($option['target_id'] === $target['suggestedId'])>{{ $option['name'] }}</option>
            @endforeach
        </select>
        <p class="field-hint">
            @if ($target['suggestedForDevice'])
                Suggested for the confirmed device class.
            @else
                Confirm the device class to get the matching Target suggested.
            @endif
            <span x-show="changed" x-cloak> · Adjusted for this test.</span>
        </p>
    </div>
    <div class="grid max-w-2xl gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,11rem),1fr))]">
        @foreach ($target['fields'] as $key => $field)
            <div class="min-w-0">
                <label for="target-{{ $key }}" class="field-label">{{ $field['label'] }} @if ($field['rule'])<span class="font-normal">{{ $field['rule'] }}</span>@endif</label>
                <span class="flex items-center gap-2">
                    <input id="target-{{ $key }}" class="input w-28" type="number" name="target[{{ $key }}]" min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="{{ $field['step'] }}"
                           x-model.number="values.{{ $key }}" x-on:input="changed = true; emit()" aria-describedby="target-{{ $key }}-hint">
                    <span class="text-body-sm text-neutral-600">{{ $field['unit'] }}</span>
                </span>
                <p id="target-{{ $key }}-hint" class="field-hint">{{ $field['help'] }}</p>
            </div>
        @endforeach
    </div>
</div>
