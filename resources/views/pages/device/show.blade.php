<x-layouts::app title="Device class" description="Confirm which class this device belongs to. Every test run records it." :sample="false">
    <script type="application/json" id="device-config">@json(['classes' => $classes, 'currentId' => $currentId])</script>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]" x-data="deviceCheck">
        <section class="card space-y-4" aria-labelledby="signals">
            <x-ui.section-label id="signals">Detected signals</x-ui.section-label>
            <dl class="grid grid-cols-[minmax(0,auto)_1fr] gap-x-4 gap-y-2 text-body-sm" x-show="context" x-cloak>
                <dt class="text-neutral-600">Category</dt><dd x-text="suggestion?.summary"></dd>
                <dt class="text-neutral-600">GPU</dt><dd><span x-text="gpu"></span> <span class="text-neutral-600">(browser-reported)</span></dd>
                <dt class="text-neutral-600">CPU threads</dt><dd class="num" x-text="context?.hardware_concurrency ?? '—'"></dd>
                <dt class="text-neutral-600">Memory</dt><dd class="num" x-text="context?.device_memory ? context.device_memory + ' GB or more' : 'Not exposed'"></dd>
                <dt class="text-neutral-600">Screen</dt><dd class="num" x-text="context ? context.screen.width + ' × ' + context.screen.height + ' @ ' + context.device_pixel_ratio + '×' : ''"></dd>
                <dt class="text-neutral-600">WebGL</dt><dd class="num" x-text="context?.webgl_version ? 'WebGL ' + context.webgl_version : 'Not available'"></dd>
            </dl>
            <p class="text-body-sm text-neutral-600" x-show="!context">Reading the browser signals…</p>
            <p class="text-body-sm text-neutral-600">The suggestion is never stored. Laptop and desktop cannot be told apart reliably, and iPhone GPUs are masked; choose the class yourself.</p>
        </section>

        <form method="POST" action="{{ route('device.store') }}" class="card space-y-4" aria-labelledby="choose" x-on:submit="remember()">
            @csrf
            <input type="hidden" name="return_to" value="{{ request('return_to') }}">
            <x-ui.section-label id="choose">Choose the device class</x-ui.section-label>
            <fieldset class="space-y-2" @error('device_class_id') aria-describedby="device-error" @enderror>
                <legend class="sr-only">Device class</legend>
                @foreach ($classes as $class)
                    <label class="flex min-h-[var(--control-height)] items-center gap-3 rounded-md border border-neutral-200 px-3 has-[:checked]:border-neutral-800 has-[:checked]:bg-primary-tint">
                        <input type="radio" name="device_class_id" value="{{ $class->id }}" class="input" x-model="selected" @checked($currentId === $class->id)>
                        <span class="font-semibold">{{ $class->name }}</span>
                        <x-ui.badge variant="info" x-show="suggestion?.classId === {{ $class->id }}" x-cloak>Suggested</x-ui.badge>
                        <span class="ms-auto text-body-sm text-neutral-600">{{ ucfirst($class->category) }}</span>
                    </label>
                @endforeach
            </fieldset>
            @error('device_class_id')<p id="device-error" class="field-error"><x-ui.icon name="warning-circle" />{{ $message }}</p>@enderror
            <x-ui.button type="submit" icon="check">Confirm device class</x-ui.button>
        </form>
    </div>
</x-layouts::app>
