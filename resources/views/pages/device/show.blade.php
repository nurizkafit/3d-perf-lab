@php($classes = \Database\Seeders\DeviceClassSeeder::CLASSES)
<x-layouts::app title="Device class" description="Confirm which class this device belongs to. Every test run records it.">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        <section class="card space-y-4" aria-labelledby="signals">
            <x-ui.section-label id="signals">Detected signals</x-ui.section-label>
            <dl class="grid grid-cols-[minmax(0,auto)_1fr] gap-x-4 gap-y-2 text-body-sm">
                <dt class="text-neutral-600">Category</dt><dd>Laptop or desktop</dd>
                <dt class="text-neutral-600">Platform</dt><dd>macOS</dd>
                <dt class="text-neutral-600">GPU</dt><dd>Apple M2 (browser-reported)</dd>
                <dt class="text-neutral-600">CPU threads</dt><dd class="num">8</dd>
                <dt class="text-neutral-600">Screen</dt><dd class="num">1512 × 982 @ 2×</dd>
            </dl>
            <x-ui.badge variant="info" icon="info">Suggestion: Laptop — Integrated GPU</x-ui.badge>
            <p class="text-body-sm text-neutral-600">The suggestion is never stored. Laptop and desktop cannot be told apart reliably; choose the class yourself.</p>
        </section>

        <form class="card space-y-4" aria-labelledby="choose">
            <x-ui.section-label id="choose">Choose the device class</x-ui.section-label>
            <fieldset class="space-y-2">
                <legend class="sr-only">Device class</legend>
                @foreach ($classes as [$name, $category, $platform])
                    <label class="flex min-h-[var(--control-height)] items-center gap-3 rounded-md border border-neutral-200 px-3 has-[:checked]:border-neutral-800 has-[:checked]:bg-primary-tint">
                        <input type="radio" name="device_class" value="{{ $name }}" class="input" @checked($name === 'Laptop — Integrated GPU')>
                        <span class="font-semibold">{{ $name }}</span>
                        <span class="ms-auto text-body-sm text-neutral-600">{{ ucfirst($category) }}</span>
                    </label>
                @endforeach
            </fieldset>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button type="submit" icon="check" disabled>Confirm device class</x-ui.button>
                <p class="text-body-sm text-neutral-600">Saving arrives in M4.1.</p>
            </div>
        </form>
    </div>
</x-layouts::app>
