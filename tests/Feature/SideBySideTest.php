<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetObject;
use App\Models\DeviceClass;
use App\Models\Target;
use App\Models\TestRun;
use App\Models\TestSession;
use App\Models\Tool;
use App\Models\User;
use App\Services\RunResultService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Side-by-Side Test (PROJECT.md M4.3, BENCHMARK.md §4.3–§4.5, §5, §6): page, test creation, run, results. */
class SideBySideTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function asset(string $name, int $fileSize = 1048576, string $tool = 'Blender'): Asset
    {
        $uuid = (string) Str::uuid();
        $asset = Asset::create([
            'name' => $name, 'tool_id' => Tool::where('name', $tool)->value('id'),
            'object_id' => AssetObject::firstOrCreate(['name' => 'Bird'])->id, 'author' => 'Author '.$name,
            'uploaded_by' => $this->user->id, 'production_time_hours' => 1, 'format' => 'glb',
            'web_file_path' => "assets/web/{$uuid}/{$uuid}.glb", 'file_size' => $fileSize, 'sha256' => hash('sha256', $uuid),
        ]);
        $asset->metrics()->create(['vertices' => 30, 'triangles' => 10, 'meshes' => 1, 'materials' => 1, 'textures' => 0, 'texture_memory_estimate_bytes' => 0]);

        return $asset;
    }

    private function confirmDevice(string $name = 'Desktop — Dedicated GPU'): void
    {
        $this->actingAs($this->user)->post('/device', ['device_class_id' => DeviceClass::where('name', $name)->value('id')])->assertRedirect();
    }

    private function payload(array $assetIds, array $overrides = []): array
    {
        $target = Target::where('name', 'Desktop')->firstOrFail();

        $payload = array_replace_recursive([
            'name' => 'Three birds',
            'method' => 'side_by_side',
            'asset_ids' => $assetIds,
            'configuration' => ['motions' => ['physics', 'static', 'spin'], 'repetitions' => 3, 'warmup_s' => 2, 'measurement_s' => 10, 'animation' => false],
            'target' => ['target_id' => $target->id, 'max_load_s' => 3, 'target_fps' => 60, 'max_file_size_mb' => 20],
        ], $overrides);
        // Lists replace the default list instead of merging by index.
        if (isset($overrides['configuration']['motions'])) {
            $payload['configuration']['motions'] = $overrides['configuration']['motions'];
        }

        return $payload;
    }

    private function createTest(array $assets, array $overrides = []): TestSession
    {
        $id = $this->postJson('/api/tests', $this->payload(array_map(fn ($a) => $a->id, $assets), $overrides))->assertCreated()->json('id');

        return TestSession::findOrFail($id);
    }

    private function startRun(TestSession $test): TestRun
    {
        $id = $this->postJson("/api/tests/{$test->id}/runs", ['environment' => ['user_agent' => 'Test UA', 'gpu_renderer' => 'Test GPU']])->assertCreated()->json('id');

        return TestRun::findOrFail($id);
    }

    /**
     * Results as the browser sends them. $values[asset name] = per repetition: [ttff_ms, p95_ms, jank_pct, work_p95_ms],
     * where work_p95_ms is one number for every motion or an array per motion.
     */
    private function results(TestSession $test, array $values): array
    {
        $results = [];
        foreach ($test->assets as $asset) {
            foreach ($values[$asset->name] as $index => [$ttff, $p95, $jank, $work]) {
                $repetition = $index + 1;
                $results[] = ['asset_id' => $asset->id, 'motion' => null, 'repetition' => $repetition,
                    'load' => ['cold' => ['fetch_ms' => $ttff * 0.6, 'parse_ms' => $ttff * 0.1, 'gpu_upload_ms' => $ttff * 0.2, 'first_frame_ms' => $ttff * 0.1, 'ttff_ms' => $ttff, 'transfer_size' => 1000],
                        'warm' => ['ttff_ms' => $ttff / 4, 'transfer_size' => 0]],
                    'collider' => ['points' => 31, 'faces' => 58, 'collider_build_ms' => 6]];
                foreach ($test->configuration_json['motions'] as $motion) {
                    $results[] = ['asset_id' => $asset->id, 'motion' => $motion, 'repetition' => $repetition,
                        'metrics' => ['frames' => 600, 'interval_p50_ms' => 16.7, 'interval_p95_ms' => $p95, 'jank_pct' => $jank, 'work_p95_ms' => is_array($work) ? $work[$motion] : $work,
                            'physics_p95_ms' => $motion === 'physics' ? 1.2 : null, 'gpu_p95_ms' => null, 'draw_calls' => 2, 'fps_avg' => 60, 'unknown_key' => 5],
                        'samples' => ['interval' => [16.7, 16.6], 'work' => [0.5, 0.6], 'physics' => null, 'gpu' => null, 'truncated' => false]];
                }
            }
        }

        return $results;
    }

    private function submit(TestRun $run, array $results)
    {
        return $this->postJson("/api/runs/{$run->id}/results", ['results' => $results, 'environment' => ['refresh_hz_estimate' => 60, 'shuffle_seed' => 42, 'dom_mutations_during_windows' => 0]]);
    }

    // ---------- Page ----------

    public function test_the_page_has_the_steps_selector_look_controls_and_real_presets(): void
    {
        $this->actingAs($this->user)->get('/lab/side-by-side')->assertOk()
            ->assertSee('id="asset-selector"', false)
            ->assertSee('Motion scenarios')->assertSee('Static')->assertSee('Spin')->assertSee('Physics')
            ->assertSee('data-panes-shake', false)->assertSee('Reset view')
            ->assertSee('id="target-select"', false)
            ->assertSee('Estimated run time')
            ->assertSee('<script type="application/json" id="lab-config">', false)
            ->assertDontSee('Sample data');
        $this->get('/lab')->assertSee('href="'.route('lab.side-by-side').'"', false);
    }

    public function test_guests_cannot_open_the_page_or_create_tests(): void
    {
        $this->get('/lab/side-by-side')->assertRedirect('/login');
        $this->postJson('/api/tests', [])->assertUnauthorized();
    }

    // ---------- Test creation ----------

    public function test_a_test_needs_two_to_four_assets_and_at_least_one_motion(): void
    {
        $assets = collect(range(1, 5))->map(fn ($i) => $this->asset("A{$i}"))->all();
        $this->actingAs($this->user);
        $ids = array_map(fn ($a) => $a->id, $assets);

        $this->postJson('/api/tests', $this->payload([$ids[0]]))->assertUnprocessable()->assertJsonValidationErrors('asset_ids');
        $this->postJson('/api/tests', $this->payload($ids))->assertUnprocessable()->assertJsonValidationErrors('asset_ids');
        $this->postJson('/api/tests', array_replace($this->payload([$ids[0], $ids[1]]), ['configuration' => ['motions' => [], 'repetitions' => 3, 'warmup_s' => 2, 'measurement_s' => 10]]))
            ->assertUnprocessable()->assertJsonValidationErrors('configuration.motions');
        $this->postJson('/api/tests', $this->payload([$ids[0], $ids[1]], ['configuration' => ['motions' => ['orbit']]]))->assertUnprocessable()->assertJsonValidationErrors('configuration.motions.0');
        $this->postJson('/api/tests', $this->payload([$ids[0], $ids[1]], ['configuration' => ['measurement_s' => 31]]))->assertUnprocessable()->assertJsonValidationErrors('configuration.measurement_s');
        $this->postJson('/api/tests', $this->payload([$ids[0], $ids[1]], ['target' => ['target_fps' => 5]]))->assertUnprocessable()->assertJsonValidationErrors('target.target_fps');

        $assets[1]->delete();
        $this->postJson('/api/tests', $this->payload([$ids[0], $ids[1]]))->assertUnprocessable()->assertJsonValidationErrors('asset_ids.1');
    }

    public function test_a_test_stores_selection_order_ordered_motions_preset_snapshots_and_the_adjusted_target(): void
    {
        $second = $this->asset('Second');
        $first = $this->asset('First');
        $this->actingAs($this->user);

        $test = $this->createTest([$second, $first], ['target' => ['max_load_s' => 2.5]]);

        $this->assertSame(['Second', 'First'], $test->assets->pluck('name')->all());
        $this->assertSame(['static', 'spin', 'physics'], $test->configuration_json['motions'], 'Fixed run order: Static, Spin, Physics');
        $this->assertSame(['width' => 1280, 'height' => 720], array_intersect_key($test->configuration_json['resolution']['values'], ['width' => 0, 'height' => 0]));
        $this->assertSame(['Desktop', 2.5, 60], [$test->target_json['name'], $test->target_json['max_load_s'], $test->target_json['target_fps']]);

        Target::where('name', 'Desktop')->update(['max_load_s' => 9]);
        $this->assertSame(2.5, $test->fresh()->target_json['max_load_s'], 'Later Target edits never change a test');
    }

    // ---------- Run page ----------

    public function test_the_run_page_shows_the_panes_in_selection_order_for_the_creator_only(): void
    {
        $b = $this->asset('Bird B');
        $a = $this->asset('Bird A');
        $this->confirmDevice('iPhone — Standard');
        $test = $this->createTest([$b, $a]);
        $run = $this->startRun($test);

        $response = $this->get("/lab/runs/{$run->id}")->assertOk()->assertSeeInOrder(['Bird B', 'Bird A'])->assertSee('Measuring')->assertSee('Estimated run time');
        $config = $response->viewData('config');
        $this->assertSame(['Bird B', 'Bird A'], collect($config['assets'])->pluck('name')->all());
        $this->assertStringContainsString('/files/', $config['assets'][0]['url']);
        $this->assertSame(10, $config['cooldown_s'], 'Phones cool down 10 s');
        $this->assertSame(route('api.runs.submit', $run), $config['endpoints']['submit']);

        $this->actingAs(User::factory()->create())->get("/lab/runs/{$run->id}")->assertForbidden();
    }

    // ---------- Submit and evaluate ----------

    public function test_submitting_stores_results_labels_and_target_checks_from_the_medians(): void
    {
        $fast = $this->asset('Fast', 2 * 1048576);
        $slow = $this->asset('Slow', 30 * 1048576);
        $this->confirmDevice();
        $test = $this->createTest([$fast, $slow]);
        $run = $this->startRun($test);

        // Fast: Smooth everywhere, loads in 0.4 s. Slow: one outlier repetition does not change the median;
        // p95 25 ms at 60 fps (T = 16.7 ms) is Mostly smooth; ttff 3.5 s fails "Loads in time"; 30 MB fails File size.
        $this->submit($run, $this->results($test, [
            'Fast' => [[400, 16.8, 0, 0.5], [420, 16.9, 0, 0.6], [410, 16.7, 0, 0.5]],
            'Slow' => [[3500, 25, 0.5, 4], [3600, 25.5, 0.5, 4.2], [900, 16.7, 0, 1]],
        ]))->assertOk()->assertJson(['status' => 'completed', 'url' => route('results.show', $test)]);

        $run->refresh();
        $this->assertSame('completed', $run->status);
        $this->assertSame(60, $run->environment_json['refresh_hz_estimate']);
        $this->assertSame(2 * 3 * 4, $run->results()->count(), '2 assets × 3 repetitions × (load + 3 motions)');
        $this->assertArrayNotHasKey('unknown_key', $run->results()->whereNotNull('motion')->first()->metrics_json);

        $checks = fn (Asset $asset) => $run->checks()->where('asset_id', $asset->id)->get()->mapWithKeys(fn ($c) => [$c->check_key.($c->motion ? ':'.$c->motion : '') => [$c->outcome, $c->label]])->all();
        $this->assertSame([
            'load' => ['pass', null], 'smooth:static' => ['pass', 'smooth'], 'smooth:spin' => ['pass', 'smooth'], 'smooth:physics' => ['pass', 'smooth'], 'file_size' => ['pass', null],
        ], $checks($fast));
        $this->assertSame([
            'load' => ['fail', null], 'smooth:static' => ['fail', 'mostly_smooth'], 'smooth:spin' => ['fail', 'mostly_smooth'], 'smooth:physics' => ['fail', 'mostly_smooth'], 'file_size' => ['fail', null],
        ], $checks($slow));
    }

    public function test_incomplete_foreign_or_duplicate_results_are_rejected(): void
    {
        $a = $this->asset('A');
        $b = $this->asset('B');
        $other = $this->asset('Not in the test');
        $this->confirmDevice();
        $test = $this->createTest([$a, $b], ['configuration' => ['motions' => ['static'], 'repetitions' => 1]]);
        $run = $this->startRun($test);
        $good = $this->results($test, ['A' => [[400, 16.7, 0, 0.5]], 'B' => [[400, 16.7, 0, 0.5]]]);

        $this->submit($run, array_slice($good, 0, 3))->assertUnprocessable()->assertJsonValidationErrors('results');
        $foreign = $good;
        $foreign[0]['asset_id'] = $other->id;
        $this->submit($run, $foreign)->assertUnprocessable()->assertJsonValidationErrors('results.0');
        $this->submit($run, [...$good, $good[1]])->assertUnprocessable()->assertJsonValidationErrors('results.4');
        $wrongMotion = $good;
        $wrongMotion[1]['motion'] = 'spin';
        $this->submit($run, $wrongMotion)->assertUnprocessable()->assertJsonValidationErrors('results.1');

        $this->assertSame('running', $run->fresh()->status);
        $this->actingAs(User::factory()->create())->postJson("/api/runs/{$run->id}/results", ['results' => $good])->assertForbidden();
        $this->actingAs($this->user);
        $this->submit($run, $good)->assertOk();
        $this->submit($run, $good)->assertUnprocessable()->assertJsonValidationErrors('run');
    }

    // ---------- Expiry (decision D59) ----------

    public function test_a_long_run_expires_after_its_estimate_plus_15_minutes_and_a_short_run_after_30(): void
    {
        $assets = collect(range(1, 4))->map(fn ($i) => $this->asset("L{$i}"))->all();
        $this->confirmDevice();
        $long = $this->startRun($this->createTest($assets, ['configuration' => ['repetitions' => 5, 'warmup_s' => 10, 'measurement_s' => 30]]));
        $short = $this->startRun($this->createTest(array_slice($assets, 0, 2)));

        // Long: 5 × 4 × (4 + 3 × (10 + 30) + 3) = 2,540 s → 43 min + 15 = 58 min.
        $this->assertSame(2540, $long->estimatedSeconds());
        $this->assertSame(58, (int) round($long->started_at->diffInMinutes($long->expiresAt())));
        $this->assertSame(30, (int) round($short->started_at->diffInMinutes($short->expiresAt())));

        TestRun::whereKey([$long->id, $short->id])->update(['started_at' => now()->subMinutes(40)]);
        app(RunResultService::class)->expireStale();
        $this->assertSame('running', $long->fresh()->status);
        $this->assertSame(['invalid', 'expired'], [$short->fresh()->status, $short->fresh()->invalid_reason]);

        $this->get("/lab/runs/{$long->id}")->assertOk();
        $this->get("/lab/runs/{$short->id}")->assertStatus(410);
    }

    // ---------- Results view ----------

    public function test_the_results_show_labels_checks_and_best_markers_in_selection_order(): void
    {
        $heavy = $this->asset('Heavy bird', 8 * 1048576);
        $light = $this->asset('Light bird', 1048576);
        $this->confirmDevice();
        $test = $this->createTest([$heavy, $light], ['configuration' => ['motions' => ['static', 'spin']]]);
        $run = $this->startRun($test);
        // Light loads faster with no overlap → Fastest load. Work p95 overlaps in Static → Too close to call;
        // in Spin Light is clearly lighter → Lightest to draw · Spin.
        $this->submit($run, $this->results($test, [
            'Heavy bird' => [[900, 16.7, 0, ['static' => 0.9, 'spin' => 2.0]], [950, 16.7, 0, ['static' => 1.0, 'spin' => 2.1]], [1000, 16.7, 0, ['static' => 1.1, 'spin' => 2.2]]],
            'Light bird' => [[300, 16.7, 0, ['static' => 1.0, 'spin' => 0.5]], [320, 16.7, 0, ['static' => 0.3, 'spin' => 0.6]], [310, 16.7, 0, ['static' => 0.4, 'spin' => 0.5]]],
        ]))->assertOk();

        $response = $this->get("/results/{$test->id}")->assertOk()
            ->assertSeeInOrder(['Heavy bird', 'Light bird'])
            ->assertSee('Static: Smooth · 60 fps')
            ->assertSee('Fastest load')->assertSee('Smallest file')->assertSee('Lightest to draw · Spin')->assertSee('Too close to call')
            ->assertSee('Loads in time')->assertSee('Meets all Target checks: Yes')
            ->assertSee('data-panes-config', false)->assertSee('Technical details')->assertSee('Tested assets');

        $results = $response->viewData('results');
        $this->assertSame([$light->id], $results['markers']['fastest_load']['winners']);
        $this->assertTrue($results['markers']['lightest']['static']['too_close']);
        $this->assertSame([$light->id], $results['markers']['lightest']['spin']['winners']);
        $this->assertSame([$heavy->id, $light->id], array_keys($results['assets']), 'Markers never re-sort the results');

        $this->actingAs(User::factory()->create())->get("/results/{$test->id}")->assertForbidden();
    }

    public function test_an_invalid_run_produces_no_results_and_the_page_shows_the_reason(): void
    {
        $this->confirmDevice();
        $test = $this->createTest([$this->asset('A'), $this->asset('B')]);
        $run = $this->startRun($test);
        $this->postJson("/api/runs/{$run->id}/invalidate", ['reason' => 'The tab was hidden during measurement.'])->assertOk();

        $this->assertSame(0, $run->results()->count() + $run->checks()->count());
        $this->get("/results/{$test->id}")->assertOk()->assertSee('The tab was hidden during measurement.')->assertSee('This test has no completed run yet.');
    }
}
