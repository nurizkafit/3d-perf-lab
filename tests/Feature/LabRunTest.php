<?php

namespace Tests\Feature;

use App\Jobs\ExpireTestRuns;
use App\Models\Asset;
use App\Models\DeviceClass;
use App\Models\Target;
use App\Models\TestRun;
use App\Models\TestSession;
use App\Models\Tool;
use App\Models\User;
use App\Services\RunResultService;
use App\Support\TargetStep;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Run lifecycle (BENCHMARK.md §4.1, §5), device confirmation (§7.1), Target step suggestion (§6.2).
 * Side-by-Side test creation, submission, and results are in SideBySideTest.
 */
class LabRunTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function createTest(): TestSession
    {
        $uuid = (string) Str::uuid();
        $asset = Asset::create([
            'name' => 'Lab asset', 'tool_id' => Tool::where('name', 'Blender')->value('id'), 'object_id' => $this->objectId(), 'author' => 'A',
            'uploaded_by' => $this->user->id, 'production_time_minutes' => 1, 'format' => 'glb',
            'web_file_path' => "assets/web/{$uuid}/{$uuid}.glb", 'file_size' => 2 * 1048576, 'sha256' => hash('sha256', $uuid),
        ]);
        $test = TestSession::create([
            'name' => 'Lab test', 'method' => 'side_by_side', 'configuration_json' => ['motions' => ['static']],
            'target_json' => Target::where('is_default', true)->firstOrFail()->snapshot(), 'created_by' => $this->user->id,
        ]);
        $test->assets()->attach($asset->id, ['position' => 1]);

        return $test;
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

    public function test_the_old_method_pages_and_endpoints_are_gone(): void
    {
        $this->actingAs($this->user);

        foreach (['/lab/comparison', '/lab/stress', '/lab/headroom'] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->postJson('/api/tests', ['method' => 'comparison'])->assertUnprocessable()->assertJsonValidationErrors('method');
    }

    public function test_the_lab_page_lists_the_new_methods_as_coming(): void
    {
        $this->actingAs($this->user)->get('/lab')->assertOk()
            ->assertSee('Side-by-Side Test')->assertSee('Crowd Test')->assertSee('Tool summary')
            ->assertDontSee('Headroom')->assertDontSee('Stress Test');
    }

    public function test_a_run_needs_a_confirmed_device_class(): void
    {
        $test = $this->createTest();

        $this->actingAs($this->user)->postJson("/api/tests/{$test->id}/runs", ['environment' => []])->assertUnprocessable()->assertJsonValidationErrors('device_class');

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
        $this->post('/device', ['device_class_id' => DeviceClass::first()->id, 'return_to' => '/lab'])->assertRedirect('/lab');
    }

    public function test_the_target_step_suggests_the_target_linked_to_the_confirmed_device_class(): void
    {
        $data = fn (?int $deviceClassId) => TargetStep::data(tap(Request::create('/'), fn ($request) => $request->setLaravelSession(tap(app('session.store'), fn ($session) => $session->put('device_class_id', $deviceClassId)))));

        $iphone = DeviceClass::where('name', 'iPhone — Standard')->value('id');
        $suggested = $data($iphone);
        $this->assertSame(Target::where('name', 'Phone — Mid-range')->value('id'), $suggested['suggestedId']);
        $this->assertTrue($suggested['suggestedForDevice']);
        $this->assertSame(['max_load_s', 'target_fps', 'max_file_size_mb'], array_keys($suggested['fields']));

        $fallback = $data(null);
        $this->assertSame(Target::where('is_default', true)->value('id'), $fallback['suggestedId']);
        $this->assertFalse($fallback['suggestedForDevice']);
    }

    public function test_an_invalidated_run_keeps_its_reason_and_has_no_target_checks(): void
    {
        $this->confirmDevice();
        $run = $this->startRun($this->createTest());

        $this->postJson("/api/runs/{$run->id}/invalidate", ['reason' => 'The tab was hidden during measurement.'])->assertOk();

        $run->refresh();
        $this->assertSame(['invalid', 'The tab was hidden during measurement.'], [$run->status, $run->invalid_reason]);
        $this->assertSame(0, $run->checks()->count());
        $this->postJson("/api/runs/{$run->id}/invalidate", ['reason' => 'again'])->assertForbidden();
    }

    public function test_a_run_can_be_cancelled_by_its_creator_only(): void
    {
        $this->confirmDevice();
        $run = $this->startRun($this->createTest());

        $this->actingAs(User::factory()->create())->postJson("/api/runs/{$run->id}/cancel")->assertForbidden();
        $this->actingAs($this->user)->postJson("/api/runs/{$run->id}/cancel")->assertOk();
        $this->assertSame('cancelled', $run->fresh()->status);
    }

    public function test_the_expiry_job_invalidates_stale_running_runs(): void
    {
        $this->confirmDevice();
        $test = $this->createTest();
        $stale = $this->startRun($test);
        $fresh = $this->startRun($test);
        $stale->update(['started_at' => now()->subMinutes(config('lab.run_expiry_minutes') + 5)]);

        app(ExpireTestRuns::class)->handle(app(RunResultService::class));

        $this->assertSame(['invalid', 'expired'], [$stale->fresh()->status, $stale->fresh()->invalid_reason]);
        $this->assertSame('running', $fresh->fresh()->status);
    }

    public function test_another_user_cannot_start_a_run_on_my_test(): void
    {
        $test = $this->createTest();
        $other = User::factory()->create();
        $this->actingAs($other)->post('/device', ['device_class_id' => DeviceClass::first()->id]);

        $this->postJson("/api/tests/{$test->id}/runs", ['environment' => []])->assertForbidden();
    }

    public function test_the_results_page_without_a_completed_run_says_so_and_is_private(): void
    {
        $test = $this->createTest();

        $this->actingAs($this->user)->get("/results/{$test->id}")->assertOk()->assertSee('Lab test')->assertSee('This test has no completed run yet.');
        $this->actingAs(User::factory()->create())->get("/results/{$test->id}")->assertForbidden();
    }
}
