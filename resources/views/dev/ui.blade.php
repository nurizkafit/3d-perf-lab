<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UI kit — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $states = ['default' => null, 'hover' => 'hover', 'focus' => 'focus', 'active' => 'active'];
    $variants = ['primary', 'secondary', 'outline', 'ghost', 'destructive'];
    $tools = ['blender' => 'Blender', 'prisma3d' => 'Prisma3D', 'spline' => 'Spline'];
@endphp
<body class="bg-neutral-100 text-neutral-950">
<main class="mx-auto max-w-[var(--content-max-width)] px-[var(--page-padding-inline)] py-[var(--page-padding-block)] space-y-10">

    <header class="space-y-2">
        <p class="section-label">Local only · M1.1</p>
        <h1 class="text-display font-bold">UI kit</h1>
        <p class="max-w-[var(--readable-width)] text-neutral-600">
            Every component in <code>resources/views/components/ui/</code> in every state.
            Hover, focus, and active are forced with <code>data-demo</code> so they can be compared side by side.
        </p>
        <div class="flex flex-wrap gap-2 pt-2" x-data="{ running: false }">
            <x-ui.button variant="outline" size="sm" icon="gauge"
                x-on:click="running = !running; running ? document.documentElement.setAttribute('data-benchmark', 'running') : document.documentElement.removeAttribute('data-benchmark')"
                x-bind:aria-pressed="running">
                <span x-text="running ? 'Benchmark motion kill switch: on' : 'Benchmark motion kill switch: off'">Benchmark motion kill switch: off</span>
            </x-ui.button>
        </div>
    </header>

    {{-- Colors --}}
    <section class="space-y-3" aria-labelledby="colors">
        <x-ui.section-label id="colors">Colors</x-ui.section-label>
        <div class="grid gap-2 [grid-template-columns:repeat(auto-fill,minmax(min(100%,9rem),1fr))]">
            @foreach ([
                'primary' => 'bg-primary', 'primary-dark' => 'bg-primary-dark', 'primary-light' => 'bg-primary-light', 'primary-tint' => 'bg-primary-tint',
                'neutral-0' => 'bg-neutral-0', 'neutral-50' => 'bg-neutral-50', 'neutral-100' => 'bg-neutral-100', 'neutral-200' => 'bg-neutral-200',
                'neutral-300' => 'bg-neutral-300', 'neutral-400' => 'bg-neutral-400', 'neutral-500' => 'bg-neutral-500', 'neutral-600' => 'bg-neutral-600',
                'neutral-700' => 'bg-neutral-700', 'neutral-800' => 'bg-neutral-800',
                'neutral-900' => 'bg-neutral-900', 'neutral-950' => 'bg-neutral-950', 'success' => 'bg-success', 'warning' => 'bg-warning',
                'destructive' => 'bg-destructive', 'info' => 'bg-info',
            ] as $token => $class)
                <div class="card" data-size="sm">
                    <div class="h-10 rounded-md border border-neutral-200 {{ $class }}"></div>
                    <p class="num pt-2">{{ $token }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Typography --}}
    <section class="space-y-3" aria-labelledby="type">
        <x-ui.section-label id="type">Typography</x-ui.section-label>
        <div class="card space-y-3">
            <p class="font-heading text-display font-bold">Display — Render Test</p>
            <p class="font-heading text-h1 font-bold">H1 — Asset library</p>
            <p class="font-heading text-h2 font-bold">H2 — Budget result</p>
            <p class="font-heading text-h3 font-semibold">H3 — Load phases</p>
            <p class="text-body">Body — The browser measures; the server stores and evaluates.</p>
            <p class="text-body-sm text-neutral-600">Body small — Results apply to the recorded device and browser only.</p>
            <p class="text-label font-semibold">Label — Production time (h)</p>
            <p class="num">Data — 1111.11 · 8888.88 · 16.70</p>
            <div>
                <p class="text-label font-semibold">Metric card</p>
                <p class="font-heading text-metric font-bold">16.7 ms</p>
                <p class="text-body-sm text-neutral-600">p95 frame interval</p>
            </div>
        </div>
    </section>

    {{-- Buttons --}}
    <section class="space-y-3" aria-labelledby="buttons">
        <x-ui.section-label id="buttons">Button</x-ui.section-label>
        <x-ui.table caption="Button variants and states">
            <x-slot:head>
                <tr><th>Variant</th>@foreach (array_keys($states) as $state)<th>{{ ucfirst($state) }}</th>@endforeach<th>Disabled</th><th>Loading</th></tr>
            </x-slot:head>
            @foreach ($variants as $variant)
                <tr>
                    <td class="font-semibold">{{ ucfirst($variant) }}</td>
                    @foreach ($states as $demo)
                        <td class="py-3"><x-ui.button :variant="$variant" icon="upload-simple" :data-demo="$demo">Upload</x-ui.button></td>
                    @endforeach
                    <td class="py-3"><x-ui.button :variant="$variant" icon="upload-simple" disabled>Upload</x-ui.button></td>
                    <td class="py-3"><x-ui.button :variant="$variant" loading>Saving</x-ui.button></td>
                </tr>
            @endforeach
        </x-ui.table>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button size="sm" icon="pencil-simple">Small</x-ui.button>
            <x-ui.button>Default</x-ui.button>
            <x-ui.button size="icon" variant="secondary" aria-label="Delete asset"><x-ui.icon name="trash" /></x-ui.button>
            <x-ui.button href="#buttons" variant="outline" icon="download-simple">Link button</x-ui.button>
        </div>
    </section>

    {{-- Badges --}}
    <section class="space-y-3" aria-labelledby="badges">
        <x-ui.section-label id="badges">Badge</x-ui.section-label>
        <div class="card flex flex-wrap items-center gap-2">
            <x-ui.badge status="pass" />
            <x-ui.badge status="fail" />
            <x-ui.badge status="not-measured" />
            <x-ui.badge status="warning">Unstable</x-ui.badge>
            <x-ui.badge variant="info" icon="info">Running</x-ui.badge>
            <x-ui.badge>GLB</x-ui.badge>
            <x-ui.badge variant="primary">Comparison</x-ui.badge>
            <x-ui.badge variant="outline">Draft</x-ui.badge>
            <x-ui.badge variant="sample">Sample data</x-ui.badge>
        </div>
    </section>

    {{-- Cards --}}
    <section class="space-y-3" aria-labelledby="cards">
        <x-ui.section-label id="cards">Card</x-ui.section-label>
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(min(100%,14rem),1fr))]">
            <x-ui.card>
                <p class="font-semibold">Static</p>
                <p class="text-body-sm text-neutral-600">Hairline border, no shadow.</p>
            </x-ui.card>
            <x-ui.card size="sm">
                <p class="font-semibold">Static compact</p>
                <p class="text-body-sm text-neutral-600">Padding 4.</p>
            </x-ui.card>
            @foreach ($states as $label => $demo)
                <x-ui.card href="#cards" :data-demo="$demo">
                    <p class="font-semibold">Clickable — {{ $label }}</p>
                    <p class="text-body-sm text-neutral-600">Strong border, hard shadow.</p>
                </x-ui.card>
            @endforeach
            <x-ui.card href="#cards" data-selected>
                <p class="font-semibold">Clickable — selected</p>
                <p class="text-body-sm text-neutral-600">Primary tint fill.</p>
            </x-ui.card>
        </div>
    </section>

    {{-- Form controls --}}
    <section class="space-y-3" aria-labelledby="forms">
        <x-ui.section-label id="forms">Input · Select · Textarea</x-ui.section-label>
        <div class="card grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(min(100%,16rem),1fr))]">
            <x-ui.input name="name_default" label="Asset name" placeholder="Bird" hint="Default: file name without extension." />
            <x-ui.input name="name_hover" label="Hover" value="Bird" data-demo="hover" />
            <x-ui.input name="name_focus" label="Focus" value="Bird" data-demo="focus" />
            <x-ui.input name="name_disabled" label="Disabled" value="Dummy Author A" disabled />
            <x-ui.input name="hours" label="Production time (h)" type="number" value="-1" error="Production time must be 0 or more." />
            <x-ui.select name="tool" label="Tool" :options="$tools" placeholder="Select a tool" />
            <x-ui.select name="tool_focus" label="Tool (focus)" :options="$tools" value="spline" data-demo="focus" />
            <x-ui.select name="tool_error" label="Tool (error)" :options="$tools" placeholder="Select a tool" error="Select a tool." />
            <x-ui.select name="tool_disabled" label="Tool (disabled)" :options="$tools" value="blender" disabled />
            <x-ui.textarea name="description" label="Description" hint="Optional." />
            <x-ui.textarea name="description_error" label="Description (error)" value="x" error="Description is too short." />
        </div>
    </section>

    {{-- Table --}}
    <section class="space-y-3" aria-labelledby="table">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.section-label id="table">Table</x-ui.section-label>
            <x-ui.badge variant="sample">Sample data</x-ui.badge>
        </div>
        <x-ui.table caption="Sample asset table" scroll>
            <x-slot:head>
                <tr><th>Asset</th><th>Tool</th><th>Author</th><th data-numeric>Size (MB)</th><th data-numeric>Triangles</th><th data-numeric>Load (ms)</th><th>Budget</th></tr>
            </x-slot:head>
            @foreach ([
                ['Apple', 'Blender', 'Dummy Author A', '0.01', '1,204', '412.30', 'pass', false],
                ['Chicken', 'Prisma3D', 'Dummy Author B', '0.01', '980', '388.10', 'pass', true],
                ['Book', 'Spline', 'Dummy Author C', '0.01', '96', '5,201.70', 'fail', false],
                ['Unknown GPU', 'Spline', 'Dummy Author C', '0.01', '96', '—', 'not-measured', false],
            ] as [$asset, $tool, $author, $size, $tris, $load, $status, $selected])
                <tr @if ($selected) aria-selected="true" @endif>
                    <td class="font-semibold">{{ $asset }}</td>
                    <td>{{ $tool }}</td>
                    <td>{{ $author }}</td>
                    <td data-numeric>{{ $size }}</td>
                    <td data-numeric>{{ $tris }}</td>
                    <td data-numeric>{{ $load }}</td>
                    <td><x-ui.badge :status="$status" /></td>
                </tr>
            @endforeach
        </x-ui.table>
        <p class="text-body-sm text-neutral-600">Second row: selected. Hover a row for the hover state. The table scrolls sideways on narrow screens.</p>
    </section>

    {{-- Tabs, dropdown, dialog, toast --}}
    <section class="space-y-3" aria-labelledby="overlays">
        <x-ui.section-label id="overlays">Tabs · Dropdown · Dialog · Toast</x-ui.section-label>
        <div class="card space-y-6">
            <x-ui.tabs id="demo-tabs" :tabs="['grid' => 'Grid', 'table' => 'Table', 'deleted' => 'Deleted']">
                <x-slot:grid><p class="pt-3">Grid panel. Use arrow keys to move between tabs.</p></x-slot:grid>
                <x-slot:table><p class="pt-3">Table panel.</p></x-slot:table>
                <x-slot:deleted><p class="pt-3">Deleted panel.</p></x-slot:deleted>
            </x-ui.tabs>

            <div class="flex flex-wrap items-start gap-3">
                <x-ui.dropdown id="demo-dropdown" label="Actions">
                    <button type="button" role="menuitem"><x-ui.icon name="pencil-simple" /> Edit</button>
                    <button type="button" role="menuitem"><x-ui.icon name="download-simple" /> Download</button>
                    <hr role="separator">
                    <button type="button" role="menuitem" data-variant="destructive"><x-ui.icon name="trash" /> Delete</button>
                </x-ui.dropdown>

                <x-ui.button variant="secondary" data-dialog-open="demo-dialog" icon="globe-simple">Open dialog</x-ui.button>

                <x-ui.button variant="outline" icon="check-circle"
                    x-data x-on:click="toast({ category: 'success', title: 'Assets registered', description: '3 assets are ready for testing.' })">
                    Success toast
                </x-ui.button>
                <x-ui.button variant="outline" icon="x-circle"
                    x-data x-on:click="toast({ category: 'error', title: 'Upload failed', description: 'bird.gltf: the referenced file bird.bin is missing.' })">
                    Error toast (stays)
                </x-ui.button>
            </div>
        </div>

        <x-ui.dialog id="demo-dialog" title="Publish report" description="The public can view this report and the approved assets.">
            <p class="text-body-sm">Focus stays in this dialog. Escape closes it and focus returns to the button.</p>
            <x-slot:footer>
                <form method="dialog" class="flex justify-end gap-2">
                    <x-ui.button variant="outline" type="submit">Cancel</x-ui.button>
                    <x-ui.button type="submit" icon="globe-simple">Publish</x-ui.button>
                </form>
            </x-slot:footer>
        </x-ui.dialog>
    </section>

    {{-- Empty state, loading --}}
    <section class="space-y-3" aria-labelledby="empty">
        <x-ui.section-label id="empty">Empty state · Loading</x-ui.section-label>
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,20rem),1fr))]">
            <x-ui.empty-state icon="cube" message="No assets yet. Upload GLB or GLTF files to start.">
                <x-ui.button icon="upload-simple">Upload assets</x-ui.button>
            </x-ui.empty-state>
            <div class="card space-y-3" aria-busy="true" aria-label="Loading">
                <div class="skeleton h-6 w-1/2"></div>
                <div class="skeleton h-4 w-full"></div>
                <div class="skeleton h-4 w-3/4"></div>
            </div>
        </div>
    </section>

    {{-- Motion --}}
    <section class="space-y-3" aria-labelledby="motion">
        <x-ui.section-label id="motion">Motion</x-ui.section-label>
        <div class="card space-y-3" x-data="{ n: 0 }">
            <p class="text-body-sm text-neutral-600">Bulk-apply cell flash (slow). With reduced motion or the benchmark kill switch, the flash and all transitions are removed.</p>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="outline" size="sm" x-on:click="n++">Flash cell</x-ui.button>
                <template x-for="i in [n]" :key="i">
                    <span class="num rounded-sm px-2 py-1" :class="n && 'cell-flash'">2.5 h</span>
                </template>
            </div>
        </div>
    </section>

</main>
<x-ui.toast />
</body>
</html>
