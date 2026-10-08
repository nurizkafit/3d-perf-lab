@props(['list', 'values', 'deviceClasses'])
@use('App\Support\MetricDefinitions')
@use('App\Support\OptionLists')
{{-- Structured fields per option list (PRD.md §11). Never a raw JSON field. --}}
@if ($list === 'camera')
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
            <x-ui.input name="fov" label="Field of view (°)" type="number" step="0.1" :value="$values['fov']" hint="10–120" />
            <x-ui.select name="position_mode" label="Position" :options="['auto_frame' => 'Auto-frame', 'fixed' => 'Fixed']" :value="$values['position_mode']" />
            <x-ui.input name="distance_factor" label="Distance factor" type="number" step="0.1" :value="$values['distance_factor']" hint="× bounding sphere, 0.5–10" />
            <x-ui.input name="elevation_deg" label="Elevation (°)" type="number" step="1" :value="$values['elevation_deg']" hint="−90 to 90" />
            <x-ui.input name="azimuth_deg" label="Azimuth (°)" type="number" step="1" :value="$values['azimuth_deg']" hint="0–360" />
        </div>

@elseif ($list === 'lighting')
        @php
            $lights = old('lights', $values['lights']);
        @endphp
        <div class="max-w-xs">
            <x-ui.input name="ambient_intensity" label="Ambient intensity" type="number" step="0.1" :value="$values['ambient_intensity']" hint="0–10" />
        </div>
        <fieldset class="space-y-3" x-data="{ count: {{ count($lights) }} }">
            <legend class="field-label">Directional lights (1–3)</legend>
            @foreach (range(0, 2) as $i)
                <div class="grid items-end gap-4 rounded-md border border-neutral-200 p-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,10rem),1fr))]"
                     @if ($i >= count($lights)) x-cloak @endif x-show="count > {{ $i }}">
                    <p class="text-label font-semibold sm:col-span-full">Light {{ $i + 1 }}</p>
                    <x-ui.input :name="'lights['.$i.'][intensity]'" :id="'light-'.$i.'-intensity'" label="Intensity" type="number" step="0.1" :value="$lights[$i]['intensity'] ?? 1" :error="$errors->first('lights.'.$i.'.intensity') ?: null" x-bind:disabled="count <= {{ $i }}" />
                    <x-ui.input :name="'lights['.$i.'][elevation_deg]'" :id="'light-'.$i.'-elevation'" label="Elevation (°)" type="number" step="1" :value="$lights[$i]['elevation_deg'] ?? 45" :error="$errors->first('lights.'.$i.'.elevation_deg') ?: null" x-bind:disabled="count <= {{ $i }}" />
                    <x-ui.input :name="'lights['.$i.'][azimuth_deg]'" :id="'light-'.$i.'-azimuth'" label="Azimuth (°)" type="number" step="1" :value="$lights[$i]['azimuth_deg'] ?? 45" :error="$errors->first('lights.'.$i.'.azimuth_deg') ?: null" x-bind:disabled="count <= {{ $i }}" />
                </div>
            @endforeach
            <div class="flex gap-2">
                <x-ui.button size="sm" variant="secondary" icon="plus" x-on:click="count = Math.min(3, count + 1)" x-bind:disabled="count >= 3">Add light</x-ui.button>
                <x-ui.button size="sm" variant="outline" x-on:click="count = Math.max(1, count - 1)" x-bind:disabled="count <= 1">Remove last light</x-ui.button>
            </div>
        </fieldset>
        <label class="flex min-h-[var(--control-height)] items-center gap-2 text-body-sm font-semibold">
            <input type="hidden" name="environment_map" value="0">
            <input type="checkbox" name="environment_map" value="1" class="input" @checked(old('environment_map', $values['environment_map']))> Environment map
        </label>

@elseif ($list === 'background')
        <div class="max-w-md">
            <x-ui.select name="color_token" label="Color (design token)" :options="OptionLists::BACKGROUND_TOKENS" :value="$values['color_token']" />
        </div>

@elseif ($list === 'resolution')
        <div class="grid max-w-md gap-4 sm:grid-cols-2">
            <x-ui.input name="width" label="Width (px)" type="number" :value="$values['width']" hint="Drawing buffer, 320–3840" />
            <x-ui.input name="height" label="Height (px)" type="number" :value="$values['height']" hint="Drawing buffer, 320–3840" />
        </div>

@elseif ($list === 'device-classes')
        <div class="grid max-w-xl gap-4 sm:grid-cols-2">
            <x-ui.select name="category" label="Category" :options="OptionLists::CATEGORIES" :value="$values['category']" />
            <x-ui.select name="platform" label="Platform" :options="OptionLists::PLATFORMS" :value="$values['platform']" />
        </div>

@elseif ($list === 'targets')
        @php
            $checkedClasses = array_map('intval', old('device_class_ids', $values['device_class_ids']));
        @endphp
        <div class="grid max-w-2xl gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
            @foreach (MetricDefinitions::TARGET_FIELDS as $key => $field)
                <x-ui.input :name="$key" :label="$field['label'].' ('.$field['unit'].')'" type="number" :step="$field['step']" :value="$values[$key]"
                    :hint="$field['help']" />
            @endforeach
        </div>

        <fieldset class="space-y-2">
            <legend class="field-label">Device classes</legend>
            <p class="text-body-sm text-neutral-600">The Lab suggests this Target for these classes.</p>
            <div class="grid gap-2 [grid-template-columns:repeat(auto-fill,minmax(min(100%,16rem),1fr))]">
                @foreach ($deviceClasses as $class)
                    <label class="flex min-h-[var(--control-height)] items-center gap-2 rounded-md border border-neutral-200 px-3 text-body-sm has-[:checked]:border-neutral-800 has-[:checked]:bg-primary-tint">
                        <input type="checkbox" class="input" name="device_class_ids[]" value="{{ $class->id }}" @checked(in_array($class->id, $checkedClasses, true))>
                        {{ $class->name }}@unless ($class->is_active) <span class="text-neutral-600">(inactive)</span>@endunless
                    </label>
                @endforeach
            </div>
        </fieldset>
@endif
