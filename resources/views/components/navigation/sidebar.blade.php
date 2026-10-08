@php
    $is = fn (...$patterns) => request()->routeIs(...$patterns);
    $list = request()->route('list');

    // Every item is always visible (no collapsed groups). Items without a screen yet are
    // disabled with the milestone that adds them (SITEMAP.md §5).
    $sections = [
        '' => [
            ['label' => 'Dashboard', 'icon' => 'squares-four', 'href' => route('dashboard'), 'active' => $is('dashboard')],
        ],
        'Assets' => [
            ['label' => 'Library', 'icon' => 'cube', 'href' => route('assets.index'), 'active' => $is('assets.index', 'assets.show', 'assets.edit')],
            ['label' => 'Upload', 'icon' => 'upload-simple', 'href' => route('assets.upload'), 'active' => $is('assets.upload')],
        ],
        'Performance Lab' => [
            ['label' => 'Side-by-Side Test', 'icon' => 'columns', 'soon' => 'M4.3'],
            ['label' => 'Crowd Test', 'icon' => 'users-three', 'soon' => 'M4.4'],
        ],
        'Results' => [
            ['label' => 'Test history', 'icon' => 'chart-line', 'href' => route('results.index'), 'active' => $is('results.*')],
            ['label' => 'Tool summary', 'icon' => 'scales', 'soon' => 'M4.5'],
        ],
    ];

    if (auth()->user()->isAdmin()) {
        $optionIcons = [
            'tools' => 'wrench', 'objects' => 'shapes', 'camera' => 'camera', 'lighting' => 'lightbulb', 'background' => 'paint-bucket',
            'resolution' => 'frame-corners', 'device-classes' => 'devices', 'targets' => 'target',
        ];
        $sections['Administration'] = [
            ['label' => 'Users', 'icon' => 'users', 'href' => route('admin.users.index'), 'active' => $is('admin.users.*')],
        ];
        $sections['Option lists'] = collect(\App\Support\OptionLists::LISTS)->map(fn ($definition, $key) => [
            'label' => $definition['label'], 'icon' => $optionIcons[$key], 'href' => route('admin.options.index', $key), 'active' => $list === $key,
        ])->values()->all();
    }
@endphp
{{-- Collapsible to icons on desktop, off-canvas drawer below lg (DESIGN.md §8.1). --}}
<div class="sidebar-backdrop lg:hidden" x-show="mobileOpen" x-cloak x-on:click="closeMobile()"></div>
<aside id="app-sidebar" class="app-sidebar" :data-open="mobileOpen" aria-label="Application">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="font-heading text-h3 font-bold">
            <span class="sidebar-text">Render Test</span>
            <span class="sidebar-rail-only" aria-hidden="true">RT</span>
        </a>
    </div>

    <nav aria-label="Main" class="sidebar-scroll">
        @foreach ($sections as $heading => $items)
            @if ($heading)
                @if ($heading === 'Administration')<hr class="sidebar-divider">@endif
                <p class="sidebar-heading" id="nav-{{ Str::slug($heading) }}">{{ $heading }}</p>
            @endif
            <ul class="space-y-1" @if ($heading) aria-labelledby="nav-{{ Str::slug($heading) }}" @endif>
                @foreach ($items as $item)
                    <li>
                        @isset($item['soon'])
                            <span class="sidebar-link" aria-disabled="true" title="{{ $item['label'] }} — coming in {{ $item['soon'] }}">
                                <x-ui.icon :name="$item['icon']" size="md" />
                                <span class="sidebar-text">{{ $item['label'] }} <span class="sidebar-soon">{{ $item['soon'] }}</span></span>
                                <span class="sr-only">(coming in {{ $item['soon'] }})</span>
                            </span>
                        @else
                            <a href="{{ $item['href'] }}" class="sidebar-link" @if ($item['active']) aria-current="page" @endif title="{{ $item['label'] }}">
                                <x-ui.icon :name="$item['icon']" size="md" /><span class="sidebar-text">{{ $item['label'] }}</span>
                            </a>
                        @endisset
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <x-ui.button variant="ghost" size="sm" class="hidden w-full justify-start lg:inline-flex" x-on:click="toggleCollapsed()"
            x-bind:aria-expanded="!collapsed" aria-controls="app-sidebar" aria-expanded="true">
            <x-ui.icon name="sidebar-simple" /><span class="sidebar-text" x-text="collapsed ? 'Expand' : 'Collapse'">Collapse</span>
            <span class="sr-only" x-show="collapsed">Expand sidebar</span>
        </x-ui.button>
    </div>
</aside>
