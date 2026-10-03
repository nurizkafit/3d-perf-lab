{{-- Drag-and-drop with click-to-browse fallback. M1.3: visual only; uploading arrives in M3.2. --}}
<div x-data="{ over: false }" class="dropzone" :data-over="over"
     x-on:dragover.prevent="over = true" x-on:dragleave="over = false" x-on:drop.prevent="over = false">
    <x-ui.icon name="upload-simple" size="md" />
    <p class="font-semibold">Drop GLB or GLTF files here, or <label for="upload-files" class="link cursor-pointer">browse</label></p>
    <p class="text-body-sm text-neutral-600">A GLTF file needs its .bin and texture files in the same drop. Maximum {{ config('lab.asset_max_upload_mb') }} MB per file.</p>
    <input id="upload-files" type="file" multiple accept=".glb,.gltf,.bin,.png,.jpg,.jpeg,.webp,.ktx2" class="sr-only">
</div>
