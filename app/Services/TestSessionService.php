<?php

namespace App\Services;

use App\Models\OptionPreset;
use App\Models\Target;
use App\Models\TestSession;
use App\Models\User;
use App\Support\MetricDefinitions;
use Illuminate\Support\Facades\DB;

/**
 * Creates a test session with snapshots of every preset and the final Target values (PRD.md §6.3, §11):
 * later edits to presets or Targets never change an existing test.
 */
class TestSessionService
{
    /** @param array<string, mixed> $data validated by CreateTestRequest */
    public function create(User $user, array $data): TestSession
    {
        $configuration = $data['configuration'];
        foreach (['resolution', 'camera', 'lighting', 'background'] as $type) {
            $id = $configuration["{$type}_id"] ?? null;
            $preset = $id !== null
                ? OptionPreset::where('type', $type)->findOrFail($id)
                : OptionPreset::where('type', $type)->where('is_default', true)->first();
            $configuration[$type] = $preset ? ['id' => $preset->id, 'name' => $preset->name, 'values' => $preset->values_json] : null;
            unset($configuration["{$type}_id"]);
        }
        // Fixed run order of the motion scenarios: Static, Spin, Physics (BENCHMARK.md §4.2).
        $configuration['motions'] = array_values(array_intersect(array_keys(MetricDefinitions::MOTIONS), $configuration['motions']));
        $configuration['repetitions'] = (int) $configuration['repetitions'];
        $configuration['animation'] = (bool) ($configuration['animation'] ?? false);
        if ($data['method'] === 'crowd') {
            // Fixed probe protocol (BENCHMARK.md §4.6); InstancedMesh mode is a later version (§8.4).
            $configuration = array_merge($configuration, [
                'cap' => (int) $configuration['cap'],
                'probe_warmup_s' => 1,
                'physics_probe_warmup_s' => 2,
                'probe_window_s' => 4,
                'time_limit_s' => 180,
                'instance_mode' => 'clone',
            ]);
            unset($configuration['warmup_s'], $configuration['measurement_s']);
        } else {
            $configuration['warmup_s'] = (int) $configuration['warmup_s'];
            $configuration['measurement_s'] = (int) $configuration['measurement_s'];
        }
        // Per tool: tool order and object order are the selection order of the groups and of the round-robin.
        $members = [];
        if (($data['scenario'] ?? null) === 'per_tool') {
            $configuration['tool_ids'] = array_map('intval', $data['tool_ids']);
            $configuration['object_ids'] = array_map(fn ($object) => (int) $object['object_id'], $data['objects']);
            foreach ($configuration['tool_ids'] as $toolId) {
                foreach ($data['objects'] as $object) {
                    $members[] = ['asset_id' => (int) $object['assets'][$toolId], 'group_tool_id' => $toolId];
                }
            }
        } else {
            $members = array_map(fn ($id) => ['asset_id' => (int) $id, 'group_tool_id' => null], array_values($data['asset_ids']));
        }

        // The Target as selected, with the values the user adjusted for this test.
        $target = [
            ...Target::findOrFail($data['target']['target_id'])->snapshot(),
            'max_load_s' => (float) $data['target']['max_load_s'],
            'target_fps' => (int) $data['target']['target_fps'],
            'max_file_size_mb' => (float) $data['target']['max_file_size_mb'],
        ];

        return DB::transaction(function () use ($user, $data, $configuration, $target, $members) {
            $test = TestSession::create([
                'name' => $data['name'],
                'method' => $data['method'],
                'scenario' => $data['method'] === 'crowd' ? $data['scenario'] : null,
                'configuration_json' => $configuration,
                'target_json' => $target,
                'created_by' => $user->id,
            ]);
            foreach ($members as $position => $member) {
                $test->assets()->attach($member['asset_id'], ['position' => $position + 1, 'group_tool_id' => $member['group_tool_id']]);
            }

            return $test;
        });
    }
}
