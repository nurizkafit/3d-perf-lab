<x-layouts::auth title="Log in">
    <form method="POST" action="{{ route('login') }}" class="card space-y-5">
        @csrf
        <div class="space-y-1">
            <h1 class="text-h2 font-bold">Log in</h1>
            <p class="text-body-sm text-neutral-600">Accounts are created by an administrator.</p>
        </div>
        <x-ui.input name="email" label="Email" type="email" autocomplete="username" required autofocus />
        <x-ui.input name="password" label="Password" type="password" autocomplete="current-password" required />
        <x-ui.button type="submit" class="w-full" icon="sign-in">Log in</x-ui.button>
    </form>
</x-layouts::auth>
