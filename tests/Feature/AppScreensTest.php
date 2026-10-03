<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
        '/dashboard', '/device', '/assets', '/assets?view=table', '/assets/upload', '/assets/1', '/assets/1/edit',
        '/lab', '/lab/comparison', '/lab/runs/1', '/results', '/results/1', '/results/1/report',
    ];

    public function test_every_screen_renders(): void
    {
        foreach (self::SCREENS as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_sample_data_is_marked(): void
    {
        foreach (array_diff(self::SCREENS, ['/lab']) as $url) {
            $this->get($url)->assertSee('Sample data');
        }
    }

    public function test_all_eight_option_lists_render_and_unknown_lists_404(): void
    {
        foreach (['tools', 'camera', 'lighting', 'background', 'resolution', 'instance-steps', 'device-classes', 'budgets'] as $list) {
            $this->get("/admin/options/{$list}")->assertOk();
        }

        $this->get('/admin/options/nope')->assertNotFound();
    }

    public function test_unbuilt_routes_are_disabled_items_not_links(): void
    {
        $this->get('/dashboard')
            ->assertSee('Coming in M4.3')
            ->assertSee('Coming in M4.4')
            ->assertDontSee('href="'.url('/lab/stress').'"', false);
    }

    public function test_benchmark_mode_has_no_app_navigation_and_no_motion(): void
    {
        $this->get('/lab/runs/1')
            ->assertSee('data-benchmark="running"', false)
            ->assertDontSee('app-sidebar')
            ->assertDontSee('id="toaster"', false);
    }

    public function test_upload_rows_are_passed_as_json_not_inline_script(): void
    {
        $this->get('/assets/upload')
            ->assertSee('<script type="application/json" id="upload-rows">', false)
            ->assertSee('x-data="uploadTable"', false);
    }

    public function test_result_screens_show_tested_asset_metadata(): void
    {
        foreach (['/results/1', '/results/1/report'] as $url) {
            $this->get($url)
                ->assertSee('Tested assets')
                ->assertSee('Dummy Author B')
                ->assertSee('Prisma3D');
        }
    }

    public function test_budget_items_and_metrics_are_explained(): void
    {
        $this->get('/results/1')
            ->assertSee('95% of frames arrive within this time')
            ->assertSee('CPU time spent in one render call');
    }

    public function test_comparison_budget_lists_only_comparison_items(): void
    {
        $this->get('/lab/comparison')
            ->assertSee('Time to first frame, warm')
            ->assertDontSee('Headroom instances');
    }

    public function test_sidebar_items_are_not_hidden_in_collapsible_groups(): void
    {
        $this->get('/dashboard')
            ->assertDontSee('<details', false)
            ->assertSee('Budget presets');
    }
}
