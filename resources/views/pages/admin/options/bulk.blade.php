<x-layouts::app :title="'Add several '.$definition['label']" :description="$definition['label']" :sample="false">
    <form method="POST" action="{{ route('admin.options.bulk.store', $list) }}" class="card max-w-4xl space-y-5">
        @csrf

        <div class="max-w-md">
            <label for="names" class="field-label">Names, one per line</label>
            <textarea id="names" name="names" rows="12" class="input" required aria-describedby="names-help @error('names') names-error @enderror" @error('names') aria-invalid="true" @enderror>{{ old('names') }}</textarea>
            <p id="names-help" class="text-neutral-600">Blank lines are ignored. A name that already exists is skipped and reported.</p>
            @error('names')<p id="names-error" class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit" icon="floppy-disk">Add all</x-ui.button>
            <x-ui.button :href="route('admin.options.index', $list)" variant="outline">Cancel</x-ui.button>
        </div>
    </form>
</x-layouts::app>
