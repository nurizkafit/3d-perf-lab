@props(['assets'])
{{-- Which assets a test used, with their metadata and file metrics, in selection order. --}}
<x-ui.table caption="Tested assets">
    <x-slot:head>
        <tr>
            <th scope="col" data-numeric>#</th>
            <th scope="col">Asset</th>
            <th scope="col">Tool</th>
            <th scope="col">Object</th>
            <th scope="col">Author</th>
            <th scope="col">Format</th>
            <th scope="col" data-numeric>File size</th>
            <th scope="col" data-numeric>Vertices</th>
            <th scope="col" data-numeric>Triangles</th>
            <th scope="col" data-numeric>Production time (min)</th>
            <th scope="col">Uploaded by</th>
        </tr>
    </x-slot:head>
    @foreach ($assets as $i => $asset)
        <tr>
            <td data-numeric class="text-neutral-600">{{ $i + 1 }}</td>
            <th scope="row"><a class="link flex items-center gap-3" href="{{ route('assets.show', $asset['id']) }}"><span class="thumb"><x-ui.icon name="cube" /></span>{{ $asset['name'] }}</a></th>
            <td>{{ $asset['tool'] }}</td>
            <td>{{ $asset['object'] ?? '—' }}</td>
            <td>{{ $asset['author'] }}</td>
            <td><x-ui.badge>{{ $asset['format'] }}</x-ui.badge></td>
            <td data-numeric>{{ $asset['size'] }}</td>
            <td data-numeric>{{ number_format($asset['vertices']) }}</td>
            <td data-numeric>{{ number_format($asset['triangles']) }}</td>
            <td data-numeric>{{ $asset['minutes'] }}</td>
            <td>{{ $asset['uploaded_by'] }}</td>
        </tr>
    @endforeach
</x-ui.table>
