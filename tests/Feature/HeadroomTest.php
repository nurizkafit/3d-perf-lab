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

/** Headroom Test (PROJECT.md M4.4, BENCHMARK.md §4.4). The search itself is tested in tests/js. */
class HeadroomTest extends TestCase
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

    private function payload(array $assetIds, string $scenario = 'repeated', array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'Headroom', 'method' => 'headroom', 'scenario' => $scenario, 'asset_ids' => $assetIds,
            'configuration' => ['repetitions' => 3, 'cap' => 1024],
            'budget' => ['preset_id' => BudgetPreset::where('name', 'Desktop — Standard')->value('id')],
        ], $overrides);
    }

    private function startRun(array $assetIds, string $scenario = 'repeated', array $overrides = []): TestRun
    {
        $testId = $this->postJson('/api/tests', $this->payload($assetIds, $scenario, $overrides))->assertCreated()->json('id');

        return TestRun::findOrFail($this->postJson("/api/tests/{$testId}/runs", ['environment' => ['user_agent' => 'Test']])->assertCreated()->json('id'));
    }

    private function row(?int $assetId, int $repetition, int $headroom, bool $cap = false, bool $time = false): array
    {
        return [
            'asset_id' => $assetId, 'repetition' => $repetition,
            'metrics' => ['frames' => 240, 'interval_p95_ms' => 16.6, 'jank_pct' => 0.5, 'headroom_instances' => $headroom],
            'probes' => [['instances' => 1, 'pass' => true, 'interval_p95_ms' => 16.6, 'jank_pct' => 0], ['instances' => 2, 'pass' => true], ['instances' => 4, 'pass' => $cap]],
            'flags' => ['cap_reached' => $cap, 'time_limit_reached' => $time],
        ];
    }

    public function test_creating_a_headroom_test_stores_the_cap_and_the_fixed_probe_protocol(): void
    {
        $id = $this->postJson('/api/tests', $this->payload([$this->asset('A')->id, $this->asset('B', 'Spline')->id]))->assertCreated()->json('id');
        $c = TestSession::find($id)->configuration_json;

        $this->assertSame([1024, 1, 4, 180, 'clone'], [$c['cap'], $c['probe_warmup_s'], $c['probe_window_s'], $c['time_limit_s'], $c['instance_mode']]);
        $this->assertArrayNotHasKey('warmup_s', $c);
    }

    public function test_the_cap_is_required_and_at_most_4096(): void
    {
        $asset = $this->asset('A');
        $this->postJson('/api/tests', $this->payload([$asset->id], 'repeated', ['configuration' => ['cap' => 5000]]))->assertJsonValidationErrors('configuration.cap');
        $this->postJson('/api/tests', $this->payload([$asset->id], 'repeated', ['configuration' => ['cap' => null]]))->assertJsonValidationErrors('configuration.cap');
    }

    public function test_the_pass_criterion_items_must_be_enabled(): void
    {
        $this->postJson('/api/tests', $this->payload([$this->asset('A')->id], 'repeated', ['budget' => ['items' => ['max_jank_pct' => ['enabled' => false, 'limit' => null]]]]))
            ->assertJsonValidationErrors(['budget.items.max_jank_pct' => 'The Headroom Test needs the jank budget item: it is part of the pass criterion.']);
    }

    public function test_a_mixed_headroom_test_needs_one_tool(): void
    {
        $this->postJson('/api/tests', $this->payload([$this->asset('A')->id, $this->asset('B', 'Spline')->id], 'mixed'))->assertJsonValidationErrors('asset_ids');
    }

    public function test_results_store_probes_and_flags_and_evaluate_the_median_headroom(): void
    {
        $light = $this->asset('Light');
        $heavy = $this->asset('Heavy');
        $run = $this->startRun([$light->id, $heavy->id]);

        $this->postJson("/api/runs/{$run->id}/results", ['results' => [
            $this->row($light->id, 1, 900), $this->row($light->id, 2, 1024, cap: true), $this->row($light->id, 3, 880),
            $this->row($heavy->id, 1, 30), $this->row($heavy->id, 2, 34), $this->row($heavy->id, 3, 32, time: true),
        ]])->assertOk();

        $run->refresh();
        $stored = $run->results->firstWhere('asset_id', $light->id)->metrics_json;
        $this->assertCount(3, $stored['probes']);
        $this->assertSame(['cap_reached' => false, 'time_limit_reached' => false], $stored['flags']);

        $headroom = fn ($asset) => $run->evaluations->where('asset_id', $asset->id)->firstWhere('item_key', 'min_headroom_instances');
        $this->assertSame([900.0, 'pass'], [$headroom($light)->measured_value, $headroom($light)->outcome], 'Median of 880/900/1024 against ≥ 50');
        $this->assertSame([32.0, 'fail'], [$headroom($heavy)->measured_value, $headroom($heavy)->outcome]);
        $this->assertNull($run->evaluations->firstWhere('item_key', 'max_ttff_cold_ms'), 'Load items do not apply');
    }

    public function test_a_result_without_the_headroom_value_is_rejected(): void
    {
        $asset = $this->asset('A');
        $run = $this->startRun([$asset->id]);

        $this->postJson("/api/runs/{$run->id}/results", ['results' => [['asset_id' => $asset->id, 'repetition' => 1, 'metrics' => ['frames' => 10]]]])
            ->assertJsonValidationErrors('results.0.metrics.headroom_instances');
    }

    public function test_the_results_page_shows_cap_time_limit_probes_and_selection_order(): void
    {
        $b = $this->asset('Bravo');
        $a = $this->asset('Alpha');
        $run = $this->startRun([$b->id, $a->id]);
        $this->postJson("/api/runs/{$run->id}/results", ['results' => [
            $this->row($b->id, 1, 1024, cap: true), $this->row($b->id, 2, 1024, cap: true), $this->row($b->id, 3, 1024, cap: true),
            $this->row($a->id, 1, 40, time: true), $this->row($a->id, 2, 44), $this->row($a->id, 3, 42),
        ]])->assertOk();

        $this->get('/results/'.$run->test_session_id)->assertOk()
            ->assertSeeInOrder(['Headroom (median', 'Bravo', 'Alpha'])
            ->assertSee('≥ 1,024')
            ->assertSee('Cap reached (1,024)')
            ->assertSee('Time limit (3 min)')
            ->assertSee('42 (40–44)')
            ->assertSee('Pass 1')->assertSee('Fail 4')
            ->assertSee('data-chart="probes"', false)
            ->assertSee('1 s warm-up, 4 s window');
    }

    public function test_a_mixed_set_is_one_search_and_one_budget_column(): void
    {
        $a = $this->asset('One');
        $b = $this->asset('Two');
        $run = $this->startRun([$a->id, $b->id], 'mixed');
        $this->postJson("/api/runs/{$run->id}/results", ['results' => [$this->row(null, 1, 60), $this->row(null, 2, 64), $this->row(null, 3, 62)]])->assertOk();

        $this->get('/results/'.$run->test_session_id)->assertOk()
            ->assertSee('Mixed set: One, Two')
            ->assertSee('62 (60–64)');
        $this->assertSame('pass', $run->fresh()->evaluations->whereNull('asset_id')->firstWhere('item_key', 'min_headroom_instances')->outcome);
    }

    public function test_the_run_page_describes_the_probe_protocol(): void
    {
        $testId = $this->postJson('/api/tests', $this->payload([$this->asset('A')->id]))->assertCreated()->json('id');
        $runUrl = $this->postJson("/api/tests/{$testId}/runs", ['environment' => ['user_agent' => 'Test']])->assertCreated()->json('url');

        $this->get($runUrl)->assertOk()
            ->assertSee('cap 1,024 instances')
            ->assertSee('1 s warm-up and a 4 s window; each search stops after 3 min');
    }

    public function test_the_configuration_page_has_cap_and_scenarios(): void
    {
        $this->get('/lab/headroom')->assertOk()
            ->assertSee('Cap (instances)')->assertSee('1 s warm-up and a 4 s window')
            ->assertSee('Repeated asset')->assertSee('Headroom instances')
            ->assertDontSee('name="configuration[warmup_s]"', false);
    }
}
