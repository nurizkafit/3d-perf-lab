<x-layouts::app title="Performance Lab" description="Look at the assets first, then measure them. Each test answers a different question." :sample="false">
    <ul class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,18rem),1fr))]">
        <li>
            <x-ui.card class="flex h-full flex-col gap-3" aria-disabled="true">
                <x-ui.icon name="columns" size="md" />
                <h2 class="text-h3 font-semibold">Side-by-Side Test <span class="sidebar-soon">M4.3</span></h2>
                <p class="text-body-sm text-neutral-600">2–4 assets next to each other. Which version loads faster and runs smoother?</p>
            </x-ui.card>
        </li>
        <li>
            <x-ui.card class="flex h-full flex-col gap-3" aria-disabled="true">
                <x-ui.icon name="users-three" size="md" />
                <h2 class="text-h3 font-semibold">Crowd Test <span class="sidebar-soon">M4.4</span></h2>
                <p class="text-body-sm text-neutral-600">Copies fill the scene until it stops being smooth. How many copies can this device show?</p>
            </x-ui.card>
        </li>
        <li>
            <x-ui.card class="flex h-full flex-col gap-3" aria-disabled="true">
                <x-ui.icon name="scales" size="md" />
                <h2 class="text-h3 font-semibold">Tool summary <span class="sidebar-soon">M4.5</span></h2>
                <p class="text-body-sm text-neutral-600">Compare authoring tools on the objects that every tool has.</p>
            </x-ui.card>
        </li>
    </ul>
</x-layouts::app>
