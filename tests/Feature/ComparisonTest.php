<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\BudgetPreset;
use App\Models\DeviceClass;
use App\Models\TestRun;
use App\Models\TestSession;
use App\Models\Tool;
use App\Models\User;
use App\Services\RunResultService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Comparison Test (PROJECT.md M4.2): selector search, configuration, run config, results view. */
class ComparisonTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Asset::query()->update(['deleted_at' => now()]); // fixtures in render_lab_verify must not affect searches
    }

    private function asset(string $name, string $tool = 'Blender', array $attributes = []): Asset
    {
        $uuid = (string) Str::uuid();
        $asset = Asset::create($attributes + [
            'name' => $name, 'tool_id' => Tool::where('name', $tool)->value('id'), 'author' => 'Author '.$name,
            'uploaded_by' => $this->user->id, 'production_time_hours' => 1, 'format' => 'glb',
            'web_file_path' => "assets/web/{$uuid}/{$uuid}.glb", 'file_size' => 100000, 'sha256' => hash('sha256', $uuid),
        ]);
        $asset->metrics()->create(['vertices' => 30, 'triangles' => 10, 'meshes' => 1, 'materials' => 1, 'textures' => 0, 'texture_memory_estimate_bytes' => 0]);

        return $asset;
    }

    /** A completed comparison test, as if the browser had run it. */
    private function completedTest(array $assets, array $p95ByAsset): TestSession
    {
        $test = TestSession::create([
            'name' => 'Three models', 'method' => 'comparison', 'created_by' => $this->user->id,
            'configuration_json' => ['repetitions' => 3, 'warmup_s' => 2, 'measurement_s' => 10, 'resolution' => ['name' => '1280×720', 'values' => ['width' => 1280, 'height' => 720]]],
            'budget_json' => ['preset_id' => BudgetPreset::first()->id, 'preset_name' => 'Desktop — Standard', 'items' => BudgetPreset::where('name', 'Desktop — Standard')->value('items_json')],
        ]);
        foreach ($assets as $position => $asset) {
            $test->assets()->attach($asset->id, ['position' => $position + 1]);
        }
        $run = TestRun::create(['test_session_id' => $test->id, 'device_class_id' => DeviceClass::first()->id, 'status' => 'running', 'started_at' => now(), 'created_by' => $this->user->id, 'environment_json' => ['user_agent' => 'Test UA', 'gpu_renderer' => 'Test GPU']]);
        $results = [];
        foreach ($assets as $asset) {
            foreach ($p95ByAsset[$asset->name] as $repetition => $p95) {
                $results[] = ['asset_id' => $asset->id, 'repetition' => $repetition + 1,
                    'metrics' => ['frames' => 600, 'interval_p50_ms' => 16.6, 'interval_p95_ms' => $p95, 'jank_pct' => 0.1, 'draw_calls' => 3, 'work_p95_ms' => 1.2, 'gpu_p95_ms' => null, 'fps_avg' => 60],
                    'load' => ['cold' => ['fetch_ms' => 50, 'parse_ms' => 5, 'gpu_upload_ms' => 10, 'first_frame_ms' => 16, 'ttff_ms' => 81, 'transfer_size' => 100000], 'warm' => ['ttff_ms' => 20, 'transfer_size' => 0]],
                    'samples' => ['interval' => [16.6, 16.7, 16.8], 'work' => [1, 1, 1], 'gpu' => null, 'truncated' => false]];
            }
        }
        app(RunResultService::class)->submit($run, ['environment' => ['refresh_hz_estimate' => 60, 'three_revision' => '186', 'shuffle_seed' => 42], 'results' => $results]);

        return $test;
    }

    // ---------- Asset Selector search (GET /api/assets) ----------

    public function test_selector_search_by_name_tool_author_and_format(): void
    {
        $this->asset('Bird One');
        $this->asset('Bird Two', 'Spline');
        $this->asset('Chair', 'Blender', ['format' => 'gltf']);
        $this->actingAs($this->user);
        $names = fn (string $query) => collect($this->getJson('/api/assets?'.$query)->assertOk()->json('assets'))->pluck('name')->all();

        $this->assertSame(['Bird One', 'Bird Two'], $names('q=bird'));
        $this->assertSame(['Bird Two'], $names('tool='.Tool::where('name', 'Spline')->value('id')));
        $this->assertSame(['Chair'], $names('format=gltf'));
        $this->assertSame(['Bird One'], $names('author=Author+Bird+One'));
    }

    public function test_selector_results_carry_what_the_list_shows_and_exclude_deleted_assets(): void
    {
        $this->asset('Visible');
        $this->asset('Gone')->delete();

        $asset = $this->actingAs($this->user)->getJson('/api/assets')->assertOk()->json('assets.0');

        $this->assertSame('Visible', $asset['name']);
        $this->assertSame(['id', 'name', 'tool_id', 'tool', 'author', 'format', 'size', 'triangles', 'thumbnail_url', 'file_url'], array_keys($asset));
        $this->assertSame(10, $asset['triangles']);
        $this->assertCount(1, $this->getJson('/api/assets')->json('assets'));
    }

    public function test_guests_cannot_search_assets(): void
    {
        $this->getJson('/api/assets')->assertUnauthorized();
    }

    // ---------- Configuration page ----------

    public function test_the_configuration_page_has_the_selector_and_real_presets(): void
    {
        $this->actingAs($this->user)->get('/lab/comparison')->assertOk()
            ->assertSee('id="asset-selector"', false)
            ->assertSee('Select all visible')
            ->assertSee('name="configuration[resolution_id]"', false)
            ->assertSee('1280×720')
            ->assertSee('<script type="application/json" id="lab-config">', false)
            ->assertDontSee('Sample data');
    }

    // ---------- Run config for the benchmark page ----------

    public function test_the_run_config_lists_assets_in_selection_order_with_file_urls(): void
    {
        $second = $this->asset('Second');
        $first = $this->asset('First');
        $this->actingAs($this->user)->post('/device', ['device_class_id' => DeviceClass::where('name', 'iPhone — Standard')->value('id')]);
        $testId = $this->postJson('/api/tests', [
            'name' => 'Order', 'method' => 'comparison', 'asset_ids' => [$second->id, $first->id],
            'configuration' => ['repetitions' => 3, 'warmup_s' => 1, 'measurement_s' => 5],
            'budget' => ['preset_id' => BudgetPreset::first()->id],
        ])->assertCreated()->json('id');
        $runUrl = $this->postJson("/api/tests/{$testId}/runs", ['environment' => ['user_agent' => 'Test']])->assertCreated()->json('url');

        $config = $this->get($runUrl)->assertOk()->viewData('config');

        $this->assertSame(['Second', 'First'], collect($config['assets'])->pluck('name')->all());
        $this->assertStringContainsString('/files/', $config['assets'][0]['url']);
        $this->assertSame('phone', $config['device_category'], 'Cool-down depends on the device category');
        $this->assertSame(route('results.show', $testId), $config['endpoints']['results']);
    }

    // ---------- Results view ----------

    public function test_results_show_medians_spread_load_phases_and_the_budget_in_selection_order(): void
    {
        $apple = $this->asset('Apple');
        $book = $this->asset('Book', 'Spline');
        $test = $this->completedTest([$book, $apple], ['Book' => [16.0, 30.0, 16.5], 'Apple' => [20.0, 21.0, 22.0]]);

        $response = $this->actingAs($this->user)->get("/results/{$test->id}")->assertOk();

        $response->assertSeeInOrder(['Budget result', 'Book', 'Apple', 'Runtime metrics']);
        $response->assertSee('16.5 (16–30)')->assertSee('Unstable');
        $response->assertSee('Load phases (median, cold · warm)')->assertSee('81 · 20');
        $response->assertSee('1 of 2 assets meet all enabled budget items.');
        $response->assertSee('data-chart="histogram"', false);
        $response->assertSee('Test GPU')->assertSee('r186');
    }

    public function test_results_are_for_the_owner_and_admins(): void
    {
        $test = $this->completedTest([$this->asset('Only')], ['Only' => [16.0, 16.0, 16.0]]);

        $this->actingAs(User::factory()->create())->get("/results/{$test->id}")->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get("/results/{$test->id}")->assertOk();
    }

    public function test_an_invalid_run_shows_its_reason_and_no_budget(): void
    {
        $asset = $this->asset('Invalid one');
        $test = TestSession::create(['name' => 'Broken', 'method' => 'comparison', 'created_by' => $this->user->id, 'configuration_json' => ['repetitions' => 1], 'budget_json' => ['items' => []]]);
        $test->assets()->attach($asset->id, ['position' => 1]);
        TestRun::create(['test_session_id' => $test->id, 'device_class_id' => DeviceClass::first()->id, 'status' => 'invalid', 'invalid_reason' => 'The tab was hidden during measurement.', 'created_by' => $this->user->id]);

        $this->actingAs($this->user)->get("/results/{$test->id}")->assertOk()
            ->assertSee('The last run is invalid: The tab was hidden during measurement.')
            ->assertDontSee('Budget result');
    }
}
