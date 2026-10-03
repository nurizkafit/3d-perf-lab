@props(['title'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-svh place-items-center bg-neutral-100 p-[var(--page-padding-inline)] text-neutral-950">
    <main id="main" class="w-full max-w-sm space-y-6">
        <a href="{{ route('home') }}" class="block text-center font-heading text-h2 font-bold">Render Test</a>
        {{ $slot }}
    </main>
</body>
</html>
