@props(['deviceClass' => session('device_class')])
{{-- Always visible in the app header (DESIGN.md §8.1). Confirmation is stored from M4.1. --}}
@if ($deviceClass)
    <a href="{{ route('device.show') }}" class="device-chip" title="Change device class">
        <x-ui.icon name="device-mobile" /><span class="truncate">{{ $deviceClass }}</span><x-ui.icon name="caret-down" />
    </a>
@else
    <a href="{{ route('device.show') }}" class="device-chip" data-warning>
        <x-ui.icon name="warning" /><span class="truncate">Device class not set</span>
    </a>
@endif
