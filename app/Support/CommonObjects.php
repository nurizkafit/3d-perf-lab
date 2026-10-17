<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\Tool;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * Objects that exist in every selected tool (PRD.md §8.3, decision D48), with the candidate assets per tool
 * (newest first), and the objects that some selected tool lacks. Registered, not deleted assets only.
 * Used by the Crowd Test Per tool and the Tool summary.
 */
class CommonObjects
{
    /**
     * @param  list<int>  $toolIds
     * @return array{tools: list<array{id: int, name: string}>, objects: list<array>, missing: list<array>}
     */
    public static function for(array $toolIds): array
    {
        $tools = Tool::whereIn('id', $toolIds)->get()->sortBy(fn (Tool $tool) => array_search($tool->id, $toolIds))->values();
        $assets = Asset::with(['object', 'tool', 'metrics'])->whereIn('tool_id', $tools->pluck('id'))->whereNotNull('object_id')
            ->orderByDesc('created_at')->orderByDesc('id')->get();

        $objects = [];
        $missing = [];
        foreach ($assets->groupBy('object_id')->sortBy(fn (Collection $group) => $group->first()->object->sort_order.'|'.$group->first()->object->name) as $objectId => $group) {
            $object = $group->first()->object;
            $byTool = $group->groupBy('tool_id');
            $lacking = $tools->reject(fn (Tool $tool) => $byTool->has($tool->id));
            if ($lacking->isNotEmpty()) {
                $missing[] = ['id' => $objectId, 'name' => $object->name, 'missing_tools' => $lacking->pluck('name')->values()->all()];

                continue;
            }
            $objects[] = [
                'id' => $objectId,
                'name' => $object->name,
                'assets' => $tools->mapWithKeys(fn (Tool $tool) => [$tool->id => $byTool[$tool->id]->map(fn (Asset $asset) => self::asset($asset))->values()->all()])->all(),
            ];
        }

        return [
            'tools' => $tools->map(fn (Tool $tool) => ['id' => $tool->id, 'name' => $tool->name])->all(),
            'objects' => $objects,
            'missing' => $missing,
        ];
    }

    public static function asset(Asset $asset): array
    {
        return [
            'id' => $asset->id,
            'name' => $asset->name,
            'tool' => $asset->tool->name,
            'object' => $asset->object?->name,
            'size' => Number::fileSize($asset->file_size, maxPrecision: 1),
            'triangles' => $asset->metrics?->triangles,
            'registered' => $asset->created_at?->display()->format('Y-m-d'),
            'file_url' => route('assets.file', [$asset, basename($asset->web_file_path)]),
        ];
    }
}
