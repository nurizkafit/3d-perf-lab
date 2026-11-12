<x-layouts::app :title="$definition['label']" description="Values that appear in dropdowns. An item in use cannot be deleted, only deactivated. Tests keep a copy of preset values, so edits never change old results." :sample="false">
    <x-slot:actions>
        @if (in_array($list, \App\Support\OptionLists::BULK_LISTS, true))
            <x-ui.button :href="route('admin.options.bulk.create', $list)" variant="secondary" icon="list-plus">Add several</x-ui.button>
        @endif
        <x-ui.button :href="route('admin.options.create', $list)" icon="plus">Add {{ $definition['singular'] }}</x-ui.button>
    </x-slot:actions>

    @if ($items->isEmpty())
        <x-ui.empty-state icon="sliders-horizontal" :message="'No '.$definition['label'].' yet.'">
            <x-ui.button :href="route('admin.options.create', $list)" icon="plus">Add {{ $definition['singular'] }}</x-ui.button>
        </x-ui.empty-state>
    @else
        <x-ui.table :caption="$definition['label']">
            <x-slot:head>
                <tr>
                    <th scope="col">Order</th>
                    <th scope="col">Name</th>
                    <th scope="col">Values</th>
                    <th scope="col">Status</th>
                    @if ($definition['default'])<th scope="col">Default</th>@endif
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </x-slot:head>
            @foreach ($items as $index => ['model' => $item, 'summary' => $summary, 'used' => $used])
                <tr>
                    <td>
                        <span class="flex gap-1">
                            @foreach (['up' => 'arrow-up', 'down' => 'arrow-down'] as $direction => $icon)
                                <form method="POST" action="{{ route('admin.options.reorder', [$list, $item->id]) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="{{ $direction }}">
                                    <x-ui.button type="submit" variant="ghost" size="icon" :aria-label="'Move '.$item->name.' '.$direction"
                                        :disabled="($direction === 'up' && $index === 0) || ($direction === 'down' && $index === $items->count() - 1)"><x-ui.icon :name="$icon" /></x-ui.button>
                                </form>
                            @endforeach
                        </span>
                    </td>
                    <th scope="row" class="font-semibold">{{ $item->name }}</th>
                    <td class="text-neutral-600">{{ $summary }}</td>
                    <td>
                        @if ($item->is_active)
                            <x-ui.badge variant="outline" icon="check">Active</x-ui.badge>
                        @else
                            <x-ui.badge variant="outline" icon="minus-circle">Inactive</x-ui.badge>
                        @endif
                        @if ($used)<x-ui.badge variant="neutral" class="ms-1">In use</x-ui.badge>@endif
                    </td>
                    @if ($definition['default'])
                        <td>
                            @if ($item->is_default)
                                <x-ui.badge variant="primary" icon="check-circle">Default</x-ui.badge>
                            @elseif ($item->is_active)
                                <form method="POST" action="{{ route('admin.options.default', [$list, $item->id]) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="ghost" size="sm">Make default<span class="sr-only"> {{ $item->name }}</span></x-ui.button>
                                </form>
                            @endif
                        </td>
                    @endif
                    <td>
                        <span class="flex flex-wrap gap-1">
                            <x-ui.button :href="route('admin.options.edit', [$list, $item->id])" variant="ghost" size="sm" icon="pencil-simple">Edit<span class="sr-only"> {{ $item->name }}</span></x-ui.button>
                            @if ($item->is_active)
                                <form method="POST" action="{{ route('admin.options.deactivate', [$list, $item->id]) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="ghost" size="sm" icon="eye-slash">Deactivate<span class="sr-only"> {{ $item->name }}</span></x-ui.button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.options.activate', [$list, $item->id]) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="ghost" size="sm" icon="check">Activate<span class="sr-only"> {{ $item->name }}</span></x-ui.button>
                                </form>
                            @endif
                            @unless ($used)
                                <form method="POST" action="{{ route('admin.options.destroy', [$list, $item->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" size="sm" icon="trash">Delete<span class="sr-only"> {{ $item->name }}</span></x-ui.button>
                                </form>
                            @endunless
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>
    @endif
</x-layouts::app>
