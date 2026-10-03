<x-layouts::app title="Create user" description="The new user logs in with this email and the temporary password." :sample="false">
    <form method="POST" action="{{ route('admin.users.store') }}" class="card max-w-2xl space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input name="name" label="Display name" required hint="Shown as Author on this user's uploads." />
            <x-ui.input name="email" label="Email" type="email" required autocomplete="off" />
            <x-ui.select name="role" label="Role" :options="['user' => 'User', 'admin' => 'Admin']" value="user" />
            <x-ui.input name="password" label="Temporary password" type="password" required autocomplete="new-password" hint="At least 8 characters. Share it through a secure channel." />
        </div>
        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit" icon="user-plus">Create user</x-ui.button>
            <x-ui.button :href="route('admin.users.index')" variant="outline">Cancel</x-ui.button>
        </div>
    </form>
</x-layouts::app>
