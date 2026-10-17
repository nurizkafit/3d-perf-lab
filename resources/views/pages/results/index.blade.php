@php($methods = \App\Support\MetricDefinitions::METHODS)
<x-layouts::app title="Test history" :description="auth()->user()->isAdmin() ? 'All tests of all users.' : 'Your tests.'">
    {{-- Filters in the query string: work without JavaScript. Dates use the display zone. --}}
    <form method="GET" action="{{ route('results.index') }}" role="search" class="card grid items-end gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]" data-size="sm">
        <x-ui.select name="method" label="Method" :options="collect($methods)->map(fn ($m) => $m['label'])->all()" :value="$filters['method']" placeholder="All methods" />
        <x-ui.select name="device" label="Device class" :options="$classes->all()" :value="$filters['device']" placeholder="All device classes" />
        <x-ui.input name="from" label="From" type="date" :value="$filters['from']" />
        <x-ui.input name="to" label="To" type="date" :value="$filters['to']" />
        <div class="flex gap-2">
            <x-ui.button type="submit" icon="funnel-simple">Apply</x-ui.button>
            @if (array_filter($filters))<x-ui.button :href="route('results.index')" variant="ghost">Clear</x-ui.button>@endif
        </div>
    </form>

    @if ($tests->isEmpty())
        <x-ui.empty-state icon="chart-line" :message="array_filter($filters) ? 'No test matches these filters.' : 'No tests yet.'">
            <x-ui.button :href="route('lab.index')" icon="flask">Open the Performance Lab</x-ui.button>
        </x-ui.empty-state>
    @else
        <x-ui.table caption="Test history">
            <x-slot:head>
                <tr><th scope="col">Test</th><th scope="col">Method</th><th scope="col" data-numeric>Date</th><th scope="col" data-numeric>Assets</th><th scope="col">Device class</th><th scope="col">Target</th><th scope="col">Status</th><th scope="col">Result</th><th scope="col">Report</th></tr>
            </x-slot:head>
            @foreach ($tests as $test)
                @php($run = $test->runs->first())
                <tr>
                    <th scope="row" class="cell-wrap"><a class="link" href="{{ route('results.show', $test) }}">{{ $test->name }}</a>
                        @if (auth()->user()->isAdmin() && $test->creator)<span class="block text-body-sm font-normal text-neutral-600">{{ $test->creator->name }}</span>@endif</th>
                    <td>{{ $methods[$test->method]['label'] }}@if ($test->scenario)<span class="block text-body-sm text-neutral-600">{{ $test->scenario === 'per_tool' ? 'Per tool' : 'Per asset' }}</span>@endif</td>
                    <td data-numeric>{{ $test->created_at->display()->format('Y-m-d H:i') }}</td>
                    <td data-numeric>{{ $test->assets_count }}</td>
                    <td>{{ $run?->deviceClass?->name ?? '—' }}</td>
                    <td>{{ $test->target_json['name'] ?? '—' }}</td>
                    <td>
                        @switch($run?->status)
                            @case('completed')<x-ui.badge variant="outline" icon="check">Completed</x-ui.badge>@break
                            @case('running')<x-ui.badge variant="outline" icon="circle-notch">Running</x-ui.badge>@break
                            @case('invalid')<x-ui.badge variant="warning" icon="warning" :title="$run->invalid_reason">Invalid</x-ui.badge>@break
                            @case('cancelled')<x-ui.badge variant="outline" icon="x">Cancelled</x-ui.badge>@break
                            @default<span class="text-neutral-600">Not run</span>
                        @endswitch
                    </td>
                    <td class="cell-wrap">{{ $summaries[$test->id] }}</td>
                    <td>@if ($test->report?->isPublished())<x-ui.badge variant="primary" icon="globe-simple">Published</x-ui.badge>@elseif ($test->report)<x-ui.badge variant="outline">Draft</x-ui.badge>@else<span class="text-neutral-600">—</span>@endif</td>
                </tr>
            @endforeach
        </x-ui.table>
        @if ($tests->hasPages())
            <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Pages">
                <x-ui.button :href="$tests->previousPageUrl()" variant="secondary" :disabled="$tests->onFirstPage()" icon="arrow-left">Previous</x-ui.button>
                <p class="text-body-sm text-neutral-600">Page {{ $tests->currentPage() }} of {{ $tests->lastPage() }}</p>
                <x-ui.button :href="$tests->nextPageUrl()" variant="secondary" :disabled="! $tests->hasMorePages()" icon="arrow-right">Next</x-ui.button>
            </nav>
        @endif
    @endif
</x-layouts::app>
