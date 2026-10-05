@props(['maxLabel'])
{{-- Drag-and-drop with click-to-browse fallback; part of the uploadTable Alpine component. --}}
<div class="dropzone" :data-over="over"
     x-on:dragover.prevent="over = true" x-on:dragleave.prevent="over = false" x-on:drop.prevent="drop($event)">
    <x-ui.icon name="upload-simple" size="md" />
    <p class="font-semibold">Drop GLB or GLTF files here, or <label for="upload-files" class="link cursor-pointer">browse</label></p>
    <p class="text-body-sm text-neutral-600">Drop a .gltf together with its .bin and texture files. Maximum {{ $maxLabel }} per file.</p>
    <input id="upload-files" type="file" multiple accept=".glb,.gltf,.bin,.png,.jpg,.jpeg,.webp,.ktx2" class="sr-only" x-on:change="pick($event)">
</div>
