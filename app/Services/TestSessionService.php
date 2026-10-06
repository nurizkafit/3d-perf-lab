<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BudgetPreset;
use App\Models\OptionPreset;
use App\Models\TestSession;
use App\Models\User;
use App\Support\MetricDefinitions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates a test session with snapshots of every preset and the final budget (PRD.md §6.3, §11):
 * later edits to presets never change an existing test.
 */
class TestSessionService
{
    /** @param array<string, mixed> $data validated by CreateTestRequest */
    public function create(User $user, array $data): TestSession
    {
        $assets = Asset::whereIn('id', $data['asset_ids'])->get()->keyBy('id');
        $toolConstraint = null;

        // Mixed scenarios need one authoring tool (PRD.md §6.2). The UI blocks it too; this is the backend rule.
        if (($data['scenario'] ?? null) === 'mixed') {
            $tools = $assets->pluck('tool_id')->unique();
            if ($tools->count() !== 1) {
                throw ValidationException::withMessages(['asset_ids' => 'A mixed scenario needs assets from one authoring tool.']);
            }
            $toolConstraint = $tools->first();
        }

        $configuration = $data['configuration'];
        foreach (['resolution', 'camera', 'lighting', 'background', 'instance_steps'] as $type) {
            $id = $configuration["{$type}_id"] ?? null;
            if ($id !== null) {
                $preset = OptionPreset::where('type', $type)->findOrFail($id);
                $configuration[$type] = ['id' => $preset->id, 'name' => $preset->name, 'values' => $preset->values_json];
            }
            unset($configuration["{$type}_id"]);
        }

        if ($data['method'] === 'stress') {
            $configuration['steps'] = array_map('intval', array_values($configuration['steps']));
            $configuration['instance_mode'] = 'clone'; // InstancedMesh mode is a later version (BENCHMARK.md §8.4)
        }

        if ($data['method'] === 'headroom') {
            $configuration = array_merge($configuration, [
                'cap' => (int) $configuration['cap'],
                'probe_warmup_s' => 1,
                'probe_window_s' => 4,
                'time_limit_s' => 180,
                'instance_mode' => 'clone',
            ]);
            unset($configuration['warmup_s'], $configuration['measurement_s']);
        }

        $preset = BudgetPreset::findOrFail($data['budget']['preset_id']);
        $items = [];
        foreach (MetricDefinitions::BUDGET_ITEMS as $key => $definition) {
            $override = $data['budget']['items'][$key] ?? null;
            $items[$key] = $override !== null
                ? ['enabled' => (bool) $override['enabled'], 'limit' => $override['limit'] === null ? null : (float) $override['limit']]
                : ($preset->items_json[$key] ?? ['enabled' => false, 'limit' => null]);
        }

        // The headroom pass criterion needs both limits (BENCHMARK.md §4.4).
        if ($data['method'] === 'headroom') {
            foreach (['max_p95_frame_ms' => 'p95 frame interval', 'max_jank_pct' => 'jank'] as $key => $label) {
                if (! ($items[$key]['enabled'] ?? false) || $items[$key]['limit'] === null) {
                    throw ValidationException::withMessages(["budget.items.{$key}" => "The Headroom Test needs the {$label} budget item: it is part of the pass criterion."]);
                }
            }
        }

        return DB::transaction(function () use ($user, $data, $configuration, $preset, $items, $toolConstraint) {
            $test = TestSession::create([
                'name' => $data['name'],
                'method' => $data['method'],
                'scenario' => $data['scenario'] ?? null,
                'tool_constraint_id' => $toolConstraint,
                'configuration_json' => $configuration,
                'budget_json' => ['preset_id' => $preset->id, 'preset_name' => $preset->name, 'items' => $items],
                'created_by' => $user->id,
            ]);
            foreach (array_values($data['asset_ids']) as $position => $assetId) {
                $test->assets()->attach($assetId, ['position' => $position + 1]);
            }

            return $test;
        });
    }
}
