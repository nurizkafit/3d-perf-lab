@props(['title'])
@php($user = auth()->user())
<header class="app-header">
    <div class="flex min-w-0 items-center gap-3">
        <x-ui.button variant="outline" size="icon" class="lg:hidden" x-on:click="openMobile()" aria-controls="app-sidebar" x-bind:aria-expanded="mobileOpen" aria-expanded="false" aria-label="Open navigation">
            <x-ui.icon name="list" />
        </x-ui.button>
        <p class="truncate font-semibold">{{ $title }}</p>
    </div>
    <div class="flex items-center gap-2">
        <x-performance.device-chip />
        <x-ui.dropdown id="user-menu" :label="$user->name" variant="ghost" align="end">
            <span role="menuitem" aria-disabled="true" class="text-neutral-600">{{ $user->email }} · {{ ucfirst($user->role) }}</span>
            <hr role="separator">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" role="menuitem" class="w-full"><x-ui.icon name="sign-out" /> Log out</button>
            </form>
        </x-ui.dropdown>
    </div>
</header>
