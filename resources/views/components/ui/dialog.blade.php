@props([
    'id',
    'title',
    'description' => null,
])
{{-- Native modal <dialog>: focus stays inside, Escape closes, focus returns to the trigger.
     Open with any element that has data-dialog-open="{{ id }}". --}}
<dialog
    id="{{ $id }}"
    aria-labelledby="{{ $id }}-title"
    @if ($description) aria-describedby="{{ $id }}-description" @endif
    {{ $attributes->merge(['class' => 'dialog']) }}
>
    <div>
        <header>
            <h2 id="{{ $id }}-title">{{ $title }}</h2>
            @if ($description)<p id="{{ $id }}-description">{{ $description }}</p>@endif
        </header>
        <section>{{ $slot }}</section>
        @isset($footer)
            <footer>{{ $footer }}</footer>
        @endisset
        <form method="dialog">
            <x-ui.button variant="ghost" size="icon" type="submit" aria-label="Close dialog"><x-ui.icon name="x" /></x-ui.button>
        </form>
    </div>
</dialog>
