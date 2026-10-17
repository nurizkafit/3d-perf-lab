<?php

namespace App\Support;

use App\Models\TestSession;
use App\Models\Tool;
use Illuminate\Support\Collection;

/**
 * Groups of a Crowd test in selection order (BENCHMARK.md §4.6): Per asset = one group per asset; Per tool = one
 * group per tool with its chosen assets in object order. Each group has its own crowd and its own search.
 */
class CrowdGroups
{
    /** @return Collection<int, array{key: int, label: string, tool_id: int|null, assets: Collection}> */
    public static function for(TestSession $test): Collection
    {
        if ($test->method !== 'crowd') {
            return collect();
        }
        $assets = $test->assets; // selection order (position)
        if ($test->scenario !== 'per_tool') {
            return $assets->map(fn ($asset) => ['key' => $asset->id, 'label' => $asset->name, 'tool_id' => null, 'assets' => collect([$asset])])->values();
        }
        $tools = Tool::whereIn('id', $test->configuration_json['tool_ids'])->pluck('name', 'id');

        return collect($test->configuration_json['tool_ids'])->map(fn ($toolId) => [
            'key' => $toolId,
            'label' => $tools[$toolId] ?? 'Tool',
            'tool_id' => $toolId,
            'assets' => $assets->filter(fn ($asset) => (int) $asset->pivot->group_tool_id === (int) $toolId)->values(),
        ])->values();
    }
}
