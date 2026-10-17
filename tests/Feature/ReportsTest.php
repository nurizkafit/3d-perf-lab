<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetObject;
use App\Models\DeviceClass;
use App\Models\Report;
use App\Models\TestRun;
use App\Models\TestSession;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Results, reports, publishing (PROJECT.md M4.6, PRD.md §10, SITEMAP.md §2, §7). */
class ReportsTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;

    /** @var list<string> */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['email' => 'owner-'.Str::random(6).'@example.test']);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $directory) {
            Storage::disk('local')->deleteDirectory($directory);
        }
        parent::tearDown();
    }

    private function asset(string $name): Asset
    {
        $uuid = (string) Str::uuid();
        $path = "assets/web/{$uuid}/{$uuid}.glb";
        Storage::disk('local')->put($path, "glTF-test-{$name}");
        $this->files[] = "assets/web/{$uuid}";
        $asset = Asset::create([
            'name' => $name, 'tool_id' => Tool::where('name', 'Blender')->value('id'), 'object_id' => AssetObject::firstOrCreate(['name' => 'Report object'])->id,
            'author' => 'Author '.$name, 'uploaded_by' => $this->owner->id, 'production_time_hours' => 1, 'format' => 'glb',
            'web_file_path' => $path, 'file_size' => 2048, 'sha256' => hash('sha256', $uuid),
        ]);
        $asset->metrics()->create(['vertices' => 12, 'triangles' => 20, 'meshes' => 1, 'materials' => 1, 'textures' => 0, 'texture_memory_estimate_bytes' => 0]);

        return $asset;
    }

    /** A Side-by-Side test with a completed run, as if the browser had run it. */
    private function completedTest(bool $completed = true): TestSession
    {
        $test = TestSession::create(['name' => 'Bird test', 'method' => 'side_by_side', 'created_by' => $this->owner->id,
            'configuration_json' => ['motions' => ['static'], 'repetitions' => 1, 'warmup_s' => 2, 'measurement_s' => 10, 'camera' => ['name' => 'Auto-frame', 'values' => ['fov' => 45]]],
            'target_json' => ['target_id' => 1, 'name' => 'Desktop', 'max_load_s' => 3, 'target_fps' => 60, 'max_file_size_mb' => 20]]);
        $run = TestRun::create(['test_session_id' => $test->id, 'device_class_id' => DeviceClass::where('name', 'Desktop — Dedicated GPU')->value('id'),
            'status' => $completed ? 'completed' : 'invalid', 'started_at' => now(), 'completed_at' => now(), 'created_by' => $this->owner->id,
            'environment_json' => ['user_agent' => 'Test UA', 'gpu_renderer' => 'Test GPU', 'refresh_hz_estimate' => 60, 'three_revision' => '186', 'shuffle_seed' => 123456789]]);
        foreach ([$this->asset('Bird A'), $this->asset('Bird B')] as $i => $asset) {
            $test->assets()->attach($asset->id, ['position' => $i + 1]);
            $run->results()->create(['asset_id' => $asset->id, 'motion' => null, 'repetition' => 1, 'metrics_json' => ['cold' => ['ttff_ms' => 100 + $i * 50, 'fetch_ms' => 50]]]);
            $run->results()->create(['asset_id' => $asset->id, 'motion' => 'static', 'repetition' => 1,
                'metrics_json' => ['frames' => 600, 'interval_p95_ms' => 16.7, 'jank_pct' => 0, 'work_p95_ms' => 1], 'samples_json' => ['interval' => [16.66, 16.67], 'work' => [1, 1]]]);
            $run->checks()->create(['asset_id' => $asset->id, 'check_key' => 'load', 'limit_value' => 3, 'measured_value' => 0.1, 'outcome' => 'pass']);
            $run->checks()->create(['asset_id' => $asset->id, 'motion' => 'static', 'check_key' => 'smooth', 'limit_value' => 60, 'measured_value' => 60, 'label' => 'smooth', 'outcome' => 'pass']);
        }

        return $test;
    }

    private function save(TestSession $test, array $data = [])
    {
        return $this->actingAs($this->owner)->put("/results/{$test->id}/report", $data + ['title' => 'Which Bird loads faster?', 'question' => 'Which Bird variant loads faster on desktop?']);
    }

    public function test_a_report_is_private_and_publish_needs_a_question_an_approved_asset_and_a_completed_run(): void
    {
        $test = $this->completedTest();
        $first = $test->assets->first();

        $this->save($test, ['question' => ''])->assertRedirect();
        $report = $test->fresh()->report;
        $this->assertSame('draft', $report->status);
        $this->post("/results/{$test->id}/report/publish")->assertSessionHasErrors('publish');

        $this->save($test)->assertRedirect();
        $this->post("/results/{$test->id}/report/publish")->assertSessionHasErrors('publish'); // no approved asset

        $this->save($test, ['approved' => [$first->id]]);
        $this->post("/results/{$test->id}/report/publish")->assertSessionHasNoErrors();
        $this->assertTrue($report->fresh()->isPublished());

        $invalid = $this->completedTest(false);
        $this->save($invalid, ['approved' => [$invalid->assets->first()->id]]);
        $this->post("/results/{$invalid->id}/report/publish")->assertSessionHasErrors('publish');
    }

    public function test_draft_urls_return_404_and_unpublish_revokes_tokens_at_once(): void
    {
        $test = $this->completedTest();
        [$approved, $notApproved] = [$test->assets[0], $test->assets[1]];
        $this->save($test, ['approved' => [$approved->id]]);
        $this->post("/results/{$test->id}/report/publish");
        $report = $test->fresh()->report;
        $slug = $report->public_slug;
        $token = $report->publicAssets()->first()->pivot->token;
        auth()->logout();

        $this->get("/reports/{$slug}")->assertOk()->assertSee('Which Bird variant loads faster on desktop?')->assertSee('Interactive reproduction');
        $this->get('/reports')->assertSee('Which Bird loads faster?');
        $this->get("/reports/{$slug}/assets/{$token}")->assertOk();
        $this->get("/reports/{$slug}/assets/wrong-token")->assertNotFound();

        $this->actingAs($this->owner)->post("/results/{$test->id}/report/unpublish")->assertRedirect();
        auth()->logout();
        $this->get("/reports/{$slug}")->assertNotFound();
        $this->get("/reports/{$slug}/assets/{$token}")->assertNotFound();
        $this->get('/reports')->assertDontSee('Which Bird loads faster?');

        // Republish: same slug, new token; the old token stays dead.
        $this->actingAs($this->owner)->post("/results/{$test->id}/report/publish");
        auth()->logout();
        $newToken = $report->fresh()->publicAssets()->first()->pivot->token;
        $this->assertSame($slug, $report->fresh()->public_slug);
        $this->assertNotSame($token, $newToken);
        $this->get("/reports/{$slug}/assets/{$token}")->assertNotFound();
        $this->get("/reports/{$slug}/assets/{$newToken}")->assertOk();
        $this->assertNotNull($notApproved);
    }

    public function test_public_html_and_json_contain_none_of_the_forbidden_fields(): void
    {
        $test = $this->completedTest();
        [$approved, $notApproved] = [$test->assets[0], $test->assets[1]];
        $this->save($test, ['approved' => [$approved->id], 'conclusion' => 'Bird A is faster.']);
        $this->post("/results/{$test->id}/report/publish");
        $report = $test->fresh()->report;
        auth()->logout();

        $html = $this->get("/reports/{$report->public_slug}")->assertOk()->getContent();
        $gallery = $this->get('/reports')->getContent();

        foreach ([$html, $gallery] as $page) {
            $this->assertStringNotContainsString($this->owner->email, $page, 'No user email');
            $this->assertStringNotContainsString('assets/web/', $page, 'No storage path');
            $this->assertStringNotContainsString(basename($approved->web_file_path), $page, 'No stored file name');
            $this->assertStringNotContainsString(route('assets.file', [$notApproved, basename($notApproved->web_file_path)]), $page, 'No unapproved asset URL');
            $this->assertStringNotContainsString('/assets/'.$approved->id.'/', $page, 'No private asset URL');
            $this->assertStringNotContainsString('/results/'.$test->id, $page, 'No private result URL');
            $this->assertStringNotContainsString('16.66', $page, 'No raw samples');
            $this->assertStringNotContainsString('123456789', $page, 'No internal run data (seed)');
            $this->assertStringNotContainsString('"asset_id"', $page, 'No database IDs in JSON');
            $this->assertStringNotContainsString('"id":'.$approved->id.',', $page, 'No asset ID in JSON');
            $this->assertStringNotContainsString('Sample data', $page);
        }
        $this->assertStringContainsString('Bird A is faster.', $html);
        $this->assertStringContainsString('"id":"a1"', $html, 'Panes use position keys');
        $this->assertStringContainsString('The author did not publish this model.', $html, 'Unapproved asset: placeholder');
        $this->assertStringContainsString('Results apply to the recorded device and browser only.', $html);
        $this->assertStringNotContainsString('/api/', $html, 'The reproduction never calls the API (never measures, never writes results)');
        $this->assertStringNotContainsString('data-benchmark', $html);
    }

    public function test_only_the_owner_or_an_admin_can_edit_publish_and_export(): void
    {
        $test = $this->completedTest();
        $other = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (["/results/{$test->id}/report", "/results/{$test->id}/export.json", "/results/{$test->id}/export.csv"] as $url) {
            $this->actingAs($other)->get($url)->assertForbidden();
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->actingAs($other)->put("/results/{$test->id}/report", ['title' => 'Mine'])->assertForbidden();
        $this->save($test, ['approved' => [999999]])->assertSessionHasErrors('approved.0');
    }

    public function test_exports_contain_the_raw_data_and_one_csv_row_per_result(): void
    {
        $test = $this->completedTest();

        $json = $this->actingAs($this->owner)->get("/results/{$test->id}/export.json")->assertOk()->json();
        $this->assertSame('Bird test', $json['test']['name']);
        $this->assertSame([16.66, 16.67], $json['runs'][0]['results'][1]['samples_json']['interval']);

        $csv = $this->get("/results/{$test->id}/export.csv")->assertOk()->streamedContent();
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertCount(1 + 4, $lines, 'Header + 2 assets × (load + static)');
        $this->assertStringContainsString('run_id,run_status,device_class,asset', $lines[0]);
    }

    public function test_history_filters_and_delete_rules(): void
    {
        $sbs = $this->completedTest();
        $crowd = TestSession::create(['name' => 'Crowd run', 'method' => 'crowd', 'scenario' => 'per_asset', 'created_by' => $this->owner->id,
            'configuration_json' => ['motions' => ['static'], 'repetitions' => 1, 'cap' => 64], 'target_json' => ['name' => 'Desktop', 'target_fps' => 60]]);

        $this->actingAs($this->owner)->get('/results')->assertOk()->assertSee('Bird test')->assertSee('Crowd run')->assertSee('2 of 2 assets meet all Target checks');
        $this->get('/results?method=crowd')->assertSee('Crowd run')->assertDontSee('Bird test');
        $this->get('/results?device='.DeviceClass::where('name', 'Desktop — Dedicated GPU')->value('id'))->assertSee('Bird test')->assertDontSee('Crowd run');
        $this->get('/results?method=bogus&from=not-a-date')->assertOk()->assertSee('Bird test');
        $this->actingAs(User::factory()->create())->get('/results')->assertDontSee('Bird test');

        // Published report: delete blocked. Unpublished draft: deleted with the test.
        $this->save($sbs, ['approved' => [$sbs->assets[0]->id]]);
        $this->post("/results/{$sbs->id}/report/publish");
        $this->delete("/results/{$sbs->id}")->assertSessionHasErrors('test');
        $this->assertNotNull(TestSession::find($sbs->id));
        $this->post("/results/{$sbs->id}/report/unpublish");
        $this->delete("/results/{$sbs->id}")->assertRedirect('/results');
        $this->assertNull(TestSession::find($sbs->id));
        $this->assertSame(0, Report::where('test_session_id', $sbs->id)->count());
        $this->actingAs(User::factory()->create())->delete("/results/{$crowd->id}")->assertForbidden();
    }

    public function test_the_dashboard_shows_real_counts_and_recent_tests(): void
    {
        $this->completedTest();
        $this->actingAs($this->owner)->get('/dashboard')->assertOk()->assertSee('Bird test')->assertSee('Your tests')->assertDontSee('Sample data')
            ->assertSee('New Side-by-Side Test')->assertSee('New Crowd Test');
    }
}
