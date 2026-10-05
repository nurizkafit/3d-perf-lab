@props(['tools', 'max' => 8])
{{-- Asset Selector modal (PRD.md §8.2), shared by all methods. Open with: $dispatch('open-asset-selector', { selected: [...] }).
     It dispatches 'assets-selected' with the chosen assets in selection order. --}}
<dialog id="asset-selector" class="dialog asset-selector" aria-labelledby="asset-selector-title" x-data="assetSelector({{ $max }})"
        x-on:open-asset-selector.window="open($event.detail)" x-on:close="onClose()">
    <div>
        <header>
            <h2 id="asset-selector-title">Select assets</h2>
            <p>Search and filter; your selection stays while you search. Up to {{ $max }} assets. Arrow keys move, Space selects.</p>
        </header>
        <section class="space-y-3">
            <div class="grid items-end gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,9rem),1fr))]">
                <div class="sm:col-span-2">
                    <label for="selector-q" class="field-label">Search by name</label>
                    <input id="selector-q" type="search" class="input" x-model="query" x-on:input.debounce.300ms="search()" x-on:keydown.down.prevent="focusOption(0)" autocomplete="off">
                </div>
                <div>
                    <label for="selector-tool" class="field-label">Tool</label>
                    <select id="selector-tool" class="select" x-model="tool" x-on:change="search()">
                        <option value="">All tools</option>
                        @foreach ($tools as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="selector-format" class="field-label">Format</label>
                    <select id="selector-format" class="select" x-model="format" x-on:change="search()">
                        <option value="">All formats</option><option value="glb">GLB</option><option value="gltf">GLTF</option>
                    </select>
                </div>
                <div>
                    <label for="selector-author" class="field-label">Author</label>
                    <input id="selector-author" class="input" x-model="author" x-on:input.debounce.300ms="search()">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button size="sm" variant="secondary" x-on:click="selectVisible()" x-bind:disabled="results.length === 0">Select all visible</x-ui.button>
                <x-ui.button size="sm" variant="ghost" x-on:click="clear()" x-bind:disabled="selected.length === 0">Clear</x-ui.button>
                <p class="text-body-sm text-neutral-600" role="status" x-text="status"></p>
            </div>

            <div class="grid gap-3 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                <ul class="selector-list" role="listbox" aria-multiselectable="true" aria-label="Assets">
                    <template x-for="(asset, index) in results" :key="asset.id">
                        <li role="option" class="selector-option" :id="'selector-option-' + asset.id" :aria-selected="isSelected(asset)" tabindex="-1"
                            x-on:click="toggle(asset)" x-on:keydown.space.prevent="toggle(asset)" x-on:keydown.enter.prevent="toggle(asset)"
                            x-on:keydown.down.prevent="focusOption(index + 1)" x-on:keydown.up.prevent="index === 0 ? $root.querySelector('#selector-q').focus() : focusOption(index - 1)"
                            x-on:focus="schedulePreview(asset)" x-on:mouseenter="schedulePreview(asset)">
                            <span class="selector-check" aria-hidden="true"><x-ui.icon name="check" /></span>
                            <span class="thumb overflow-hidden">
                                <img x-show="asset.thumbnail_url" :src="asset.thumbnail_url" alt="" class="size-full object-cover">
                                <span x-show="!asset.thumbnail_url"><x-ui.icon name="cube" /></span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold" x-text="asset.name"></span>
                                <span class="block truncate text-body-sm text-neutral-600" x-text="asset.tool + ' · ' + asset.author"></span>
                            </span>
                            <span class="num text-end text-neutral-600">
                                <span class="block" x-text="asset.size"></span>
                                <span class="block" x-text="asset.triangles === null ? '— tris' : asset.triangles.toLocaleString() + ' tris'"></span>
                            </span>
                        </li>
                    </template>
                    <li class="p-4 text-body-sm text-neutral-600" x-show="!loading && results.length === 0" role="presentation">No registered asset matches.</li>
                </ul>
                <figure class="viewer-frame selector-preview hidden lg:grid" aria-live="polite">
                    <div class="viewer-stage" data-selector-preview></div>
                    <figcaption class="viewer-label" x-show="previewAsset" x-text="previewAsset ? 'Preview: ' + previewAsset.name : ''"></figcaption>
                    <p class="viewer-message" x-show="!previewAsset">Hover or focus an asset to preview it.</p>
                </figure>
            </div>
        </section>
        <footer class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-body-sm" role="status">
                <strong x-text="selected.length + ' selected'"></strong><span x-show="selected.length" x-text="': ' + selected.map((a) => a.name).join(', ')"></span>
            </p>
            <div class="flex gap-2">
                <x-ui.button variant="outline" data-dialog-close>Cancel</x-ui.button>
                <x-ui.button icon="check" x-on:click="confirm()" x-bind:disabled="selected.length === 0">Use selected assets</x-ui.button>
            </div>
        </footer>
        <form method="dialog">
            <x-ui.button variant="ghost" size="icon" type="submit" aria-label="Close dialog"><x-ui.icon name="x" /></x-ui.button>
        </form>
    </div>
</dialog>
