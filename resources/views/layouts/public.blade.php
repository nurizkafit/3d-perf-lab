@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? 'Measure how 3D web assets load and render in real browsers on real devices.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-svh flex-col bg-neutral-100 text-neutral-950">
    <a href="#main" class="skip-link">Skip to content</a>
    <x-navigation.public-header />
    <main id="main" tabindex="-1" {{ $attributes->merge(['class' => 'flex-1']) }}>
        {{ $slot }}
    </main>
    <x-navigation.public-footer />
    <x-ui.toast />
</body>
</html>
