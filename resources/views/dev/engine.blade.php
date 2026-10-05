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
        <p class="section-label">Local only · M4.1</p>
        <h1 class="text-h1 font-bold">Engine self-test</h1>
        <p class="max-w-[var(--readable-width)] text-neutral-600">
            Runs <code>{{ $fixture }}</code> in benchmark mode at 1280 × 720: refresh-rate estimate, cold and warm load, 1 s warm-up, 5 s measurement window.
            Jank uses a budget frame time of 16.7 ms. Nothing is stored. Keep this tab visible; hiding it makes the run invalid.
        </p>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button icon="play" data-engine-start>Run self-test</x-ui.button>
            <p class="text-body-sm" role="status" data-engine-status></p>
        </div>
    </header>

    <div class="viewer-frame min-h-0 bg-neutral-800 p-2" data-engine-stage></div>

    <div class="grid gap-6 xl:grid-cols-3" data-engine-output hidden>
        <section class="space-y-2"><x-ui.section-label as="h2">Runtime summary (§3.3)</x-ui.section-label>
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
