@php($methods = \App\Support\MetricDefinitions::METHODS)
<x-layouts::app title="Dashboard" description="Your device, recent activity, and quick actions." :sample="false">
    <x-slot:actions>
        <x-ui.button :href="route('assets.upload')" icon="upload-simple">Upload assets</x-ui.button>
    </x-slot:actions>

    <x-ui.card class="flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-1">
            <p class="section-label">This device</p>
            @if ($deviceClass)
                <p class="font-semibold">Confirmed: {{ $deviceClass->name }}</p>
                <p class="text-body-sm text-neutral-600">Tests on this device record this class. Change it if this is a different device.</p>
            @else
                <p class="font-semibold">Device class not confirmed</p>
                <p class="text-body-sm text-neutral-600">Confirm the device class before the first test on this device. The page suggests one from the browser signals.</p>
            @endif
        </div>
        <x-ui.button :href="route('device.show')" variant="secondary" icon="device-mobile">{{ $deviceClass ? 'Change device class' : 'Confirm device class' }}</x-ui.button>
    </x-ui.card>

    <section aria-labelledby="counts" class="space-y-3">
        <x-ui.section-label id="counts">Overview</x-ui.section-label>
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
            <x-performance.metric-card label="Registered assets" :value="number_format($counts['assets'])" />
            <x-performance.metric-card :label="auth()->user()->isAdmin() ? 'Tests (all users)' : 'Your tests'" :value="number_format($counts['tests'])" />
            <x-performance.metric-card label="Published reports" :value="number_format($counts['reports'])" />
        </div>
    </section>

    <section aria-labelledby="quick" class="space-y-3">
        <x-ui.section-label id="quick">Quick actions</x-ui.section-label>
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,14rem),1fr))]">
            <x-ui.card :href="route('assets.upload')" size="sm" class="flex items-center gap-3"><x-ui.icon name="upload-simple" size="md" /><span class="font-semibold">Upload assets</span></x-ui.card>
            <x-ui.card :href="route('lab.side-by-side')" size="sm" class="flex items-center gap-3"><x-ui.icon name="columns" size="md" /><span class="font-semibold">New Side-by-Side Test</span></x-ui.card>
            <x-ui.card :href="route('lab.crowd')" size="sm" class="flex items-center gap-3"><x-ui.icon name="users-three" size="md" /><span class="font-semibold">New Crowd Test</span></x-ui.card>
            <x-ui.card :href="route('results.tools')" size="sm" class="flex items-center gap-3"><x-ui.icon name="scales" size="md" /><span class="font-semibold">Tool summary</span></x-ui.card>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section aria-labelledby="recent-tests" class="min-w-0 space-y-3">
            <x-ui.section-label id="recent-tests">Recent tests</x-ui.section-label>
            @if ($tests->isEmpty())
                <x-ui.empty-state icon="flask" message="No tests yet.">
                    <x-ui.button :href="route('lab.index')" icon="flask">Open the Performance Lab</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.table caption="Recent tests">
                    <x-slot:head><tr><th scope="col">Test</th><th scope="col">Method</th><th scope="col">Device</th><th scope="col">Result</th></tr></x-slot:head>
                    @foreach ($tests as $test)
                        <tr>
                            <th scope="row"><a class="link" href="{{ route('results.show', $test) }}">{{ $test->name }}</a></th>
                            <td>{{ $methods[$test->method]['label'] }}</td>
                            <td>{{ $test->runs->first()?->deviceClass?->name ?? '—' }}</td>
                            <td class="cell-wrap">{{ $summaries[$test->id] }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </section>
        <section aria-labelledby="recent-uploads" class="min-w-0 space-y-3">
            <x-ui.section-label id="recent-uploads">Recent uploads</x-ui.section-label>
            @if ($uploads->isEmpty())
                <x-ui.empty-state icon="cube" message="No assets yet.">
                    <x-ui.button :href="route('assets.upload')" icon="upload-simple">Upload assets</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.table caption="Recent uploads">
                    <x-slot:head><tr><th scope="col">Asset</th><th scope="col">Tool</th><th scope="col">Object</th><th scope="col" data-numeric>Registered</th></tr></x-slot:head>
                    @foreach ($uploads as $asset)
                        <tr>
                            <th scope="row"><a class="link" href="{{ route('assets.show', $asset) }}">{{ $asset->name }}</a></th>
                            <td>{{ $asset->tool->name }}</td>
                            <td>{{ $asset->object?->name ?? '—' }}</td>
                            <td data-numeric>{{ $asset->created_at->display()->format('Y-m-d') }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </section>
    </div>
</x-layouts::app>
