<?php

namespace Tests\Feature;

use App\Jobs\ExpireTestRuns;
use App\Models\Asset;
use App\Models\BudgetPreset;
use App\Models\DeviceClass;
use App\Models\OptionPreset;
use App\Models\TestRun;
use App\Models\TestSession;
use App\Models\Tool;
use App\Models\User;
use App\Services\RunResultService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Run lifecycle (BENCHMARK.md §4.1, §5), device confirmation (§7.1), budget step (§6). */
class LabRunTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function asset(string $tool = 'Blender', array $attributes = []): Asset
    {
        $uuid = (string) Str::uuid();
        $asset = Asset::create($attributes + [
            'name' => 'Lab asset '.Str::random(4), 'tool_id' => Tool::where('name', $tool)->value('id'), 'author' => 'A',
            'uploaded_by' => $this->user->id, 'production_time_hours' => 1, 'format' => 'glb',
            'web_file_path' => "assets/web/{$uuid}/{$uuid}.glb", 'file_size' => 2 * 1048576, 'sha256' => hash('sha256', $uuid),
        ]);
        $asset->metrics()->create(['vertices' => 10, 'triangles' => 5, 'meshes' => 1, 'materials' => 1, 'textures' => 0, 'texture_memory_estimate_bytes' => 0]);

        return $asset;
    }

    private function payloadFor(array $assetIds, array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'Lab test',
            'method' => 'comparison',
            'asset_ids' => $assetIds,
            'configuration' => [
                'repetitions' => 3, 'warmup_s' => 2, 'measurement_s' => 10, 'animation' => false,
                'resolution_id' => OptionPreset::where('type', 'resolution')->where('is_default', true)->value('id'),
                'camera_id' => OptionPreset::where('type', 'camera')->where('is_default', true)->value('id'),
            ],
            'budget' => [
                'preset_id' => BudgetPreset::where('name', 'Desktop — Standard')->value('id'),
                'items' => ['max_draw_calls' => ['enabled' => true, 'limit' => 2]],
            ],
        ], $overrides);
    }

    private function createTest(array $assetIds, array $overrides = []): TestSession
    {
        $id = $this->actingAs($this->user)->postJson('/api/tests', $this->payloadFor($assetIds, $overrides))->assertCreated()->json('id');

        return TestSession::findOrFail($id);
    }

    private function confirmDevice(string $name = 'Desktop — Dedicated GPU'): DeviceClass
    {
        $class = DeviceClass::where('name', $name)->firstOrFail();
        $this->actingAs($this->user)->post('/device', ['device_class_id' => $class->id])->assertRedirect();

        return $class;
    }

    private function startRun(TestSession $test): TestRun
    {
        $id = $this->postJson("/api/tests/{$test->id}/runs", ['environment' => ['user_agent' => 'Test', 'three_revision' => '186']])->assertCreated()->json('id');

        return TestRun::findOrFail($id);
    }

    private function resultRow(int $assetId, int $repetition, float $p95, array $extra = []): array
    {
        return [
            'asset_id' => $assetId,
            'repetition' => $repetition,
            'metrics' => ['frames' => 600, 'interval_p50_ms' => 16.6, 'interval_p95_ms' => $p95, 'interval_p99_ms' => $p95 + 1, 'fps_avg' => 60, 'fps_1pct_low' => 50,
                'jank_count' => 0, 'jank_pct' => 0.2, 'work_p50_ms' => 1, 'work_p95_ms' => 2, 'gpu_p50_ms' => null, 'gpu_p95_ms' => null,
                'draw_calls' => 3, 'rendered_triangles' => 5, 'programs' => 2, 'geometries' => 2, 'textures_in_memory' => 0, 'js_heap_used' => 1000000],
            'load' => ['cold' => ['fetch_ms' => 100, 'parse_ms' => 10, 'gpu_upload_ms' => 5, 'first_frame_ms' => 16, 'ttff_ms' => 131 + $repetition], 'warm' => ['ttff_ms' => 40]],
            'samples' => ['interval' => [16.6, 16.7], 'work' => [1, 2], 'gpu' => null, 'truncated' => false],
        ] + $extra;
    }

    // ---------- Create test: snapshots and rules ----------

    public function test_creating_a_test_stores_snapshots_of_presets_and_the_final_budget(): void
    {
        $asset = $this->asset();
        $test = $this->createTest([$asset->id]);

        $this->assertSame('1280×720', $test->configuration_json['resolution']['name']);
        $this->assertSame(['width' => 1280, 'height' => 720], $test->configuration_json['resolution']['values']);
        $this->assertSame('Desktop — Standard', $test->budget_json['preset_name']);
        $this->assertEquals(['enabled' => true, 'limit' => 2.0], $test->budget_json['items']['max_draw_calls'], 'Adjusted value');
        $this->assertEquals(16.7, $test->budget_json['items']['max_p95_frame_ms']['limit'], 'Preset value');
        $this->assertSame([$asset->id], $test->assets->pluck('id')->all());

        // A later preset edit does not change the test.
        BudgetPreset::where('name', 'Desktop — Standard')->first()->update(['items_json' => []]);
        $this->assertEquals(16.7, $test->fresh()->budget_json['items']['max_p95_frame_ms']['limit']);
    }

    public function test_a_mixed_scenario_needs_one_authoring_tool(): void
    {
        $this->actingAs($this->user)->postJson('/api/tests', $this->payloadFor(
            [$this->asset('Blender')->id, $this->asset('Spline')->id],
            ['method' => 'stress', 'scenario' => 'mixed', 'configuration' => ['steps' => [1, 5]]],
        ))->assertUnprocessable()->assertJsonValidationErrors('asset_ids');
    }

    public function test_deleted_assets_and_unknown_budget_items_are_rejected(): void
    {
        $gone = $this->asset();
        $gone->delete();

        $this->actingAs($this->user)->postJson('/api/tests', $this->payloadFor([$gone->id], ['budget' => ['items' => ['max_score' => ['enabled' => true, 'limit' => 1]]]]))
            ->assertUnprocessable()->assertJsonValidationErrors(['asset_ids.0', 'budget.items.max_score']);
    }

    // ---------- Device class ----------

    public function test_a_run_needs_a_confirmed_device_class(): void
    {
        $test = $this->createTest([$this->asset()->id]);

        $this->postJson("/api/tests/{$test->id}/runs", ['environment' => []])->assertUnprocessable()->assertJsonValidationErrors('device_class');

        $class = $this->confirmDevice();
        $run = $this->startRun($test);
        $this->assertSame([$class->id, 'running'], [$run->device_class_id, $run->status]);
    }

    public function test_device_confirmation_is_stored_in_the_session_and_shown_in_the_header(): void
    {
        $this->actingAs($this->user)->get('/dashboard')->assertSee('Device class not set');

        $this->confirmDevice('iPhone — Standard');

        $this->get('/dashboard')->assertSee('iPhone — Standard')->assertDontSee('Device class not set');
        $this->get('/device')->assertOk()->assertSee('Confirm device class');
    }

    public function test_device_confirmation_validates_the_class_and_the_return_path(): void
    {
        $inactive = DeviceClass::create(['name' => 'Retired class', 'category' => 'phone', 'platform' => 'any', 'is_active' => false]);

        $this->actingAs($this->user)->post('/device', ['device_class_id' => $inactive->id])->assertSessionHasErrors('device_class_id');
        $this->post('/device', ['device_class_id' => DeviceClass::first()->id, 'return_to' => 'https://evil.example'])->assertSessionHasErrors('return_to');
        $this->post('/device', ['device_class_id' => DeviceClass::first()->id, 'return_to' => '//evil.example'])->assertSessionHasErrors('return_to');
        $this->post('/device', ['device_class_id' => DeviceClass::first()->id, 'return_to' => '/lab/comparison'])->assertRedirect('/lab/comparison');
    }

    public function test_the_budget_step_suggests_the_preset_linked_to_the_confirmed_device_class(): void
    {
        $this->confirmDevice('iPhone — Standard');
        $midRange = BudgetPreset::where('name', 'Mobile — Mid-range')->value('id');

        $this->get('/lab/comparison')->assertOk()
            ->assertSee('<option value="'.$midRange.'" selected', false)
            ->assertSee('Suggested for the confirmed device class.')
            ->assertSee('Time to first frame, warm')
            ->assertDontSee('Headroom instances');
    }

    // ---------- Benchmark page ----------

    public function test_the_benchmark_page_is_for_the_run_creator_while_running(): void
    {
        $this->confirmDevice();
        $run = $this->startRun($this->createTest([$this->asset()->id]));

        $this->get("/lab/runs/{$run->id}")->assertOk()
            ->assertSee('data-benchmark="running"', false)
            ->assertDontSee('app-sidebar')
            ->assertDontSee('id="toaster"', false);

        $this->actingAs(User::factory()->create())->get("/lab/runs/{$run->id}")->assertForbidden();

        $run->update(['status' => 'completed']);
        $this->actingAs($this->user)->get("/lab/runs/{$run->id}")->assertStatus(410);
    }

    // ---------- Submit, aggregate, evaluate ----------

    public function test_submitting_results_stores_them_and_evaluates_the_budget_on_medians(): void
    {
        $a = $this->asset();
        $b = $this->asset('Spline', ['file_size' => 30 * 1048576]);
        $this->confirmDevice();
        $test = $this->createTest([$a->id, $b->id]);
        $run = $this->startRun($test);

        $response = $this->postJson("/api/runs/{$run->id}/results", ['results' => [
            $this->resultRow($a->id, 1, 16.0), $this->resultRow($a->id, 2, 30.0), $this->resultRow($a->id, 3, 16.5),
            $this->resultRow($b->id, 1, 40.0), $this->resultRow($b->id, 2, 41.0), $this->resultRow($b->id, 3, 42.0),
        ]])->assertOk();

        $this->assertSame('completed', $response->json('status'));
        $run->refresh();
        $this->assertSame('completed', $run->status);
        $this->assertCount(6, $run->results);

        $outcome = fn ($assetId, $key) => $run->evaluations->where('asset_id', $assetId)->firstWhere('item_key', $key);
        // Median p95 of A = 16.5 (not the 30 outlier) → pass against 16.7.
        $this->assertSame('pass', $outcome($a->id, 'max_p95_frame_ms')->outcome);
        $this->assertSame(16.5, $outcome($a->id, 'max_p95_frame_ms')->measured_value);
        $this->assertSame('fail', $outcome($b->id, 'max_p95_frame_ms')->outcome);
        $this->assertSame('fail', $outcome($b->id, 'max_file_size_mb')->outcome);
        $this->assertSame('fail', $outcome($a->id, 'max_draw_calls')->outcome, 'Adjusted limit 2 < 3 draw calls');
        $this->assertSame(133.0, $outcome($a->id, 'max_ttff_cold_ms')->measured_value, 'Median cold TTFF');
        $this->assertNull($outcome($a->id, 'min_headroom_instances'), 'Headroom does not apply to Comparison');
    }

    public function test_result_payloads_are_validated(): void
    {
        $asset = $this->asset();
        $this->confirmDevice();
        $run = $this->startRun($this->createTest([$asset->id]));

        $tooMany = $this->resultRow($asset->id, 1, 16);
        $tooMany['samples']['interval'] = array_fill(0, 3601, 16.6);
        $this->postJson("/api/runs/{$run->id}/results", ['results' => [$tooMany]])->assertJsonValidationErrors('results.0.samples.interval');

        $foreign = $this->asset();
        $this->postJson("/api/runs/{$run->id}/results", ['results' => [$this->resultRow($foreign->id, 1, 16)]])->assertJsonValidationErrors('results.0.asset_id');
        $this->postJson("/api/runs/{$run->id}/results", ['results' => [$this->resultRow($asset->id, 4, 16)]])->assertJsonValidationErrors('results.0.repetition');
        $this->postJson("/api/runs/{$run->id}/results", ['results' => [['asset_id' => $asset->id, 'repetition' => 1, 'metrics' => ['frames' => 0]]]])->assertJsonValidationErrors('results.0.metrics.frames');

        $this->assertSame('running', $run->fresh()->status);
    }

    public function test_only_the_creator_can_submit_and_only_while_running(): void
    {
        $asset = $this->asset();
        $this->confirmDevice();
        $run = $this->startRun($this->createTest([$asset->id]));
        $payload = ['results' => [$this->resultRow($asset->id, 1, 16)]];

        $this->actingAs(User::factory()->create())->postJson("/api/runs/{$run->id}/results", $payload)->assertForbidden();

        $this->actingAs($this->user)->postJson("/api/runs/{$run->id}/cancel")->assertOk()->assertJson(['status' => 'cancelled']);
        $this->postJson("/api/runs/{$run->id}/results", $payload)->assertJsonValidationErrors('run');
    }

    public function test_an_expired_run_cannot_submit(): void
    {
        $asset = $this->asset();
        $this->confirmDevice();
        $run = $this->startRun($this->createTest([$asset->id]));
        $run->update(['started_at' => now()->subMinutes(config('lab.run_expiry_minutes') + 1)]);

        $this->postJson("/api/runs/{$run->id}/results", ['results' => [$this->resultRow($asset->id, 1, 16)]])->assertJsonValidationErrors('run');
    }

    public function test_an_invalidated_run_keeps_its_reason_and_has_no_budget_results(): void
    {
        $this->confirmDevice();
        $run = $this->startRun($this->createTest([$this->asset()->id]));

        $this->postJson("/api/runs/{$run->id}/invalidate", ['reason' => 'The tab was hidden during measurement.'])->assertOk();

        $run->refresh();
        $this->assertSame(['invalid', 'The tab was hidden during measurement.'], [$run->status, $run->invalid_reason]);
        $this->assertSame(0, $run->evaluations()->count());
        $this->postJson("/api/runs/{$run->id}/invalidate", ['reason' => 'again'])->assertForbidden();
    }

    public function test_the_expiry_job_invalidates_stale_running_runs(): void
    {
        $this->confirmDevice();
        $test = $this->createTest([$this->asset()->id]);
        $stale = $this->startRun($test);
        $fresh = $this->startRun($test);
        $stale->update(['started_at' => now()->subMinutes(config('lab.run_expiry_minutes') + 5)]);

        app(ExpireTestRuns::class)->handle(app(RunResultService::class));

        $this->assertSame(['invalid', 'expired'], [$stale->fresh()->status, $stale->fresh()->invalid_reason]);
        $this->assertSame('running', $fresh->fresh()->status);
    }

    public function test_another_user_cannot_start_a_run_on_my_test(): void
    {
        $test = $this->createTest([$this->asset()->id]);
        $other = User::factory()->create();
        $this->actingAs($other)->post('/device', ['device_class_id' => DeviceClass::first()->id]);

        $this->postJson("/api/tests/{$test->id}/runs", ['environment' => []])->assertForbidden();
    }
}
