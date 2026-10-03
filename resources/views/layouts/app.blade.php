@props([
    'title',
    'description' => null,
    'sample' => true,
])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-100 text-neutral-950" x-data="appShell" x-on:keydown.escape.window="closeMobile()">
    <a href="#main" class="skip-link">Skip to content</a>
    <div class="app-shell" :data-collapsed="collapsed">
        <x-navigation.sidebar />
        <div class="app-main">
            <x-navigation.header :title="$title" />
            <main id="main" tabindex="-1" class="page-inline page-block mx-auto w-full max-w-[var(--content-max-width)] space-y-6">
                {{-- Page header: title, short description, primary action on the right (DESIGN.md §8.2). --}}
                <header class="flex flex-wrap items-end justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-h1 font-bold">{{ $title }}</h1>
                            @if ($sample)<x-ui.badge variant="sample">Sample data</x-ui.badge>@endif
                        </div>
                        @if ($description)<p class="max-w-[var(--readable-width)] text-neutral-600">{{ $description }}</p>@endif
                    </div>
                    @isset($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endisset
                </header>
                {{-- Result of the last action. Field errors show next to their fields. --}}
                @if (session('status'))
                    <p class="alert-box" role="status"><x-ui.icon name="check-circle" class="text-success" />{{ session('status') }}</p>
                @endif
                @foreach (['item', 'user'] as $errorKey)
                    @error($errorKey)
                        <p class="alert-box" data-error role="alert"><x-ui.icon name="warning-circle" />{{ $message }}</p>
                    @enderror
                @endforeach
                {{ $slot }}
            </main>
        </div>
    </div>
    <x-ui.toast />
</body>
</html>
