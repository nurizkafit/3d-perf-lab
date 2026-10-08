@php
    $methods = \App\Support\MetricDefinitions::METHODS;
    $run = $test->runs->sortByDesc('id')->first();
@endphp
<x-layouts::app :title="$test->name" :description="($methods[$test->method]['label'] ?? $test->method).' · '.($run?->deviceClass?->name ?? 'No run yet').' · '.($test->target_json['name'] ?? '')" :sample="false">
    <x-ui.empty-state icon="chart-line" message="The results view for this method arrives with the Side-by-Side (M4.3) and Crowd (M4.4) milestones." />

    <section class="space-y-3" aria-labelledby="tested">
        <x-ui.section-label id="tested">Tested assets</x-ui.section-label>
        <x-ui.table caption="Tested assets in selection order">
            <x-slot:head><tr><th scope="col">Asset</th><th scope="col">Tool</th><th scope="col">Object</th></tr></x-slot:head>
            @foreach ($test->assets as $asset)
                <tr><th scope="row">{{ $asset->name }}</th><td>{{ $asset->tool->name }}</td><td>{{ $asset->object?->name ?? '—' }}</td></tr>
            @endforeach
        </x-ui.table>
    </section>
</x-layouts::app>
