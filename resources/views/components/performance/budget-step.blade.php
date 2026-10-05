@props(['budget'])
{{-- Budget step shared by all methods (PRD.md §6.3): select a preset, adjust values for this test.
     The test stores a copy of the final values. Only items that apply to the method are listed. --}}
<div x-data="budgetStep" class="space-y-4">
    <script type="application/json" data-budget-config>@json($budget)</script>
    <div class="max-w-sm">
        <label for="budget-preset" class="field-label">Budget preset</label>
        <select id="budget-preset" name="budget[preset_id]" class="select" x-model="presetId" x-on:change="applyPreset()">
            @foreach ($budget['presets'] as $preset)
                <option value="{{ $preset['id'] }}" @selected($preset['id'] === $budget['suggestedId'])>{{ $preset['name'] }}</option>
            @endforeach
        </select>
        <p class="field-hint">
            @if ($budget['suggestedForDevice'])
                Suggested for the confirmed device class.
            @else
                Confirm the device class to get the matching preset suggested.
            @endif
            <span x-show="changed" x-cloak> · Adjusted for this test.</span>
        </p>
    </div>
    <x-ui.table caption="Budget items for this test">
        <x-slot:head><tr><th scope="col">Enabled</th><th scope="col">Budget item</th><th scope="col">Rule</th><th scope="col">Limit</th></tr></x-slot:head>
        @foreach ($budget['items'] as $key => $item)
            <tr>
                <td>
                    <input type="hidden" name="budget[items][{{ $key }}][enabled]" value="0">
                    <input type="checkbox" class="input" name="budget[items][{{ $key }}][enabled]" value="1" x-model="items.{{ $key }}.enabled" x-on:change="changed = true" aria-label="Enable {{ $item['label'] }}">
                </td>
                <th scope="row" class="cell-wrap">
                    <span class="block font-semibold">{{ $item['label'] }}</span>
                    <span class="block max-w-96 text-body-sm font-normal text-neutral-600">{{ $item['help'] }}</span>
                </th>
                <td>{{ $item['rule'] }}</td>
                <td>
                    <span class="flex items-center gap-2">
                        <input class="input w-28" type="number" step="any" min="0" name="budget[items][{{ $key }}][limit]" x-model="items.{{ $key }}.limit"
                               x-on:input="changed = true" x-bind:disabled="!items.{{ $key }}.enabled" aria-label="Limit for {{ $item['label'] }}">
                        <span class="text-body-sm text-neutral-600">{{ $item['unit'] }}</span>
                    </span>
                </td>
            </tr>
        @endforeach
    </x-ui.table>
</div>
