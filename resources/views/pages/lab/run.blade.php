<x-layouts::benchmark title="Benchmark running">
    <main class="flex min-h-svh flex-col gap-3 p-[var(--page-padding-inline)]">
        {{-- Progress strip: updated between measurement windows only. --}}
        <div class="benchmark-strip" role="status" aria-live="off">
            <span>Comparison Test</span>
            <span>Asset 2 of 3: Chicken</span>
            <span>Repetition 1 of 3</span>
            <span>Step: measurement</span>
            <span>About 1 min 40 s left</span>
            <x-ui.badge variant="sample" class="ms-auto">Sample data</x-ui.badge>
        </div>
        <div class="grid flex-1 place-items-center">
            <div class="benchmark-canvas" aria-label="Benchmark canvas placeholder (1280 × 720)">
                <p>Canvas 1280 × 720</p>
            </div>
        </div>
        <div class="flex justify-center">
            <x-ui.button :href="route('lab.comparison')" variant="secondary" icon="stop">Cancel run</x-ui.button>
        </div>
    </main>
</x-layouts::benchmark>
