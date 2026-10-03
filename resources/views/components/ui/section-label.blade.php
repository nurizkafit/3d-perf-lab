@props(['as' => 'h2'])
<{{ $as }} {{ $attributes->merge(['class' => 'section-label']) }}>{{ $slot }}</{{ $as }}>
