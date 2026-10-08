<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * App screens (sample data until their milestones). Signed in as an admin so every screen is reachable.
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
        '/lab', '/results', '/results/1/report',
    ];

    public function test_every_screen_renders(): void
    {
        foreach (self::SCREENS as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_sample_data_is_marked(): void
    {
        foreach (array_diff(self::SCREENS, ['/lab', '/assets/upload']) as $url) {
            $this->get($url)->assertSee('Sample data');
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

    public function test_the_sidebar_shows_the_new_lab_methods_as_coming(): void
    {
        $this->get('/dashboard')
            ->assertSee('Side-by-Side Test')->assertSee('coming in M4.3')
            ->assertSee('Crowd Test')->assertSee('coming in M4.4')
            ->assertSee('Tool summary')->assertSee('coming in M4.5')
            ->assertDontSee('href="'.url('/lab/comparison').'"', false)->assertDontSee('href="'.url('/lab/headroom').'"', false);
    }

    public function test_upload_config_is_passed_as_json_not_inline_script(): void
    {
        $this->get('/assets/upload')
            ->assertSee('<script type="application/json" id="upload-config">', false)
            ->assertSee('x-data="uploadTable"', false);
    }

    public function test_result_screens_show_tested_asset_metadata(): void
    {
        foreach (['/results/1/report'] as $url) {
            $this->get($url)
                ->assertSee('Tested assets')
                ->assertSee('Dummy Author B')
                ->assertSee('Prisma3D');
        }
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
            'production_time_hours' => 1, 'format' => 'glb', 'web_file_path' => 'assets/web/x/x.glb', 'file_size' => 10, 'sha256' => str_repeat('b', 64),
        ]);

        $this->get("/assets/{$asset->id}")->assertOk()->assertSee('2026-10-08 03:30');
    }
}
