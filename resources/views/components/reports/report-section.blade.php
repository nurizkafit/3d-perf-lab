@props(['id', 'label', 'title' => null])
<section id="{{ $id }}" aria-labelledby="{{ $id }}-label" {{ $attributes->merge(['class' => 'space-y-3 scroll-mt-6']) }}>
    <x-ui.section-label :id="$id.'-label'">{{ $label }}</x-ui.section-label>
    @if ($title)<h2 class="text-h2 font-bold">{{ $title }}</h2>@endif
    {{ $slot }}
</section>
