<x-layouts::app title="Users" description="Accounts and roles. There is no self-registration; an admin creates every account." :sample="false">
    <x-slot:actions>
        <x-ui.button :href="route('admin.users.create')" icon="user-plus">Create user</x-ui.button>
    </x-slot:actions>

    <x-ui.table caption="Users">
        <x-slot:head>
            <tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col" data-numeric>Last login</th><th scope="col"><span class="sr-only">Actions</span></th></tr>
        </x-slot:head>
        @foreach ($users as $user)
            <tr>
                <th scope="row" class="font-semibold">
                    {{ $user->name }}
                    @if ($user->is(auth()->user()))<span class="text-body-sm font-normal text-neutral-600">(you)</span>@endif
                </th>
                <td>{{ $user->email }}</td>
                <td><x-ui.badge :variant="$user->isAdmin() ? 'primary' : 'neutral'">{{ ucfirst($user->role) }}</x-ui.badge></td>
                <td>
                    @if ($user->is_active)
                        <x-ui.badge variant="outline" icon="check">Active</x-ui.badge>
                    @else
                        <x-ui.badge variant="outline" icon="minus-circle">Deactivated</x-ui.badge>
                    @endif
                </td>
                <td data-numeric>{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                <td>
                    <div class="flex flex-wrap gap-1">
                        <x-ui.button :href="route('admin.users.edit', $user)" variant="ghost" size="sm" icon="pencil-simple">Edit<span class="sr-only"> {{ $user->name }}</span></x-ui.button>
                        @if ($user->is_active)
                            @can('deactivate', $user)
                                <form method="POST" action="{{ route('admin.users.deactivate', $user) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="ghost" size="sm" icon="user-minus">Deactivate<span class="sr-only"> {{ $user->name }}</span></x-ui.button>
                                </form>
                            @endcan
                        @else
                            <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                                @csrf
                                <x-ui.button type="submit" variant="ghost" size="sm" icon="check">Activate<span class="sr-only"> {{ $user->name }}</span></x-ui.button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-ui.table>
</x-layouts::app>
