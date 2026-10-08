<x-layouts::app title="Dashboard" description="Your device, recent activity, and quick actions.">
    <x-slot:actions>
        <x-ui.button :href="route('assets.upload')" icon="upload-simple">Upload assets</x-ui.button>
    </x-slot:actions>

    <x-ui.card class="flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-1">
            <p class="section-label">This device</p>
            <p class="font-semibold">Detected: Laptop or desktop · Integrated GPU (suggestion)</p>
            <p class="text-body-sm text-neutral-600">Confirm the device class before the first test on this device.</p>
        </div>
        <x-ui.button :href="route('device.show')" variant="secondary" icon="device-mobile">Confirm device class</x-ui.button>
    </x-ui.card>

    <section aria-labelledby="counts" class="space-y-3">
        <x-ui.section-label id="counts">Overview</x-ui.section-label>
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
            <x-performance.metric-card label="Registered assets" :value="count($assets)" />
            <x-performance.metric-card label="Tests" :value="count($tests)" />
            <x-performance.metric-card label="Published reports" value="4" />
        </div>
    </section>

    <section aria-labelledby="quick" class="space-y-3">
        <x-ui.section-label id="quick">Quick actions</x-ui.section-label>
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,14rem),1fr))]">
            <x-ui.card :href="route('assets.upload')" size="sm" class="flex items-center gap-3"><x-ui.icon name="upload-simple" size="md" /><span class="font-semibold">Upload assets</span></x-ui.card>
            <x-ui.card :href="route('lab.index')" size="sm" class="flex items-center gap-3"><x-ui.icon name="flask" size="md" /><span class="font-semibold">Performance Lab</span></x-ui.card>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section aria-labelledby="recent-tests" class="min-w-0 space-y-3">
            <x-ui.section-label id="recent-tests">Recent tests</x-ui.section-label>
            <x-ui.table caption="Recent tests">
                <x-slot:head><tr><th scope="col">Test</th><th scope="col">Method</th><th scope="col">Device</th></tr></x-slot:head>
                @foreach ($tests as $test)
                    <tr>
                        <th scope="row"><a class="link" href="{{ route('results.show', $test['id']) }}">{{ $test['name'] }}</a></th>
                        <td>{{ \App\Support\SampleReports::METHODS[$test['method']]['label'] }}</td>
                        <td>{{ $test['device'] }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
        </section>
        <section aria-labelledby="recent-uploads" class="min-w-0 space-y-3">
            <x-ui.section-label id="recent-uploads">Recent uploads</x-ui.section-label>
            <x-ui.table caption="Recent uploads">
                <x-slot:head><tr><th scope="col">Asset</th><th scope="col">Tool</th><th scope="col">Uploaded</th></tr></x-slot:head>
                @foreach ($assets as $asset)
                    <tr>
                        <th scope="row"><a class="link" href="{{ route('assets.show', $asset['id']) }}">{{ $asset['name'] }}</a></th>
                        <td>{{ $asset['tool'] }}</td>
                        <td class="num">{{ $asset['uploaded'] }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
        </section>
    </div>
</x-layouts::app>
