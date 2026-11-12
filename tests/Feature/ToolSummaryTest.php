<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetObject;
use App\Models\DeviceClass;
use App\Models\TestRun;
use App\Models\TestSession;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Tool summary (PROJECT.md M4.5, BENCHMARK.md §4.7). */
class ToolSummaryTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private DeviceClass $desktop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->desktop = DeviceClass::where('name', 'Desktop — Dedicated GPU')->firstOrFail();
        Asset::query()->update(['deleted_at' => now()]); // fixtures in render_lab_verify must not take part
    }

    private function asset(string $object, string $tool, int $size, int $triangles): Asset
    {
        $uuid = (string) Str::uuid();
        $asset = Asset::create([
            'name' => "{$object} {$tool}", 'tool_id' => Tool::where('name', $tool)->value('id'), 'object_id' => AssetObject::firstOrCreate(['name' => "Summary {$object}"])->id,
            'author' => 'A', 'uploaded_by' => $this->user->id, 'production_time_minutes' => 1, 'format' => 'glb',
            'web_file_path' => "assets/web/{$uuid}/{$uuid}.glb", 'file_size' => $size, 'sha256' => hash('sha256', $uuid),
        ]);
        $asset->metrics()->create(['vertices' => $triangles, 'triangles' => $triangles, 'meshes' => 1, 'materials' => 1, 'textures' => 0, 'texture_memory_estimate_bytes' => 0]);

        return $asset;
    }

    /** A completed Side-by-Side run: cold ttff per repetition and the "load" check outcome per asset. */
    private function sideBySide(array $values, ?User $owner = null, ?DeviceClass $class = null): TestSession
    {
        $owner ??= $this->user;
        $test = TestSession::create(['name' => 'SbS '.Str::random(4), 'method' => 'side_by_side', 'created_by' => $owner->id,
            'configuration_json' => ['motions' => ['static'], 'repetitions' => 2], 'target_json' => ['name' => 'Desktop', 'target_fps' => 60]]);
        $run = TestRun::create(['test_session_id' => $test->id, 'device_class_id' => ($class ?? $this->desktop)->id, 'status' => 'completed', 'started_at' => now(), 'completed_at' => now(), 'created_by' => $owner->id]);
        foreach ($values as $position => [$asset, $ttffs, $loadOutcome]) {
            $test->assets()->attach($asset->id, ['position' => $position + 1]);
            foreach ($ttffs as $i => $ttff) {
                $run->results()->create(['asset_id' => $asset->id, 'motion' => null, 'repetition' => $i + 1, 'metrics_json' => ['cold' => ['ttff_ms' => $ttff]]]);
            }
            $run->checks()->create(['asset_id' => $asset->id, 'check_key' => 'load', 'limit_value' => 3, 'measured_value' => 1, 'outcome' => $loadOutcome]);
            $run->checks()->create(['asset_id' => $asset->id, 'check_key' => 'smooth', 'motion' => 'static', 'limit_value' => 60, 'measured_value' => 60, 'label' => 'smooth', 'outcome' => 'pass']);
        }

        return $test;
    }

    /** A completed Crowd Per asset run: smooth copies per asset (Static). */
    private function crowd(array $values): TestSession
    {
        $test = TestSession::create(['name' => 'Crowd '.Str::random(4), 'method' => 'crowd', 'scenario' => 'per_asset', 'created_by' => $this->user->id,
            'configuration_json' => ['motions' => ['static'], 'repetitions' => 1, 'cap' => 1024], 'target_json' => ['name' => 'Desktop', 'target_fps' => 60]]);
        $run = TestRun::create(['test_session_id' => $test->id, 'device_class_id' => $this->desktop->id, 'status' => 'completed', 'started_at' => now(), 'completed_at' => now(), 'created_by' => $this->user->id]);
        foreach ($values as $position => [$asset, $copies]) {
            $test->assets()->attach($asset->id, ['position' => $position + 1]);
            $run->results()->create(['asset_id' => $asset->id, 'motion' => 'static', 'repetition' => 1, 'metrics_json' => ['smooth_copies' => $copies]]);
        }

        return $test;
    }

    private function url(array $tools, array $exclude = []): string
    {
        return '/results/tools?'.http_build_query(['device_class' => $this->desktop->id, 'tools' => array_map(fn ($t) => Tool::where('name', $t)->value('id'), $tools), 'exclude' => $exclude]);
    }

    public function test_medians_check_counts_and_copies_match_a_manual_calculation(): void
    {
        $birdB = $this->asset('Bird', 'Blender', 1000, 100);
        $cupB = $this->asset('Cup', 'Blender', 3000, 300);
        $fishB = $this->asset('Fish', 'Blender', 5000, 500);
        $birdS = $this->asset('Bird', 'Spline', 2000, 200);
        $cupS = $this->asset('Cup', 'Spline', 4000, 400);
        $fishS = $this->asset('Fish', 'Spline', 900, 900);
        $this->asset('Lamp', 'Blender', 10, 10); // missing in Spline

        // Blender: Bird 150 ms (pass), Cup 400 ms (fail), Fish not tested. Spline: 60, 90, 1100 ms, all pass.
        $blenderTest = $this->sideBySide([[$birdB, [100, 200], 'pass'], [$cupB, [300, 500], 'fail']]);
        $splineTest = $this->sideBySide([[$birdS, [50, 70], 'pass'], [$cupS, [80, 100], 'pass'], [$fishS, [1000, 1200], 'pass']]);
        $crowdTest = $this->crowd([[$birdB, 100], [$cupB, 300], [$fishB, 500], [$birdS, 200], [$cupS, 50]]);
        // Ignored: another user's test, and a test on another device class.
        $this->sideBySide([[$fishB, [1, 1], 'pass']], User::factory()->create());
        $this->sideBySide([[$fishB, [2, 2], 'pass']], null, DeviceClass::where('name', 'iPhone — Standard')->firstOrFail());

        $response = $this->actingAs($this->user)->get($this->url(['Blender', 'Spline']))->assertOk()
            ->assertSee('Summary Lamp')->assertSee('missing in Spline')
            ->assertSee('Side-by-Side: Not tested')
            ->assertSee(route('results.show', $blenderTest), false)->assertSee(route('results.show', $splineTest), false)->assertSee(route('results.show', $crowdTest), false);
        $summary = $response->viewData('summary');
        $blender = $summary['tools'][Tool::where('name', 'Blender')->value('id')];
        $spline = $summary['tools'][Tool::where('name', 'Spline')->value('id')];

        $this->assertSame([3, 3000, 300, 275, 2], [$blender['objects'], $blender['file_size'], $blender['triangles'], $blender['cold_ms'], $blender['tested']]);
        $this->assertSame([3, 2000, 400, 90, 3], [$spline['objects'], $spline['file_size'], $spline['triangles'], $spline['cold_ms'], $spline['tested']]);
        $this->assertSame([1, 2], [$blender['checks']['load']['pass'], $blender['checks']['load']['total']]);
        $this->assertSame([3, 3], [$spline['checks']['load']['pass'], $spline['checks']['load']['total']]);
        $this->assertSame([300, 125], [$blender['copies']['static'], $spline['copies']['static']]);
        $this->assertSame([$spline['id']], $summary['markers']['fastest_load']['winners']);
        $this->assertSame([$spline['id']], $summary['markers']['smallest_file']['winners']);
        $this->assertSame([$blender['id']], $summary['markers']['most_copies']['static']['winners']);
        $response->assertSee('1 of 2 pass')->assertSee('3 of 3 pass');
    }

    public function test_an_object_can_be_left_out_and_the_missing_one_is_never_included(): void
    {
        $this->asset('Bird', 'Blender', 1000, 100);
        $this->asset('Cup', 'Blender', 3000, 300);
        $this->asset('Bird', 'Spline', 2000, 200);
        $cupS = $this->asset('Cup', 'Spline', 4000, 400);
        $this->asset('Lamp', 'Blender', 10, 10);

        $summary = $this->actingAs($this->user)->get($this->url(['Blender', 'Spline'], [$cupS->object_id]))->assertOk()->viewData('summary');

        $this->assertSame(['Summary Bird'], array_column($summary['objects'], 'name'));
        $this->assertSame(['Summary Cup'], array_column($summary['excluded'], 'name'));
        $this->assertSame(['Summary Lamp'], array_column($summary['missing'], 'name'));
        $this->assertSame(1, $summary['tools'][Tool::where('name', 'Blender')->value('id')]['objects']);
    }

    public function test_the_page_asks_for_a_device_class_and_two_tools(): void
    {
        $this->actingAs($this->user)->get('/results/tools')->assertOk()->assertSee('Select a device class and at least two authoring tools.');
        $this->get('/results/tools?device_class='.$this->desktop->id.'&tools[]='.Tool::where('name', 'Blender')->value('id'))->assertOk()->assertSee('Select a device class and at least two authoring tools.');
        $this->get('/lab')->assertSee('href="'.route('results.tools').'"', false);
    }
}
