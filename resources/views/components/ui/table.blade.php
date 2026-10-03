@props([
    'caption' => null,
    'scroll' => false,
])
{{-- Horizontal scroll on narrow screens; never truncate numbers. Mark numeric cells with data-numeric. --}}
<div class="table-container" @if ($scroll) data-scroll @endif tabindex="0" role="region" aria-label="{{ $caption ?? 'Table' }}">
    <table {{ $attributes->merge(['class' => 'table']) }}>
        @if ($caption)<caption class="sr-only">{{ $caption }}</caption>@endif
        @isset($head)<thead>{{ $head }}</thead>@endisset
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
