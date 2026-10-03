<x-layouts::app title="Report editor" description="A report answers one question with evidence. Reports are private until you publish.">
    <x-slot:actions>
        <x-ui.badge variant="outline">Draft</x-ui.badge>
    </x-slot:actions>

    <section class="space-y-3" aria-labelledby="tested">
        <x-ui.section-label id="tested">Tested assets</x-ui.section-label>
        <p class="text-body-sm text-neutral-600">{{ $report['question'] }} · {{ $report['device_class'] }} · {{ $report['budget_preset'] }}</p>
        <x-assets.tested-assets :assets="$assets" />
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <form class="card space-y-5" method="POST" action="#">
            <x-ui.input name="title" label="Title" :value="$report['title']" />
            <x-ui.textarea name="question" label="Question" :value="$report['question']" hint="Required before publishing." />
            <div class="space-y-2">
                <p class="field-label">Standard limitations (added by the system)</p>
                <ul class="list-disc space-y-1 ps-5 text-body-sm text-neutral-600">
                    @foreach ($standardLimitations as $limitation)<li>{{ $limitation }}</li>@endforeach
                </ul>
            </div>
            <x-ui.textarea name="limitations" label="Additional limitations" :value="implode(PHP_EOL, $report['limitations'])" hint="Optional." />
            <x-ui.textarea name="conclusion" label="Conclusion" :value="$report['conclusion']" hint="Optional. Written by you; the system never writes it." />
            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit" icon="floppy-disk" disabled>Save draft</x-ui.button>
                <span class="self-center text-body-sm text-neutral-600">Saving arrives in M4.5.</span>
            </div>
        </form>

        <div class="space-y-6">
            <section class="card space-y-3" aria-labelledby="approval">
                <x-ui.section-label id="approval">Public asset approval</x-ui.section-label>
                <p class="text-body-sm text-neutral-600">Approved assets can be downloaded by the public for the reproduction. Default: not approved.</p>
                <fieldset class="space-y-2">
                    <legend class="sr-only">Assets approved for public delivery</legend>
                    @foreach ($assets as $asset)
                        <label class="flex items-center gap-3 rounded-md border border-neutral-200 p-3 has-[:checked]:border-neutral-800 has-[:checked]:bg-primary-tint">
                            <input type="checkbox" class="input" name="approved[]" value="{{ $asset['id'] }}">
                            <span class="thumb"><x-ui.icon name="cube" /></span>
                            <span class="min-w-0">
                                <span class="block font-semibold">{{ $asset['name'] }}</span>
                                <span class="block text-body-sm text-neutral-600">{{ $asset['tool'] }} · {{ $asset['author'] }} · {{ $asset['format'] }} · {{ $asset['size'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
            </section>

            <section class="card space-y-3" aria-labelledby="publish">
                <x-ui.section-label id="publish">Publish</x-ui.section-label>
                <ul class="space-y-1 text-body-sm">
                    <li class="flex items-center gap-2"><x-ui.icon name="check-circle" class="text-success" /> Question written</li>
                    <li class="flex items-center gap-2"><x-ui.icon name="x-circle" class="text-destructive" /> At least one asset approved</li>
                    <li class="flex items-center gap-2"><x-ui.icon name="check-circle" class="text-success" /> Completed test run</li>
                </ul>
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.button icon="globe-simple" disabled>Publish</x-ui.button>
                    <a href="{{ route('reports.show', $report['slug']) }}" class="link text-body-sm">Preview public page</a>
                </div>
                <p class="text-body-sm text-neutral-600">Approve at least one asset to publish.</p>
            </section>
        </div>
    </div>
</x-layouts::app>
