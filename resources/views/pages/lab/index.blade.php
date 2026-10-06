<x-layouts::app title="Performance Lab" description="Choose a test method. Each method answers a different question." :sample="false">
    <ul class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,18rem),1fr))]">
        <li>
            <x-ui.card :href="route('lab.comparison')" class="flex h-full flex-col gap-3">
                <x-ui.icon name="columns" size="md" />
                <h2 class="text-h3 font-semibold">Comparison Test</h2>
                <p class="text-body-sm text-neutral-600">N assets in N separate viewers, one at a time. How does each asset load and render on its own?</p>
            </x-ui.card>
        </li>
        <li>
            <x-ui.card :href="route('lab.stress')" class="flex h-full flex-col gap-3">
                <x-ui.icon name="stack" size="md" />
                <h2 class="text-h3 font-semibold">Stress Test</h2>
                <p class="text-body-sm text-neutral-600">N instances in one scene at fixed steps. How does performance change as the count grows?</p>
            </x-ui.card>
        </li>
        <li>
            <x-ui.card :href="route('lab.headroom')" class="flex h-full flex-col gap-3">
                <x-ui.icon name="gauge" size="md" />
                <h2 class="text-h3 font-semibold">Headroom Test</h2>
                <p class="text-body-sm text-neutral-600">Instances increase until the frame budget fails. How many can this device hold?</p>
            </x-ui.card>
        </li>
    </ul>
</x-layouts::app>
