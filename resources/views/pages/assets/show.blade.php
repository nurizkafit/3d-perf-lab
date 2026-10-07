@php
    $methods = \App\Support\SampleReports::METHODS;
    $fileUrl = route('assets.file', [$asset, basename($asset->web_file_path)]);
@endphp
<x-layouts::app :title="$asset->name" :description="$asset->tool->name.' · '.$asset->author" :sample="false">
    <x-slot:actions>
        <x-ui.button :href="route('assets.download', $asset)" variant="secondary" icon="download-simple">Download {{ $asset->format === 'gltf' ? '(ZIP)' : '' }}</x-ui.button>
        @if (! $asset->trashed())
            @can('update', $asset)
                <x-ui.button :href="route('assets.edit', $asset)" variant="secondary" icon="pencil-simple">Edit</x-ui.button>
            @endcan
            @can('delete', $asset)
                <x-ui.button variant="destructive" icon="trash" data-dialog-open="delete-asset">Delete</x-ui.button>
            @endcan
        @else
            @can('restore', $asset)
                <form method="POST" action="{{ route('assets.restore', $asset) }}">
                    @csrf
                    <x-ui.button type="submit" icon="arrow-counter-clockwise">Restore</x-ui.button>
                </form>
            @endcan
            @can('forceDelete', $asset)
                <x-ui.button variant="destructive" icon="trash" data-dialog-open="force-delete-asset">Delete permanently</x-ui.button>
            @endcan
        @endif
    </x-slot:actions>

    @if ($asset->trashed())
        <p class="alert-box" data-error role="status"><x-ui.icon name="trash" />Deleted on {{ $asset->deleted_at->display()->format('Y-m-d H:i') }}. It is hidden from the library and cannot be selected for new tests. Old results keep it.</p>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <x-performance.viewer mode="preview" :src="$fileUrl" :config="$viewerConfig" />

        <section class="card space-y-4" aria-labelledby="meta">
            <x-ui.section-label id="meta">Metadata</x-ui.section-label>
            <dl class="grid grid-cols-[minmax(0,auto)_1fr] gap-x-4 gap-y-2 text-body-sm">
                <dt class="text-neutral-600">Name</dt><dd>{{ $asset->name }}</dd>
                <dt class="text-neutral-600">Tool</dt><dd>{{ $asset->tool->name }}</dd>
                <dt class="text-neutral-600">Author</dt><dd>{{ $asset->author }}</dd>
                <dt class="text-neutral-600">Uploaded by</dt><dd>{{ $asset->uploader->name }}</dd>
                <dt class="text-neutral-600">Production time</dt><dd class="num">{{ rtrim(rtrim($asset->production_time_hours, '0'), '.') }} h</dd>
                <dt class="text-neutral-600">Uploaded</dt><dd class="num">{{ $asset->created_at->display()->format('Y-m-d H:i') }}</dd>
            </dl>
            @if ($asset->description)
                <p class="text-body-sm">{{ $asset->description }}</p>
            @else
                <p class="text-body-sm text-neutral-600">No description.</p>
            @endif
        </section>
    </div>

    {{-- Asset metrics and runtime metrics stay in separate sections (PRD.md §7.3). --}}
    <section class="space-y-3" aria-labelledby="asset-metrics">
        <x-ui.section-label id="asset-metrics">Asset metrics (from the file)</x-ui.section-label>
        <x-assets.asset-metrics :asset="$asset" />
    </section>

    <section class="space-y-3" aria-labelledby="used-in">
        <x-ui.section-label id="used-in">Tests that used this asset</x-ui.section-label>
        @if ($tests->isEmpty())
            <p class="text-body-sm text-neutral-600">{{ $otherTests > 0 ? "Used in {$otherTests} ".Str::plural('test', $otherTests)." by other users." : 'Not used in any test yet. Runtime metrics appear in test results.' }}</p>
        @else
            <x-ui.table caption="Tests that used this asset">
                <x-slot:head><tr><th scope="col">Test</th><th scope="col">Method</th><th scope="col" data-numeric>Date</th></tr></x-slot:head>
                @foreach ($tests as $test)
                    <tr><th scope="row" class="font-semibold">{{ $test->name }}</th><td>{{ $methods[$test->method]['label'] }}</td><td data-numeric>{{ $test->created_at->display()->format('Y-m-d') }}</td></tr>
                @endforeach
            </x-ui.table>
            @if ($otherTests > 0)<p class="text-body-sm text-neutral-600">Also used in {{ $otherTests }} {{ Str::plural('test', $otherTests) }} by other users.</p>@endif
        @endif
    </section>

    @can('delete', $asset)
        <x-ui.dialog id="delete-asset" title="Delete {{ $asset->name }}?" description="The asset is hidden from the library and from new tests. Old results keep it. You can restore it later.">
            <x-slot:footer>
                <form method="POST" action="{{ route('assets.destroy', $asset) }}" class="flex justify-end gap-2">
                    @csrf
                    @method('DELETE')
                    <x-ui.button variant="outline" type="button" data-dialog-close>Cancel</x-ui.button>
                    <x-ui.button variant="destructive" type="submit" icon="trash">Delete</x-ui.button>
                </form>
            </x-slot:footer>
        </x-ui.dialog>
    @endcan
    @can('forceDelete', $asset)
        <x-ui.dialog id="force-delete-asset" title="Delete {{ $asset->name }} permanently?" description="This removes the asset record and its files. It cannot be undone. Assets used by a test cannot be deleted permanently.">
            <x-slot:footer>
                <form method="POST" action="{{ route('assets.force-destroy', $asset) }}" class="flex justify-end gap-2">
                    @csrf
                    @method('DELETE')
                    <x-ui.button variant="outline" type="button" data-dialog-close>Cancel</x-ui.button>
                    <x-ui.button variant="destructive" type="submit" icon="trash">Delete permanently</x-ui.button>
                </form>
            </x-slot:footer>
        </x-ui.dialog>
    @endcan
</x-layouts::app>
