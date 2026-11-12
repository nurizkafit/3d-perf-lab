<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * App screens. Signed in as an admin so every screen is reachable. Since M4.6 no screen shows sample data.
 */
class AppScreensTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private const SCREENS = [
        '/dashboard', '/assets/upload',
        '/lab', '/lab/side-by-side', '/lab/crowd', '/results', '/results/tools',
    ];

    public function test_every_screen_renders(): void
    {
        foreach (self::SCREENS as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_no_screen_shows_sample_data(): void
    {
        foreach (self::SCREENS as $url) {
            $this->get($url)->assertDontSee('Sample data');
        }
    }

    public function test_all_option_lists_render_and_removed_lists_404(): void
    {
        foreach (['tools', 'objects', 'camera', 'lighting', 'background', 'resolution', 'device-classes', 'targets'] as $list) {
            $this->get("/admin/options/{$list}")->assertOk();
        }

        foreach (['nope', 'instance-steps', 'budgets'] as $list) {
            $this->get("/admin/options/{$list}")->assertNotFound();
        }
    }

    public function test_the_sidebar_links_the_lab_methods_and_the_tool_summary(): void
    {
        $this->get('/dashboard')
            ->assertSee('Side-by-Side Test')->assertSee('href="'.url('/lab/side-by-side').'"', false)->assertDontSee('coming in M4.3')
            ->assertSee('Crowd Test')->assertSee('href="'.url('/lab/crowd').'"', false)
            ->assertSee('Tool summary')->assertSee('href="'.url('/results/tools').'"', false)
            ->assertDontSee('href="'.url('/lab/comparison').'"', false)->assertDontSee('href="'.url('/lab/headroom').'"', false);
    }

    public function test_upload_config_is_passed_as_json_not_inline_script(): void
    {
        $this->get('/assets/upload')
            ->assertSee('<script type="application/json" id="upload-config">', false)
            ->assertSee('x-data="uploadTable"', false);
    }

    public function test_sidebar_items_are_not_hidden_in_collapsible_groups(): void
    {
        $this->get('/dashboard')
            ->assertDontSee('<details', false)
            ->assertSee('Targets')->assertSee('Objects')->assertDontSee('Budget presets');
    }

    public function test_times_are_shown_in_the_display_timezone(): void
    {
        config(['lab.display_timezone' => 'Asia/Jakarta']);
        $this->travelTo(Carbon::parse('2026-10-07 20:30:00', 'UTC'));
        $this->actingAs(User::factory()->create());

        $user = User::factory()->create();
        $asset = Asset::create([
            'name' => 'Timed asset', 'tool_id' => Tool::first()->id, 'object_id' => $this->objectId(), 'author' => 'A', 'uploaded_by' => $user->id,
            'production_time_minutes' => 1, 'format' => 'glb', 'web_file_path' => 'assets/web/x/x.glb', 'file_size' => 10, 'sha256' => str_repeat('b', 64),
        ]);

        $this->get("/assets/{$asset->id}")->assertOk()->assertSee('2026-10-08 03:30');
    }
}
