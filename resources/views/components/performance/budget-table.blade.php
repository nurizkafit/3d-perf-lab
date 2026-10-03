@props(['items', 'assets', 'budget', 'deviceClass'])
{{-- Rows = budget items, columns = assets in selection order, cells = outcome badge + measured value + limit (DESIGN.md §9.4).
     Each column names the asset with its tool, so the result never loses which model was tested. --}}
<x-ui.table caption="Budget result for {{ $deviceClass }}">
    <x-slot:head>
        <tr>
            <th scope="col">Budget item</th>
            <th scope="col" data-numeric>Limit</th>
            @foreach ($assets as $asset)
                <th scope="col" class="align-bottom">
                    <span class="block text-body-sm font-semibold text-neutral-950">{{ $asset['name'] }}</span>
                    <span class="block font-normal">{{ $asset['tool'] }} · {{ $asset['format'] ?? 'GLB' }}</span>
                    <span class="block font-normal">{{ $asset['author'] }}</span>
                </th>
            @endforeach
        </tr>
    </x-slot:head>
    @foreach ($budget as $key => $row)
        @php
            $item = $items[$key];
            $limit = $row[0];
            $cells = array_slice($row, 1);
        @endphp
        <tr>
            <th scope="row" class="cell-wrap">
                <span class="block font-semibold">{{ $item['label'] }}@if ($item['unit']) ({{ $item['unit'] }})@endif</span>
                <span class="block max-w-72 text-body-sm font-normal text-neutral-600">{{ $item['help'] }}</span>
            </th>
            <td data-numeric>{{ $item['rule'] }} {{ $limit }}</td>
            @foreach ($cells as [$outcome, $measured])
                <td>
                    <span class="flex items-center gap-2">
                        <x-ui.badge :status="$outcome" />
                        <span class="num">{{ $measured ?? '—' }}</span>
                    </span>
                </td>
            @endforeach
        </tr>
    @endforeach
</x-ui.table>
