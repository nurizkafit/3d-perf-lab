@props(['title'])
<!DOCTYPE html>
{{-- Benchmark shell (DESIGN.md §9.3): no sidebar, no header, no toasts, no motion. --}}
<html lang="en" data-benchmark="running">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="benchmark-shell">
    {{ $slot }}
</body>
</html>
