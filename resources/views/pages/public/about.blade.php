@php
    $sections = [
        'purpose' => 'Purpose',
        'method' => 'How tests work',
        'budgets' => 'Budgets, not scores',
        'limits' => 'Limitations',
    ];
@endphp
<x-layouts::public title="About" description="What the 3D Render Test Dashboard measures and how.">
    <div class="page-inline page-block mx-auto grid max-w-[var(--content-max-width)] gap-8 lg:grid-cols-[14rem_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-6 lg:self-start">
            <nav aria-label="On this page" class="space-y-2">
                <p class="section-label">On this page</p>
                <ul class="flex flex-wrap gap-1 lg:flex-col">
                    @foreach ($sections as $id => $label)
                        <li><a href="#{{ $id }}" class="nav-link">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </aside>

        <div class="max-w-[var(--readable-width)] space-y-10">
            <header class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-h1 font-bold">About</h1>
                    <x-ui.badge variant="warning" icon="warning">Placeholder text</x-ui.badge>
                </div>
                <p class="text-neutral-600">The owner has not supplied the About text yet. The sections below are placeholders based on the product specification.</p>
            </header>

            <section id="purpose" class="scroll-mt-6 space-y-3">
                <h2 class="text-h2 font-bold">Purpose</h2>
                <p>Render Test measures how 3D web assets made with different tools load and render in the browser, so teams can check a model against a performance budget before they ship it.</p>
            </section>
            <section id="method" class="scroll-mt-6 space-y-3">
                <h2 class="text-h2 font-bold">How tests work</h2>
                <p>Every test runs in the browser with fixed renderer settings. Each measurement repeats, and reports show the median and the spread. Frame interval and render work time are recorded separately.</p>
            </section>
            <section id="budgets" class="scroll-mt-6 space-y-3">
                <h2 class="text-h2 font-bold">Budgets, not scores</h2>
                <p>A result is Pass or Fail against each enabled budget item for one device class. The product never ranks assets or combines metrics into a score.</p>
            </section>
            <section id="limits" class="scroll-mt-6 space-y-3">
                <h2 class="text-h2 font-bold">Limitations</h2>
                <p>Results apply to the recorded device and browser only. Some values, such as GPU time and JS heap, are available in some browsers only.</p>
            </section>
        </div>
    </div>
</x-layouts::public>
