<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\BudgetPreset;
use App\Models\DeviceClass;
use App\Models\OptionPreset;
use App\Models\Tool;
use App\Models\User;
use App\Support\OptionLists;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminOptionListsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_every_list_page_and_form_renders(): void
    {
        foreach (array_keys(OptionLists::LISTS) as $list) {
            $this->get("/admin/options/{$list}")->assertOk();
            $this->get("/admin/options/{$list}/create")->assertOk();
            $first = OptionLists::query($list)->first();
            $this->get("/admin/options/{$list}/{$first->id}/edit")->assertOk();
        }

        $this->get('/admin/options/nope')->assertNotFound();
    }

    public function test_forms_have_no_raw_json_field(): void
    {
        foreach (array_keys(OptionLists::LISTS) as $list) {
            $this->get("/admin/options/{$list}/create")->assertDontSee('values_json')->assertDontSee('items_json');
        }
    }

    public function test_the_sidebar_links_to_each_list(): void
    {
        $response = $this->get('/dashboard');

        foreach (array_keys(OptionLists::LISTS) as $list) {
            $response->assertSee('href="'.url("/admin/options/{$list}").'"', false);
        }
    }

    public function test_a_tool_used_by_an_asset_cannot_be_deleted_only_deactivated(): void
    {
        $tool = Tool::where('name', 'Blender')->firstOrFail();
        $this->assertTrue(Asset::where('tool_id', $tool->id)->exists(), 'Seeded fixture asset uses Blender');

        $this->delete("/admin/options/tools/{$tool->id}")->assertSessionHasErrors('item');
        $this->assertDatabaseHas('tools', ['id' => $tool->id]);

        $this->post("/admin/options/tools/{$tool->id}/deactivate")->assertSessionHasNoErrors();
        $this->assertFalse($tool->fresh()->is_active);
    }

    public function test_a_device_class_linked_to_a_budget_preset_cannot_be_deleted(): void
    {
        $class = DeviceClass::where('name', 'iPhone — Standard')->firstOrFail();

        $this->delete("/admin/options/device-classes/{$class->id}")->assertSessionHasErrors('item');
        $this->assertDatabaseHas('device_classes', ['id' => $class->id]);
    }

    public function test_an_unused_item_can_be_deleted(): void
    {
        $tool = Tool::create(['name' => 'Unused tool', 'sort_order' => 99]);

        $this->delete("/admin/options/tools/{$tool->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('tools', ['id' => $tool->id]);
    }

    public function test_create_a_camera_preset_from_structured_fields(): void
    {
        $this->post('/admin/options/camera', [
            'name' => 'Close-up', 'fov' => 30, 'position_mode' => 'fixed', 'distance_factor' => 0.8, 'elevation_deg' => 10, 'azimuth_deg' => 90,
        ])->assertRedirect('/admin/options/camera')->assertSessionHasNoErrors();

        $preset = OptionPreset::where('type', 'camera')->where('name', 'Close-up')->firstOrFail();
        $this->assertEquals(['fov' => 30.0, 'position_mode' => 'fixed', 'distance_factor' => 0.8, 'elevation_deg' => 10.0, 'azimuth_deg' => 90.0], $preset->values_json);
    }

    public function test_preset_values_are_validated(): void
    {
        $this->post('/admin/options/camera', ['name' => 'Bad', 'fov' => 500, 'position_mode' => 'orbit', 'distance_factor' => 0, 'elevation_deg' => 100, 'azimuth_deg' => -1])
            ->assertSessionHasErrors(['fov', 'position_mode', 'distance_factor', 'elevation_deg', 'azimuth_deg']);

        $this->post('/admin/options/instance-steps', ['name' => 'Bad steps', 'steps' => '10, 5, 600'])->assertSessionHasErrors('steps');
        $this->post('/admin/options/resolution', ['name' => '1280×720', 'width' => 1280, 'height' => 720])->assertSessionHasErrors('name');
    }

    public function test_create_a_lighting_preset_with_two_lights(): void
    {
        $this->post('/admin/options/lighting', [
            'name' => 'Two lights', 'ambient_intensity' => 0.4, 'environment_map' => '1',
            'lights' => [['intensity' => 2, 'elevation_deg' => 45, 'azimuth_deg' => 45], ['intensity' => 1, 'elevation_deg' => 20, 'azimuth_deg' => 200]],
        ])->assertSessionHasNoErrors();

        $values = OptionPreset::where('name', 'Two lights')->firstOrFail()->values_json;
        $this->assertCount(2, $values['directional_lights']);
        $this->assertTrue($values['environment_map']);
    }

    public function test_instance_steps_are_stored_as_numbers(): void
    {
        $this->post('/admin/options/instance-steps', ['name' => 'Big', 'steps' => '1, 50, 100, 500'])->assertSessionHasNoErrors();

        $this->assertSame([1, 50, 100, 500], OptionPreset::where('name', 'Big')->firstOrFail()->values_json['steps']);
    }

    public function test_budget_preset_form_stores_items_and_device_classes(): void
    {
        $classes = DeviceClass::whereIn('name', ['iPad', 'Android Tablet'])->pluck('id')->all();

        $this->post('/admin/options/budgets', [
            'name' => 'Tablet — Standard',
            'device_class_ids' => $classes,
            'items' => [
                'max_file_size_mb' => ['enabled' => '1', 'limit' => '10'],
                'max_p95_frame_ms' => ['enabled' => '1', 'limit' => '16.7'],
                'max_jank_pct' => ['enabled' => '0', 'limit' => ''],
            ],
        ])->assertSessionHasNoErrors();

        $budget = BudgetPreset::where('name', 'Tablet — Standard')->firstOrFail();
        $this->assertEquals(['enabled' => true, 'limit' => 10.0], $budget->items_json['max_file_size_mb']);
        $this->assertEquals(['enabled' => true, 'limit' => 16.7], $budget->items_json['max_p95_frame_ms']);
        $this->assertSame(['enabled' => false, 'limit' => null], $budget->items_json['max_jank_pct']);
        $this->assertEqualsCanonicalizing($classes, $budget->deviceClasses->pluck('id')->all());
    }

    public function test_an_enabled_budget_item_needs_a_limit(): void
    {
        $this->post('/admin/options/budgets', [
            'name' => 'Missing limit',
            'items' => ['max_draw_calls' => ['enabled' => '1', 'limit' => '']],
        ])->assertSessionHasErrors('items.max_draw_calls.limit');
    }

    public function test_set_default_moves_the_default_flag(): void
    {
        $other = OptionPreset::create(['type' => 'resolution', 'name' => '2560×1440', 'values_json' => ['width' => 2560, 'height' => 1440]]);

        $this->post("/admin/options/resolution/{$other->id}/default")->assertSessionHasNoErrors();

        $this->assertSame([$other->id], OptionPreset::where('type', 'resolution')->where('is_default', true)->pluck('id')->all());
    }

    public function test_the_default_item_cannot_be_deactivated(): void
    {
        $default = OptionPreset::where('type', 'camera')->where('is_default', true)->firstOrFail();

        $this->post("/admin/options/camera/{$default->id}/deactivate")->assertSessionHasErrors('item');
        $this->assertTrue($default->fresh()->is_active);
    }

    public function test_reorder_moves_an_item(): void
    {
        $names = fn () => OptionLists::query('tools')->pluck('name')->all();
        $before = $names();
        $second = OptionLists::query('tools')->get()[1];

        $this->post("/admin/options/tools/{$second->id}/reorder", ['direction' => 'up'])->assertSessionHasNoErrors();

        $this->assertSame([$before[1], $before[0], ...array_slice($before, 2)], $names());
    }

    public function test_a_user_cannot_change_option_lists(): void
    {
        $this->actingAs(User::factory()->create());
        $tool = Tool::where('name', 'Spline')->firstOrFail();

        $this->get('/admin/options/tools')->assertForbidden();
        $this->post("/admin/options/tools/{$tool->id}/deactivate")->assertForbidden();
        $this->post('/admin/options/tools', ['name' => 'Sneaky'])->assertForbidden();
        $this->assertTrue($tool->fresh()->is_active);
    }
}
