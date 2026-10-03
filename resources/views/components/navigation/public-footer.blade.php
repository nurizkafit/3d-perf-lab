<footer class="border-t border-neutral-200 bg-neutral-0">
    <div class="page-inline mx-auto flex max-w-[var(--content-max-width)] flex-wrap items-center justify-between gap-3 py-6 text-body-sm text-neutral-600">
        <p>{{ config('app.name') }}. Results apply to the recorded device and browser only.</p>
        <nav aria-label="Footer" class="flex flex-wrap gap-4">
            <a href="{{ route('reports.index') }}">Reports</a>
            <a href="{{ route('about') }}">About</a>
            <a href="{{ route('contact') }}">Contact</a>
        </nav>
    </div>
</footer>
