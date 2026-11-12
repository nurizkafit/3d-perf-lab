@php
    use Illuminate\Support\Number;

    $methods = \App\Support\MetricDefinitions::METHODS;
    $published = $report->isPublished();
@endphp
<x-layouts::app title="Report editor" :description="'A report answers one question with evidence from “'.$test->name.'”. Reports are private until you publish.'" :sample="false">
    <x-slot:actions>
        <div class="flex flex-wrap items-center gap-2">
            @if ($published)
                <x-ui.badge variant="primary" icon="globe-simple">Published</x-ui.badge>
            @else
                <x-ui.badge variant="outline">{{ $report->exists ? 'Draft' : 'Not saved yet' }}</x-ui.badge>
            @endif
            <x-ui.button :href="route('results.show', $test)" variant="secondary" icon="chart-line">Test results</x-ui.button>
        </div>
    </x-slot:actions>

    <section class="space-y-3" aria-labelledby="tested">
        <x-ui.section-label id="tested">Tested assets</x-ui.section-label>
        <p class="text-body-sm text-neutral-600">{{ $methods[$test->method]['label'] }}{{ $test->scenario ? ' · '.($test->scenario === 'per_tool' ? 'Per tool' : 'Per asset') : '' }} · {{ $run?->deviceClass?->name ?? 'No completed run' }} · Target {{ $test->target_json['name'] ?? '' }}</p>
        <x-assets.tested-assets :assets="$test->assets->map(fn ($asset) => [
            'id' => $asset->id, 'name' => $asset->name, 'tool' => $asset->tool->name, 'object' => $asset->object?->name, 'author' => $asset->author,
            'format' => strtoupper($asset->format), 'size' => Number::fileSize($asset->file_size, maxPrecision: 1),
            'vertices' => $asset->metrics?->vertices ?? 0, 'triangles' => $asset->metrics?->triangles ?? 0,
            'minutes' => $asset->production_time_minutes, 'uploaded_by' => $asset->uploader?->name ?? '—',
        ])->values()->all()" />
    </section>

    <form method="POST" action="{{ route('results.report.update', $test) }}" class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        @csrf
        @method('PUT')
        <div class="card space-y-5">
            <x-ui.input name="title" label="Title" :value="$report->title" required maxlength="150" />
            <x-ui.textarea name="question" label="Question" :value="$report->question" hint="The one question this report answers. Required before publishing." maxlength="1000" />
            <div class="space-y-2">
                <p class="field-label">Standard limitations (added by the system)</p>
                <ul class="list-disc space-y-1 ps-5 text-body-sm text-neutral-600">
                    @foreach ($standardLimitations as $limitation)<li>{{ $limitation }}</li>@endforeach
                </ul>
            </div>
            <x-ui.textarea name="limitations" label="Additional limitations" :value="$report->limitations" hint="Optional. One limitation per line." maxlength="3000" />
            <x-ui.textarea name="conclusion" label="Conclusion" :value="$report->conclusion" hint="Optional. Written by you; the system never writes it." maxlength="5000" />
            <x-ui.button type="submit" icon="floppy-disk">Save draft</x-ui.button>
        </div>

        <section class="card space-y-3" aria-labelledby="approval">
            <x-ui.section-label id="approval">Public asset approval</x-ui.section-label>
            <p class="text-body-sm text-neutral-600">Approved assets can be downloaded by the public for the interactive reproduction. Default: not approved. An asset that is not approved shows its metrics and a placeholder. Save the draft to keep changes.</p>
            @error('approved.*')<p class="field-error" role="alert"><x-ui.icon name="warning-circle" />{{ $message }}</p>@enderror
            <fieldset class="space-y-2">
                <legend class="sr-only">Assets approved for public delivery</legend>
                @foreach ($test->assets as $asset)
                    <label class="flex items-center gap-3 rounded-md border border-neutral-200 p-3 has-[:checked]:border-neutral-800 has-[:checked]:bg-primary-tint">
                        <input type="checkbox" class="input" name="approved[]" value="{{ $asset->id }}" @checked(in_array($asset->id, old('approved', $approved)))>
                        <span class="min-w-0">
                            <span class="block font-semibold">{{ $asset->name }}</span>
                            <span class="block text-body-sm text-neutral-600">{{ $asset->tool->name }} · {{ $asset->author }} · {{ strtoupper($asset->format) }} · {{ Number::fileSize($asset->file_size, maxPrecision: 1) }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>
        </section>
    </form>

    <section class="card space-y-3" aria-labelledby="publish">
        <x-ui.section-label id="publish">Publish</x-ui.section-label>
        @error('publish')<p class="field-error" role="alert"><x-ui.icon name="warning-circle" />{{ $message }}</p>@enderror
        @if ($published)
            <p class="text-body-sm">Public page: <a class="link" href="{{ route('reports.show', $report->public_slug) }}">{{ route('reports.show', $report->public_slug) }}</a></p>
            <p class="text-body-sm text-neutral-600">Unpublishing removes public access at once and revokes every public file link. Publishing again keeps the same address and creates new file links.</p>
            <form method="POST" action="{{ route('results.report.unpublish', $test) }}">
                @csrf
                <x-ui.button type="submit" variant="secondary" icon="eye-slash">Unpublish</x-ui.button>
            </form>
        @else
            <ul class="space-y-1 text-body-sm">
                @foreach (['Write the question the report answers.' => 'Question written', 'Approve at least one asset for public delivery.' => 'At least one asset approved', 'The test needs a completed run.' => 'Completed test run'] as $blocker => $label)
                    @php($ok = $report->exists && ! in_array($blocker, $blockers, true))
                    <li class="flex items-center gap-2">
                        <x-ui.icon :name="$ok ? 'check-circle' : 'x-circle'" :class="$ok ? 'text-success' : 'text-destructive'" />
                        {{ $label }}<span class="sr-only">: {{ $ok ? 'done' : 'missing' }}</span>
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('results.report.publish', $test) }}" class="flex flex-wrap items-center gap-3">
                @csrf
                <x-ui.button type="submit" icon="globe-simple" :disabled="$blockers !== []">Publish</x-ui.button>
                @if ($blockers)<p class="text-body-sm text-neutral-600">{{ $blockers[0] }} The checks use the saved draft.</p>@endif
            </form>
        @endif
    </section>
</x-layouts::app>
