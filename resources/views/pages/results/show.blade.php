@php
    $methods = \App\Support\MetricDefinitions::METHODS;
    $run = $results['run'] ?? $latest;
    $labRoute = $test->method === 'crowd' ? 'lab.crowd' : 'lab.side-by-side';
@endphp
<x-layouts::app :title="$test->name" :description="($methods[$test->method]['label'] ?? $test->method).' · '.($run?->deviceClass?->name ?? 'No run yet').' · Target: '.($test->target_json['name'] ?? '')" :sample="false">
    <x-slot:actions>
        <div class="flex flex-wrap gap-2">
            <x-ui.button :href="route('results.report', $test)" icon="article" variant="secondary">{{ $test->report ? ($test->report->isPublished() ? 'Report (published)' : 'Edit report') : 'Write report' }}</x-ui.button>
            <x-ui.button :href="route('results.export.json', $test)" icon="file-code" variant="outline">JSON</x-ui.button>
            <x-ui.button :href="route('results.export.csv', $test)" icon="file-csv" variant="outline">CSV</x-ui.button>
            <x-ui.button variant="destructive" icon="trash" data-dialog-open="delete-test" :disabled="(bool) $test->report?->isPublished()" :title="$test->report?->isPublished() ? 'Unpublish the report first.' : null">Delete</x-ui.button>
        </div>
    </x-slot:actions>
    @error('test')<p class="field-error" role="alert"><x-ui.icon name="warning-circle" />{{ $message }}</p>@enderror

    @if (! $results)
        @if ($latest && $latest->status !== 'running')
            <div class="card flex items-start gap-3" role="status">
                <x-ui.icon name="warning" size="md" />
                <p>The most recent run is <strong>{{ $latest->status }}</strong>@if ($latest->invalid_reason): {{ $latest->invalid_reason === 'expired' ? 'it expired before results were submitted.' : $latest->invalid_reason }}@endif. Invalid and cancelled runs produce no results.</p>
            </div>
        @endif
        <x-ui.empty-state icon="chart-line" message="This test has no completed run yet.">
            <x-ui.button :href="route($labRoute)" icon="flask">Open the {{ $methods[$test->method]['label'] ?? 'Lab' }}</x-ui.button>
        </x-ui.empty-state>
    @elseif ($test->method === 'crowd')
        @include('pages.results.partials.crowd', ['results' => $results])
    @else
        @include('pages.results.partials.side-by-side', ['results' => $results])
    @endif

    <x-ui.dialog id="delete-test" title="Delete {{ $test->name }}?" description="This removes the test, all its runs and results{{ $test->report ? ', and its draft report' : '' }}. It cannot be undone. The assets stay in the library.">
        <x-slot:footer>
            <form method="POST" action="{{ route('results.destroy', $test) }}" class="flex justify-end gap-2">
                @csrf
                @method('DELETE')
                <x-ui.button variant="outline" type="button" data-dialog-close>Cancel</x-ui.button>
                <x-ui.button variant="destructive" type="submit" icon="trash">Delete test</x-ui.button>
            </form>
        </x-slot:footer>
    </x-ui.dialog>
</x-layouts::app>
