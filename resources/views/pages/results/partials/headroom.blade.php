@php
    use App\Support\TestResultPresenter as P;
    $groups = $result->headroomGroups();
    $cap = $test->configuration_json['cap'] ?? null;
@endphp
<section class="space-y-3" aria-labelledby="headroom">
    <x-ui.section-label id="headroom">Headroom (median of repetitions, min–max)</x-ui.section-label>
    <x-ui.table caption="Headroom per search">
        <x-slot:head>
            <tr><th scope="col">Asset</th><th scope="col" data-numeric>Headroom (instances)</th><th scope="col">Per repetition</th><th scope="col">Stopped by</th></tr>
        </x-slot:head>
        @foreach ($groups as $group)
            <tr>
                <th scope="row" class="cell-wrap font-semibold">{{ $group['label'] }}</th>
                <td data-numeric class="font-semibold">{{ $group['display'] }}</td>
                <td class="num">{{ collect($group['repetitions'])->map(fn ($r) => $r['headroom'] === null ? '—' : ($r['cap_reached'] ? '≥ '.number_format($r['headroom']) : number_format($r['headroom'])))->join(' · ') }}</td>
                <td>
                    @if ($group['cap_reached'])<x-ui.badge variant="info" icon="info">Cap reached ({{ number_format($cap) }})</x-ui.badge>@endif
                    @if ($group['time_limit_reached'])<x-ui.badge status="warning">Time limit (3 min)</x-ui.badge>@endif
                    @unless ($group['cap_reached'] || $group['time_limit_reached'])<span class="text-body-sm text-neutral-600">Budget failed</span>@endunless
                </td>
            </tr>
        @endforeach
    </x-ui.table>
    <p class="text-body-sm text-neutral-600">A probe passes when its p95 frame interval and jank meet the budget. Headroom is the largest instance count that passed. "≥ cap" means the cap passed and the search stopped there. Assets are shown in selection order, not sorted by result.</p>
</section>

<section class="space-y-3" aria-labelledby="budget">
    <x-ui.section-label id="budget">Budget result</x-ui.section-label>
    <x-performance.budget-table :items="$budgetItems" :assets="$result->budgetColumns()" :budget="$result->budget()" :device-class="$run->deviceClass->name" />
</section>

<section class="space-y-3" aria-labelledby="headroom-charts" data-results-charts>
    <script type="application/json" id="results-charts">@json(['headroom' => $result->headroomChart()])</script>
    <x-ui.section-label id="headroom-charts">Charts</x-ui.section-label>
    <figure class="card space-y-2" data-size="sm">
        <figcaption class="text-label font-semibold">Headroom per asset (median; selection order)</figcaption>
        <div data-chart="headroom-bars" role="img" aria-label="Bar chart of the headroom per asset; values are printed on the bars and listed in the table above"></div>
    </figure>
    <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,24rem),1fr))]">
        @foreach ($groups as $index => $group)
            <figure class="card space-y-2" data-size="sm">
                <figcaption class="text-label font-semibold">{{ $group['label'] }} · probe sequence</figcaption>
                <div data-chart="probes" data-index="{{ $index }}" role="img" aria-label="Probe sequence for {{ $group['label'] }}: instance count per probe, passes as filled circles and fails as crosses"></div>
                <details class="text-body-sm">
                    <summary class="link w-fit cursor-pointer">Probe list</summary>
                    @foreach ($group['repetitions'] as $repetition)
                        <p class="pt-2 font-semibold">Repetition {{ $repetition['repetition'] }}</p>
                        <ol class="flex flex-wrap gap-1 pt-1">
                            @foreach ($repetition['probes'] as $probe)
                                <li><x-ui.badge :status="$probe['pass'] ? 'pass' : 'fail'">{{ $probe['pass'] ? 'Pass' : 'Fail' }} {{ number_format($probe['instances']) }}</x-ui.badge></li>
                            @endforeach
                        </ol>
                    @endforeach
                </details>
            </figure>
        @endforeach
    </div>
</section>
