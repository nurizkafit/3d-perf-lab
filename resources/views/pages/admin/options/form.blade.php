<x-layouts::app :title="($item ? 'Edit ' : 'Add ').$definition['singular']" :description="$definition['label']" :sample="false">
    <form method="POST" action="{{ $item ? route('admin.options.update', [$list, $item->id]) : route('admin.options.store', $list) }}" class="card max-w-4xl space-y-5">
        @csrf
        @if ($item) @method('PUT') @endif

        <div class="max-w-md">
            <x-ui.input name="name" label="Name" :value="$values['name']" required />
        </div>

        <x-admin.option-form-fields :list="$list" :values="$values" :device-classes="$deviceClasses" />

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit" icon="floppy-disk">{{ $item ? 'Save' : 'Add' }}</x-ui.button>
            <x-ui.button :href="route('admin.options.index', $list)" variant="outline">Cancel</x-ui.button>
        </div>
    </form>
</x-layouts::app>
