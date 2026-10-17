{{-- Error pages (PROJECT.md M5.1): design-system styles, no session, database, or user data, so they also render
     when something else is broken. Each page: status code, one sentence, one action. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-svh place-items-center bg-neutral-100 p-[var(--page-padding-inline)] text-neutral-950">
    <main class="card w-full max-w-[var(--readable-width)] space-y-4">
        <p class="section-label">Error @yield('code')</p>
        <h1 class="text-h1 font-bold">@yield('title')</h1>
        <p class="text-neutral-600">@yield('message')</p>
        <div class="flex flex-wrap gap-3">
            @yield('action')
            <a href="{{ url('/') }}" class="btn" data-variant="outline">Home</a>
        </div>
    </main>
</body>
</html>
