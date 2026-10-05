{{-- Always visible in the app header (DESIGN.md §8.1). $confirmedClass comes from a view composer (AppServiceProvider). --}}
@if ($confirmedClass)
    <a href="{{ route('device.show', ['return_to' => request()->getRequestUri()]) }}" class="device-chip" title="Change device class">
        <x-ui.icon name="device-mobile" /><span class="truncate">{{ $confirmedClass }}</span><x-ui.icon name="caret-down" />
    </a>
@else
    <a href="{{ route('device.show', ['return_to' => request()->getRequestUri()]) }}" class="device-chip" data-warning>
        <x-ui.icon name="warning" /><span class="truncate">Device class not set</span>
    </a>
@endif
