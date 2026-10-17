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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Crowd Test (PROJECT.md M4.4, BENCHMARK.md §4.6, PRD.md §8.3). */
class CrowdTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Asset::query()->update(['deleted_at' => now()]); // fixtures in render_lab_verify must not affect object matching
    }

    private function tool(string $name): int
    {
        return Tool::where('name', $name)->value('id');
    }

    private function asset(string $object, string $tool, ?string $name = null): Asset
    {
        $uuid = (string) Str::uuid();

        return Asset::create([
            'name' => $name ?? "{$object} {$tool}", 'tool_id' => $this->tool($tool), 'object_id' => AssetObject::firstOrCreate(['name' => "Crowd {$object}"])->id,
            'author' => 'A', 'uploaded_by' => $this->user->id, 'production_time_hours' => 1, 'format' => 'glb',
            'web_file_path' => "assets/web/{$uuid}/{$uuid}.glb", 'file_size' => 1000, 'sha256' => hash('sha256', $uuid),
        ]);
    }

    private function confirmDevice(): void
    {
        $this->actingAs($this->user)->post('/device', ['device_class_id' => DeviceClass::where('name', 'Desktop — Dedicated GPU')->value('id')])->assertRedirect();
    }

    private function payload(array $selection, array $configuration = []): array
    {
        return [
            'name' => 'Crowd', 'method' => 'crowd', ...$selection,
            'configuration' => ['motions' => ['static', 'spin'], 'repetitions' => 1, 'cap' => 64, ...$configuration],
            'target' => ['target_id' => Target::where('name', 'Desktop')->value('id'), 'max_load_s' => 3, 'target_fps' => 60, 'max_file_size_mb' => 20],
        ];
    }

    /** A probe summary: p95 16.7 ms = Smooth at 60 fps, 30 ms = Mostly smooth. */
    private function probe(array $group, string $motion, int $copies, bool $smooth, int $repetition = 1): array
    {
        return [...$group, 'motion' => $motion, 'repetition' => $repetition, 'probe_copies' => $copies,
            'metrics' => ['frames' => 240, 'interval_p95_ms' => $smooth ? 16.7 : 30, 'jank_pct' => 0, 'work_p95_ms' => 1, 'draw_calls' => $copies]];
    }

    private function search(array $group, string $motion, bool $cap = false, int $repetition = 1): array
    {
        return [...$group, 'motion' => $motion, 'repetition' => $repetition, 'probe_copies' => null, 'search' => ['cap_reached' => $cap, 'time_limit_reached' => false]];
    }

    private function startRun(TestSession $test): TestRun
    {
        return TestRun::findOrFail($this->postJson("/api/tests/{$test->id}/runs", ['environment' => ['user_agent' => 'Test']])->assertCreated()->json('id'));
    }

    public function test_the_page_has_both_scenarios_the_explorer_and_the_settings(): void
    {
        $this->actingAs($this->user)->get('/lab/crowd')->assertOk()
            ->assertSee('Per asset')->assertSee('Per tool')->assertSee('data-crowd-copies', false)
            ->assertSee('Cap (instances)')->assertSee('Estimated run time')->assertSee('id="asset-selector"', false);
        $this->get('/lab')->assertSee('href="'.route('lab.crowd').'"', false);
    }

    public function test_common_objects_lists_only_objects_in_every_tool_and_the_missing_ones(): void
    {
        $this->asset('Bird', 'Blender');
        $newest = $this->asset('Bird', 'Spline', 'Bird Spline v2');
        $this->travel(-1)->days();
        $this->asset('Bird', 'Spline', 'Bird Spline v1');
        $this->travelBack();
        $this->asset('Cup', 'Blender');

        $body = $this->actingAs($this->user)->getJson('/api/objects/common?tools='.$this->tool('Blender').','.$this->tool('Spline'))->assertOk()->json();

        $this->assertSame(['Crowd Bird'], array_column($body['objects'], 'name'));
        $this->assertSame($newest->id, $body['objects'][0]['assets'][$this->tool('Spline')][0]['id'], 'Newest asset first (the default)');
        $this->assertSame([['name' => 'Crowd Cup', 'missing_tools' => ['Spline']]], array_map(fn ($m) => array_intersect_key($m, ['name' => 0, 'missing_tools' => 0]), $body['missing']));
    }

    public function test_per_tool_rejects_a_forced_object_that_is_missing_in_a_tool(): void
    {
        $birdBlender = $this->asset('Bird', 'Blender');
        $birdSpline = $this->asset('Bird', 'Spline');
        $cupBlender = $this->asset('Cup', 'Blender');
        $this->actingAs($this->user);
        $blender = $this->tool('Blender');
        $spline = $this->tool('Spline');

        $this->postJson('/api/tests', $this->payload(['scenario' => 'per_tool', 'tool_ids' => [$blender, $spline], 'objects' => [
            ['object_id' => $birdBlender->object_id, 'assets' => [$blender => $birdBlender->id, $spline => $birdSpline->id]],
            ['object_id' => $cupBlender->object_id, 'assets' => [$blender => $cupBlender->id]],
        ]]))->assertUnprocessable()->assertJsonValidationErrors('objects.1');

        // A Spline asset of another object cannot stand in for the missing one.
        $this->postJson('/api/tests', $this->payload(['scenario' => 'per_tool', 'tool_ids' => [$blender, $spline], 'objects' => [
            ['object_id' => $cupBlender->object_id, 'assets' => [$blender => $cupBlender->id, $spline => $birdSpline->id]],
        ]]))->assertUnprocessable()->assertJsonValidationErrors('objects.0');

        $this->postJson('/api/tests', $this->payload(['scenario' => 'per_tool', 'tool_ids' => [$blender], 'objects' => []]))->assertUnprocessable()->assertJsonValidationErrors(['tool_ids', 'objects']);
    }

    public function test_per_asset_limits_and_crowd_settings_are_validated(): void
    {
        $assets = collect(range(1, 9))->map(fn ($i) => $this->asset("O{$i}", 'Blender'));
        $this->actingAs($this->user);

        $this->postJson('/api/tests', $this->payload(['scenario' => 'per_asset', 'asset_ids' => $assets->pluck('id')->all()]))->assertUnprocessable()->assertJsonValidationErrors('asset_ids');
        $this->postJson('/api/tests', $this->payload(['scenario' => 'per_asset', 'asset_ids' => [$assets[0]->id]], ['repetitions' => 4]))->assertUnprocessable()->assertJsonValidationErrors('configuration.repetitions');
        $this->postJson('/api/tests', $this->payload(['scenario' => 'per_asset', 'asset_ids' => [$assets[0]->id]], ['cap' => 5000]))->assertUnprocessable()->assertJsonValidationErrors('configuration.cap');

        $id = $this->postJson('/api/tests', $this->payload(['scenario' => 'per_asset', 'asset_ids' => [$assets[0]->id]]))->assertCreated()->json('id');
        $configuration = TestSession::find($id)->configuration_json;
        $this->assertSame([64, 1, 4, 180, 'clone'], [$configuration['cap'], $configuration['probe_warmup_s'], $configuration['probe_window_s'], $configuration['time_limit_s'], $configuration['instance_mode']]);
    }

    public function test_per_tool_stores_one_group_per_tool_in_object_order(): void
    {
        $birdB = $this->asset('Bird', 'Blender');
        $birdS = $this->asset('Bird', 'Spline');
        $cupB = $this->asset('Cup', 'Blender');
        $cupS = $this->asset('Cup', 'Spline');
        $this->confirmDevice();
        [$blender, $spline] = [$this->tool('Blender'), $this->tool('Spline')];

        $id = $this->postJson('/api/tests', $this->payload(['scenario' => 'per_tool', 'tool_ids' => [$spline, $blender], 'objects' => [
            ['object_id' => $cupB->object_id, 'assets' => [$blender => $cupB->id, $spline => $cupS->id]],
            ['object_id' => $birdB->object_id, 'assets' => [$blender => $birdB->id, $spline => $birdS->id]],
        ]]))->assertCreated()->json('id');
        $test = TestSession::find($id);

        $config = $this->get('/lab/runs/'.$this->startRun($test)->id)->assertOk()->viewData('config');
        $this->assertSame(['Spline', 'Blender'], collect($config['groups'])->pluck('label')->all());
        $this->assertSame([$cupS->id, $birdS->id], collect($config['groups'][0]['assets'])->pluck('id')->all());
        $this->assertSame([2, 2], $test->crowdShape());
    }

    public function test_the_server_derives_smooth_copies_from_the_probes_and_shows_most_copies(): void
    {
        $light = $this->asset('Light', 'Blender');
        $heavy = $this->asset('Heavy', 'Blender');
        $this->confirmDevice();
        $test = TestSession::find($this->postJson('/api/tests', $this->payload(['scenario' => 'per_asset', 'asset_ids' => [$heavy->id, $light->id]], ['motions' => ['static']]))->json('id'));
        $run = $this->startRun($test);
        $l = ['asset_id' => $light->id];
        $h = ['asset_id' => $heavy->id];

        $results = [
            // Light: Smooth up to the cap (64).
            ...array_map(fn ($c) => $this->probe($l, 'static', $c, true), [1, 2, 4, 8, 16, 32, 64]), $this->search($l, 'static', true),
            // Heavy: 1, 2, 4 Smooth; 8 not; binary 6 Smooth, 7 not → 6.
            $this->probe($h, 'static', 1, true), $this->probe($h, 'static', 2, true), $this->probe($h, 'static', 4, true),
            $this->probe($h, 'static', 8, false), $this->probe($h, 'static', 6, true), $this->probe($h, 'static', 7, false), $this->search($h, 'static'),
        ];
        $this->postJson("/api/runs/{$run->id}/results", ['results' => $results, 'environment' => ['refresh_hz_estimate' => 60]])->assertOk();

        $search = fn (Asset $asset) => $run->results()->where('asset_id', $asset->id)->whereNull('probe_copies')->first()->metrics_json;
        $this->assertSame([64, true, true], [$search($light)['smooth_copies'], $search($light)['at_cap'], $search($light)['cap_reached']]);
        $this->assertSame([6, false, 6], [$search($heavy)['smooth_copies'], $search($heavy)['at_cap'], $search($heavy)['probes']]);
        $this->assertSame('mostly_smooth', $run->results()->where('asset_id', $heavy->id)->where('probe_copies', 8)->first()->metrics_json['label']);
        $this->assertSame(0, $run->checks()->count(), 'The Crowd has no Target checks (D46)');

        $response = $this->get("/results/{$test->id}")->assertOk()
            ->assertSeeInOrder(['Heavy', 'Light'])->assertSee('Smooth up to 6 copies')->assertSee('Smooth up to ≥ 64 copies')
            ->assertSee('Most copies')->assertSee('Reached the cap')->assertSee('data-probe-chart', false)->assertSee('data-crowd-explorer', false);
        $this->assertSame([$light->id], $response->viewData('results')['markers']['static']['winners']);
    }

    public function test_two_results_at_the_cap_are_too_close_to_call(): void
    {
        $a = $this->asset('A', 'Blender');
        $b = $this->asset('B', 'Blender');
        $this->confirmDevice();
        $test = TestSession::find($this->postJson('/api/tests', $this->payload(['scenario' => 'per_asset', 'asset_ids' => [$a->id, $b->id]], ['motions' => ['static'], 'cap' => 2]))->json('id'));
        $run = $this->startRun($test);
        $results = [];
        foreach ([$a, $b] as $asset) {
            $results = [...$results, $this->probe(['asset_id' => $asset->id], 'static', 1, true), $this->probe(['asset_id' => $asset->id], 'static', 2, true), $this->search(['asset_id' => $asset->id], 'static', true)];
        }
        $this->postJson("/api/runs/{$run->id}/results", ['results' => $results])->assertOk();

        $markers = $this->get("/results/{$test->id}")->assertOk()->assertSee('Too close to call')->viewData('results')['markers'];
        $this->assertSame(['winners' => [], 'too_close' => true], $markers['static']);
    }

    public function test_incomplete_or_over_the_cap_results_are_rejected(): void
    {
        $a = $this->asset('A', 'Blender');
        $this->confirmDevice();
        $test = TestSession::find($this->postJson('/api/tests', $this->payload(['scenario' => 'per_asset', 'asset_ids' => [$a->id]], ['cap' => 8]))->json('id'));
        $run = $this->startRun($test);
        $g = ['asset_id' => $a->id];

        $this->postJson("/api/runs/{$run->id}/results", ['results' => [$this->probe($g, 'static', 1, true), $this->search($g, 'static')]])
            ->assertUnprocessable()->assertJsonValidationErrors('results');
        $this->postJson("/api/runs/{$run->id}/results", ['results' => [$this->probe($g, 'static', 16, true), $this->search($g, 'static'), $this->probe($g, 'spin', 1, true), $this->search($g, 'spin')]])
            ->assertUnprocessable()->assertJsonValidationErrors('results.0.probe_copies');
        $this->postJson("/api/runs/{$run->id}/results", ['results' => [$this->probe(['asset_id' => 999999], 'static', 1, true)]])
            ->assertUnprocessable()->assertJsonValidationErrors('results.0');
        $this->assertSame('running', $run->fresh()->status);
    }

    public function test_a_crowd_run_expires_after_its_maximum_estimate_plus_15_minutes(): void
    {
        $assets = collect(range(1, 4))->map(fn ($i) => $this->asset("E{$i}", 'Blender'));
        $this->confirmDevice();
        $test = TestSession::find($this->postJson('/api/tests', $this->payload(['scenario' => 'per_asset', 'asset_ids' => $assets->pluck('id')->all()], ['motions' => ['static', 'spin', 'physics'], 'repetitions' => 3]))->json('id'));
        $run = $this->startRun($test);

        // 3 × 4 × (185.5 + 185.5 + 186.5 + 4 + 3 × 3) = 6,846 s → 115 min + 15 = 130 min.
        $this->assertSame(6846, $run->estimatedSeconds());
        $this->assertSame(130, (int) round($run->started_at->diffInMinutes($run->expiresAt())));
    }
}
