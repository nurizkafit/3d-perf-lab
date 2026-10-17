@php
    use App\Support\MetricDefinitions as Defs;
    use Illuminate\Support\Number;

    $seconds = fn ($ms) => $ms === null ? 'Not tested' : number_format($ms / 1000, 2).' s';
    $size = fn ($bytes) => $bytes === null ? '—' : Number::fileSize($bytes, maxPrecision: 1);
    $copies = fn ($n) => $n === null ? 'Not tested' : number_format($n).' '.($n == 1 ? 'copy' : 'copies');
    $wins = fn ($marker, $id) => in_array($id, $marker['winners'], true);
    $checkName = fn ($c) => Defs::TARGET_CHECKS[$c['check_key']]['label'].($c['motion'] ? ' · '.Defs::MOTIONS[$c['motion']]['label'] : '');
    $link = fn ($source) => route('results.show', $source['test_id']);
@endphp
<x-layouts::app title="Tool summary" description="Compare authoring tools on the objects that every tool has, with the results that already exist on one device class. A summary of measured values, not a score." :sample="false">
    {{-- Filters in the query string: works without JavaScript and can be bookmarked. --}}
    <form method="GET" action="{{ route('results.tools') }}" class="card space-y-4">
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,16rem),1fr))]">
            <div>
                <label for="device_class" class="field-label">Device class</label>
                <select id="device_class" name="device_class" class="select">
                    <option value="">Select a device class</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected($class->id === $deviceClassId)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <fieldset class="space-y-2">
                <legend class="field-label">Authoring tools (two or more)</legend>
                <div class="flex flex-wrap gap-4">
                    @foreach ($tools as $id => $name)
                        <label class="flex items-center gap-2 text-body-sm"><input type="checkbox" class="input" name="tools[]" value="{{ $id }}" @checked(in_array($id, $toolIds, true))> {{ $name }}</label>
                    @endforeach
                </div>
            </fieldset>
        </div>
        @if ($summary && count($summary['objects']) + count($summary['excluded']))
            <fieldset class="space-y-2">
                <legend class="field-label">Objects in every selected tool</legend>
                <p class="field-hint">Untick an object to leave it out.</p>
                <div class="flex flex-wrap gap-4">
                    @foreach (collect($summary['objects'])->concat($summary['excluded'])->sortBy('name') as $object)
                        {{-- Unticked boxes are sent as exclude[]: a hidden list keeps the others included. --}}
                        <label class="flex items-center gap-2 text-body-sm">
                            <input type="checkbox" class="input" name="exclude[]" value="{{ $object['id'] }}" @checked(in_array($object['id'], $excluded, true))> Leave out {{ $object['name'] }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif
        <x-ui.button type="submit" icon="funnel-simple">Show summary</x-ui.button>
    </form>

    @if (! $summary)
        <x-ui.empty-state icon="scales" message="Select a device class and at least two authoring tools." />
    @else
        @php($tools = $summary['tools'])
        @if ($summary['missing'])
            <section class="space-y-1" aria-labelledby="missing-title">
                <x-ui.section-label id="missing-title" as="h2">Not compared: missing in a selected tool</x-ui.section-label>
                <ul class="list-disc space-y-1 ps-5 text-body-sm">
                    @foreach ($summary['missing'] as $object)<li><span class="font-semibold">{{ $object['name'] }}</span> — missing in {{ implode(', ', $object['missing_tools']) }}</li>@endforeach
                </ul>
            </section>
        @endif

        @if (! $summary['objects'])
            <x-ui.empty-state icon="shapes" message="No object exists in every selected tool, or all objects are left out." />
        @else
            <section class="space-y-3" aria-labelledby="summary-title">
                <x-ui.section-label id="summary-title" as="h2">Summary per tool</x-ui.section-label>
                <p class="text-body-sm text-neutral-600">Medians across the {{ count($summary['objects']) }} compared objects. Best markers apply per row and never re-sort the columns. Each tool's source tests are linked below the table and in the per-object table.</p>
                <x-ui.table caption="Summary per tool">
                    <x-slot:head><tr><th scope="col">Value</th>@foreach ($tools as $tool)<th scope="col" data-numeric>{{ $tool['name'] }}</th>@endforeach</tr></x-slot:head>
                    <tr><th scope="row">Objects compared</th>@foreach ($tools as $tool)<td data-numeric>{{ $tool['objects'] }}</td>@endforeach</tr>
                    <tr><th scope="row">File size (median)<span class="block text-body-sm font-normal text-neutral-600">{{ Defs::TARGET_FIELDS['max_file_size_mb']['help'] }}</span></th>
                        @foreach ($tools as $id => $tool)<td data-numeric>{{ $size($tool['file_size']) }} @if ($wins($summary['markers']['smallest_file'], $id))<x-ui.badge variant="outline" icon="trophy">Smallest file</x-ui.badge>@endif</td>@endforeach</tr>
                    <tr><th scope="row">Triangles (median)</th>@foreach ($tools as $tool)<td data-numeric>{{ $tool['triangles'] === null ? '—' : number_format($tool['triangles']) }}</td>@endforeach</tr>
                    <tr><th scope="row">Cold load (median)<span class="block text-body-sm font-normal text-neutral-600">{{ Defs::METRICS['ttff_cold_ms']['help'] }} From Side-by-Side tests.</span></th>
                        @foreach ($tools as $id => $tool)<td data-numeric>{{ $seconds($tool['cold_ms']) }}<span class="block text-neutral-600">{{ $tool['tested'] }} of {{ $tool['objects'] }} tested</span> @if ($wins($summary['markers']['fastest_load'], $id))<x-ui.badge variant="outline" icon="trophy">Fastest load</x-ui.badge>@endif</td>@endforeach</tr>
                    @php($checkKeys = collect($tools)->flatMap(fn ($t) => array_keys($t['checks']))->unique()->values())
                    @foreach ($checkKeys as $key)
                        @php($sample = collect($tools)->first(fn ($t) => isset($t['checks'][$key]))['checks'][$key])
                        <tr><th scope="row">{{ $checkName($sample) }}<span class="block text-body-sm font-normal text-neutral-600">Assets that pass this Target check, with the Target of each test.</span></th>
                            @foreach ($tools as $tool)
                                @php($c = $tool['checks'][$key] ?? null)
                                <td data-numeric>{{ $c ? $c['pass'].' of '.$c['total'].' pass' : 'Not tested' }}@if ($c)<span class="block text-neutral-600">{{ implode(', ', $c['targets']) }}</span>@endif</td>
                            @endforeach
                        </tr>
                    @endforeach
                    @foreach ($summary['motions'] as $motion)
                        <tr><th scope="row">Smooth copies · {{ Defs::MOTIONS[$motion]['label'] }} (median)<span class="block text-body-sm font-normal text-neutral-600">From Crowd Per asset tests.</span></th>
                            @foreach ($tools as $id => $tool)<td data-numeric>{{ $copies($tool['copies'][$motion]) }} @if ($wins($summary['markers']['most_copies'][$motion], $id))<x-ui.badge variant="outline" icon="trophy">Most copies</x-ui.badge>@endif</td>@endforeach</tr>
                    @endforeach
                </x-ui.table>
                <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,16rem),1fr))]">
                    @foreach ($tools as $tool)
                        <div class="card space-y-2" data-size="sm">
                            <h3 class="font-semibold">{{ $tool['name'] }}: source tests</h3>
                            @if ($tool['sources'])
                                <ul class="space-y-1 text-body-sm">@foreach ($tool['sources'] as $source)<li><a class="link" href="{{ $link($source) }}">{{ $source['name'] }}</a> <span class="text-neutral-600">· {{ Defs::METHODS[$source['method']]['label'] }}</span></li>@endforeach</ul>
                            @else
                                <p class="text-body-sm text-neutral-600">Not tested on this device class.</p>
                            @endif
                            <h4 class="pt-2 font-semibold">Tool crowd results</h4>
                            @if ($tool['tool_crowds'])
                                <ul class="space-y-1 text-body-sm">
                                    @foreach ($tool['tool_crowds'] as $crowd)
                                        <li><a class="link" href="{{ route('results.show', $crowd['test_id']) }}">{{ $crowd['name'] }}</a> <span class="text-neutral-600">· {{ $crowd['objects'] }} objects, {{ $crowd['tools'] }} tools ·
                                            {{ collect($crowd['copies'])->map(fn ($n, $m) => Defs::MOTIONS[$m]['label'].' '.$copies($n))->join(' · ') }}</span></li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-body-sm text-neutral-600">No Crowd Per tool test with this tool on this device class.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="space-y-3" aria-labelledby="objects-title">
                <x-ui.section-label id="objects-title" as="h2">Per object</x-ui.section-label>
                <x-ui.table caption="Values per object and tool, with links to the tests they came from">
                    <x-slot:head><tr><th scope="col">Object</th>@foreach ($tools as $tool)<th scope="col">{{ $tool['name'] }}</th>@endforeach</tr></x-slot:head>
                    @foreach ($summary['objects'] as $object)
                        <tr>
                            <th scope="row">{{ $object['name'] }}</th>
                            @foreach ($tools as $id => $tool)
                                @php($cell = $summary['cells'][$object['id']][$id])
                                <td class="cell-wrap">
                                    <a class="link font-semibold" href="{{ route('assets.show', $cell['asset']['id']) }}">{{ $cell['asset']['name'] }}</a> <span class="text-neutral-600">{{ $size($cell['file_size']) }}</span>
                                    @if ($cell['side_by_side'])
                                        <span class="block text-body-sm"><a class="link" href="{{ $link($cell['side_by_side']) }}">Cold load {{ $seconds($cell['cold_ms']) }}</a> ·
                                            {{ collect($cell['checks'])->where('check_key', 'smooth')->map(fn ($c) => Defs::MOTIONS[$c['motion']]['label'].' '.($c['label'] ? Defs::SMOOTHNESS[$c['label']]['label'] : 'Not measured'))->join(' · ') }}</span>
                                    @else
                                        <span class="block text-body-sm text-neutral-600">Side-by-Side: Not tested</span>
                                    @endif
                                    @if ($cell['crowd'])
                                        <span class="block text-body-sm"><a class="link" href="{{ $link($cell['crowd']) }}">{{ collect($cell['copies'])->map(fn ($n, $m) => Defs::MOTIONS[$m]['label'].' '.$copies($n))->join(' · ') }}</a></span>
                                    @else
                                        <span class="block text-body-sm text-neutral-600">Crowd: Not tested</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </x-ui.table>
            </section>
        @endif
    @endif
</x-layouts::app>
