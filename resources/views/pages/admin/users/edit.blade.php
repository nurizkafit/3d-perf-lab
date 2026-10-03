<x-layouts::app :title="'Edit '.$user->name" description="Change the name, email, or role." :sample="false">
    <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card space-y-4">
            @csrf
            @method('PUT')
            <x-ui.section-label as="h2">Account</x-ui.section-label>
            <x-ui.input name="name" label="Display name" :value="$user->name" required />
            <x-ui.input name="email" label="Email" type="email" :value="$user->email" required />
            <x-ui.select name="role" label="Role" :options="['user' => 'User', 'admin' => 'Admin']" :value="$user->role" />
            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit" icon="floppy-disk">Save</x-ui.button>
                <x-ui.button :href="route('admin.users.index')" variant="outline">Cancel</x-ui.button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="card space-y-4 self-start">
            @csrf
            <x-ui.section-label as="h2">Reset password</x-ui.section-label>
            <x-ui.input name="password" label="New temporary password" type="password" required autocomplete="new-password" hint="At least 8 characters. The current password stops working at once." />
            <x-ui.button type="submit" variant="secondary" icon="key">Set temporary password</x-ui.button>
        </form>
    </div>
</x-layouts::app>
