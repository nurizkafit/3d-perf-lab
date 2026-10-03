@php
    $links = [
        'home' => 'Home',
        'reports.index' => 'Reports',
        'about' => 'About',
        'contact' => 'Contact',
    ];
    $isCurrent = fn ($route) => request()->routeIs($route) || ($route === 'reports.index' && request()->routeIs('reports.show'));
@endphp
<header class="border-b border-neutral-200 bg-neutral-0" x-data="{ open: false }">
    <div class="page-inline mx-auto flex h-[var(--header-height)] max-w-[var(--content-max-width)] items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="font-heading text-h3 font-bold no-underline">Render Test</a>

        <nav aria-label="Main" class="hidden items-center gap-1 md:flex">
            @foreach ($links as $route => $label)
                <a href="{{ route($route) }}" class="nav-link" @if ($isCurrent($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            @auth
                <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="nav-link">Log in</a>
            @endauth
        </nav>

        <x-ui.button variant="outline" size="sm" class="md:hidden" icon="list"
            x-on:click="open = !open" x-bind:aria-expanded="open" aria-controls="mobile-nav" aria-expanded="false">
            Menu
        </x-ui.button>
    </div>

    <nav id="mobile-nav" aria-label="Main" class="page-inline border-t border-neutral-200 pb-3 md:hidden" x-show="open" x-cloak>
        @foreach ($links as $route => $label)
            <a href="{{ route($route) }}" class="nav-link flex" @if ($isCurrent($route)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
        @auth
            <a href="{{ route('dashboard') }}" class="nav-link flex">Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="nav-link flex">Log in</a>
        @endauth
    </nav>
</header>
