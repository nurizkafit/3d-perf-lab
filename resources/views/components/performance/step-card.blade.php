@props(['number', 'title', 'description' => null])
{{-- Numbered configuration step (DESIGN.md §9.2). The body is indented to line up with the title. --}}
<section {{ $attributes->merge(['class' => 'card space-y-4']) }} aria-labelledby="step-{{ $number }}-title">
    <div class="flex items-start gap-3">
        <span class="step-number" aria-hidden="true">{{ $number }}</span>
        <div>
            <h2 id="step-{{ $number }}-title" class="text-h3 font-semibold"><span class="sr-only">Step {{ $number }}: </span>{{ $title }}</h2>
            @if ($description)<p class="text-body-sm text-neutral-600">{{ $description }}</p>@endif
        </div>
    </div>
    <div class="step-body space-y-4">
        {{ $slot }}
    </div>
</section>
