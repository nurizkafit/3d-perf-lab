<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Engine self-test — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-100 text-neutral-950">
<main class="mx-auto max-w-[var(--content-max-width)] space-y-6 px-[var(--page-padding-inline)] py-[var(--page-padding-block)]" data-engine-self-test>
    <script type="application/json" data-engine-config>@json($config)</script>
    <header class="space-y-2">
        <p class="section-label">Local only · M4.1, motion scenarios M4.2</p>
        <h1 class="text-h1 font-bold">Engine self-test</h1>
        <p class="max-w-[var(--readable-width)] text-neutral-600">
            Runs one fixture from <code>fixtures/models/</code> in benchmark mode at 1280 × 720: refresh-rate estimate, cold and warm load, then a warm-up and a 5 s
            measurement window in each selected motion scenario. The smoothness label and stutter use the Target frame rate. Nothing is stored.
            Keep this tab visible; hiding it makes the run invalid.
        </p>
    </header>

    <section class="card space-y-4" aria-labelledby="engine-settings">
        <x-ui.section-label id="engine-settings" as="h2">Settings</x-ui.section-label>
        <div class="max-w-sm">
            <label for="engine-fixture" class="field-label">Fixture</label>
            <select id="engine-fixture" class="select" data-engine-fixture>
                @foreach ($fixtures as $path => $url)
                    <option value="{{ $url }}">{{ $path }}</option>
                @endforeach
            </select>
        </div>
        <fieldset class="space-y-2">
            <legend class="field-label">Motion scenarios</legend>
            <div class="flex flex-wrap gap-4">
                @foreach (\App\Support\MetricDefinitions::MOTIONS as $key => $motion)
                    <label class="flex items-center gap-2 text-body-sm">
                        <input type="checkbox" class="input" value="{{ $key }}" data-engine-motion checked> {{ $motion['label'] }}
                        <span class="text-neutral-600">— {{ $motion['help'] }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
        <x-performance.target-step :target="$target" />
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button icon="play" data-engine-start>Run self-test</x-ui.button>
            <p class="text-body-sm" role="status" data-engine-status></p>
        </div>
    </section>

    <div class="viewer-frame min-h-0 bg-neutral-800 p-2" data-engine-stage></div>

    <div class="grid gap-6 xl:grid-cols-3" data-engine-output hidden>
        <section class="space-y-2"><x-ui.section-label as="h2">Runtime summary per motion (§3.3, §4.4)</x-ui.section-label>
            <div class="table-container"><table class="table"><tbody data-engine-summary></tbody></table></div></section>
        <section class="space-y-2"><x-ui.section-label as="h2">Load phases (§3.2)</x-ui.section-label>
            <div class="table-container"><table class="table"><tbody data-engine-load></tbody></table></div></section>
        <section class="space-y-2"><x-ui.section-label as="h2">Context (§3.4)</x-ui.section-label>
            <div class="table-container"><table class="table"><tbody data-engine-context></tbody></table></div></section>
        <section class="space-y-2 xl:col-span-3"><x-ui.section-label as="h2">Samples</x-ui.section-label>
            <div class="card" data-engine-chart></div></section>
    </div>
</main>
</body>
</html>
