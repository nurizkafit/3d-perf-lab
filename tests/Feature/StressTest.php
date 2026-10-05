<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\BudgetPreset;
use App\Models\DeviceClass;
use App\Models\TestRun;
use App\Models\TestSession;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Stress Test (PROJECT.md M4.3, BENCHMARK.md §4.3, decision D37). */
class StressTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user)->post('/device', ['device_class_id' => DeviceClass::where('name', 'Desktop — Dedicated GPU')->value('id')]);
    }

    private function asset(string $name, string $tool = 'Blender'): Asset
    {
        $uuid = (string) Str::uuid();
        $asset = Asset::create([
            'name' => $name, 'tool_id' => Tool::where('name', $tool)->value('id'), 'author' => 'A', 'uploaded_by' => $this->user->id,
            'production_time_hours' => 1, 'format' => 'glb', 'web_file_path' => "assets/web/{$uuid}/{$uuid}.glb", 'file_size' => 1048576, 'sha256' => hash('sha256', $uuid),
        ]);
        $asset->metrics()->create(['vertices' => 3, 'triangles' => 1, 'meshes' => 1, 'materials' => 1, 'textures' => 0, 'texture_memory_estimate_bytes' => 0]);

        return $asset;
    }

    private function payload(array $assetIds, string $scenario, array $steps = [1, 5, 10]): array
    {
        return [
            'name' => 'Stress', 'method' => 'stress', 'scenario' => $scenario, 'asset_ids' => $assetIds,
            'configuration' => ['repetitions' => 2, 'warmup_s' => 1, 'measurement_s' => 5, 'steps' => $steps],
            'budget' => ['preset_id' => BudgetPreset::where('name', 'Desktop — Standard')->value('id'), 'items' => ['min_instances_at_budget' => ['enabled' => true, 'limit' => 5]]],
        ];
    }

    public function test_the_api_rejects_a_mixed_test_with_two_tools(): void
    {
        $this->postJson('/api/tests', $this->payload([$this->asset('A')->id, $this->asset('B', 'Spline')->id], 'mixed'))
            ->assertUnprocessable()->assertJsonValidationErrors(['asset_ids' => 'A mixed scenario needs assets from one authoring tool.']);
    }

    public function test_scenario_asset_counts_and_step_lists_are_validated(): void
    {
        $a = $this->asset('A');
        $b = $this->asset('B');

        $this->postJson('/api/tests', $this->payload([$a->id, $b->id], 'repeated'))->assertJsonValidationErrors('asset_ids');
        $this->postJson('/api/tests', $this->payload([$a->id], 'mixed'))->assertJsonValidationErrors('asset_ids');
        $this->postJson('/api/tests', $this->payload([$a->id], 'repeated', [10, 5]))->assertJsonValidationErrors('configuration.steps');
        $this->postJson('/api/tests', $this->payload([$a->id], 'repeated', [1, 501]))->assertJsonValidationErrors('configuration.steps.1');
        $this->postJson('/api/tests', $this->payload([$a->id], 'repeated', []))->assertJsonValidationErrors('configuration.steps');
    }

    public function test_a_mixed_test_with_one_tool_stores_the_tool_constraint_and_steps(): void
    {
        $a = $this->asset('A');
        $b = $this->asset('B');

        $test = TestSession::findOrFail($this->postJson('/api/tests', $this->payload([$a->id, $b->id], 'mixed', [1, 25, 50]))->assertCreated()->json('id'));

        $this->assertSame(Tool::where('name', 'Blender')->value('id'), $test->tool_constraint_id);
        $this->assertSame([1, 25, 50], $test->configuration_json['steps']);
        $this->assertSame('clone', $test->configuration_json['instance_mode']);
    }

    private function submit(TestRun $run, ?int $assetId = null, array $p95ByStep = [1 => [10, 11], 5 => [15, 16], 10 => [30, 31]]): void
    {
        $results = [];
        foreach ($p95ByStep as $step => $values) {
            foreach ($values as $i => $p95) {
                $results[] = ['asset_id' => $assetId, 'step_instances' => $step, 'repetition' => $i + 1,
                    'metrics' => ['frames' => 300, 'interval_p95_ms' => $p95, 'work_p95_ms' => $step * 0.1, 'jank_pct' => 0, 'draw_calls' => $step * 2],
                    'samples' => ['interval' => [16.6], 'work' => [1], 'gpu' => null, 'truncated' => false]];
            }
        }
        $this->postJson("/api/runs/{$run->id}/results", ['results' => $results, 'environment' => ['renderers_created' => 1, 'scenes_used' => 1]])->assertOk();
    }

    public function test_budget_is_evaluated_at_each_step_and_instances_at_budget_once(): void
    {
        $asset = $this->asset('Solo');
        $testId = $this->postJson('/api/tests', $this->payload([$asset->id], 'repeated'))->json('id');
        $run = TestRun::findOrFail($this->postJson("/api/tests/{$testId}/runs", ['environment' => ['user_agent' => 'Test']])->json('id'));

        $this->submit($run, $asset->id);

        $evaluations = $run->fresh()->evaluations;
        $p95 = $evaluations->where('item_key', 'max_p95_frame_ms')->sortBy('step_instances')->map(fn ($e) => [$e->step_instances, $e->outcome])->values()->all();
        $this->assertSame([[1, 'pass'], [5, 'pass'], [10, 'fail']], $p95, 'Median p95 per step against 16.7 ms');
        $this->assertSame([1, 5, 10], $evaluations->where('item_key', 'max_draw_calls')->pluck('step_instances')->sort()->values()->all(), 'Draw calls evaluated per step');
        $atBudget = $evaluations->firstWhere('item_key', 'min_instances_at_budget');
        $this->assertNull($atBudget->step_instances);
        $this->assertSame(5.0, $atBudget->measured_value, 'Largest step that meets p95');
        $this->assertSame('pass', $atBudget->outcome);
        $this->assertNull($evaluations->firstWhere('item_key', 'max_file_size_mb')->step_instances);
    }

    public function test_results_for_an_unknown_step_are_rejected(): void
    {
        $asset = $this->asset('Solo');
        $testId = $this->postJson('/api/tests', $this->payload([$asset->id], 'repeated'))->json('id');
        $run = TestRun::findOrFail($this->postJson("/api/tests/{$testId}/runs", ['environment' => ['user_agent' => 'Test']])->json('id'));

        $this->postJson("/api/runs/{$run->id}/results", ['results' => [['asset_id' => $asset->id, 'step_instances' => 7, 'repetition' => 1, 'metrics' => ['frames' => 10]]]])
            ->assertJsonValidationErrors('results.0.step_instances');
    }

    public function test_the_results_page_shows_steps_the_step_budget_and_the_chart(): void
    {
        $a = $this->asset('First');
        $b = $this->asset('Second');
        $testId = $this->postJson('/api/tests', $this->payload([$a->id, $b->id], 'mixed'))->json('id');
        $run = TestRun::findOrFail($this->postJson("/api/tests/{$testId}/runs", ['environment' => ['user_agent' => 'Test']])->json('id'));
        $this->submit($run, null);

        $this->get("/results/{$testId}")->assertOk()
            ->assertSee('Mixed set: First, Second')
            ->assertSee('Budget at each step')
            ->assertSee('Instances at budget: 5')
            ->assertSee('15.5 (15–16)')
            ->assertSee('data-chart="stress"', false)
            ->assertSee('1 / 5 / 10');
    }

    public function test_the_configuration_page_has_scenarios_and_step_presets(): void
    {
        $this->get('/lab/stress')->assertOk()
            ->assertSee('Repeated asset')->assertSee('Mixed assets (one tool)')
            ->assertSee('Instance steps preset')->assertSee('Instances at budget')
            ->assertDontSee('Time to first frame, cold');
    }
}
