@php($methods = \App\Support\SampleReports::METHODS)
<x-layouts::app title="Test history" description="Your tests. Admins see all tests.">
    <form method="GET" role="search" class="card grid items-end gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]" data-size="sm">
        <x-ui.select name="method" label="Method" :options="collect($methods)->map(fn ($m) => $m['label'])->all()" placeholder="All methods" />
        <x-ui.select name="device" label="Device class" :options="array_combine(array_column(\Database\Seeders\DeviceClassSeeder::CLASSES, 0), array_column(\Database\Seeders\DeviceClassSeeder::CLASSES, 0))" placeholder="All device classes" />
        <x-ui.input name="from" label="From" type="date" />
        <x-ui.input name="to" label="To" type="date" />
        <x-ui.button type="submit" icon="funnel-simple">Apply</x-ui.button>
    </form>

    <x-ui.table caption="Test history">
        <x-slot:head>
            <tr><th scope="col">Test</th><th scope="col">Method</th><th scope="col" data-numeric>Date</th><th scope="col" data-numeric>Assets</th><th scope="col">Device class</th><th scope="col">Budget preset</th><th scope="col">Status</th><th scope="col">Budget summary</th></tr>
        </x-slot:head>
        @foreach ($tests as $test)
            <tr>
                <th scope="row"><a class="link" href="{{ route('results.show', $test['id']) }}">{{ $test['name'] }}</a></th>
                <td>{{ $methods[$test['method']]['label'] }}</td>
                <td data-numeric>{{ $test['date'] }}</td>
                <td data-numeric>{{ $test['assets'] }}</td>
                <td>{{ $test['device'] }}</td>
                <td>{{ $test['budget'] }}</td>
                <td>
                    @if ($test['status'] === 'completed')
                        <x-ui.badge variant="outline" icon="check">Completed</x-ui.badge>
                    @else
                        <x-ui.badge variant="warning" icon="warning">Invalid</x-ui.badge>
                    @endif
                </td>
                <td>{{ $test['summary'] }}</td>
            </tr>
        @endforeach
    </x-ui.table>
</x-layouts::app>
