<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetObject;
use App\Models\DeviceClass;
use App\Models\OptionPreset;
use App\Models\Target;
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
            $this->get("/admin/options/{$list}/create")->assertDontSee('values_json')->assertDontSee('items_json')->assertDontSee('target_json');
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

    public function test_an_object_used_by_an_asset_cannot_be_deleted_only_deactivated(): void
    {
        $object = AssetObject::where('name', 'Apple')->firstOrFail();
        $this->assertTrue(Asset::where('object_id', $object->id)->exists(), 'Seeded fixture asset is an Apple');

        $this->delete("/admin/options/objects/{$object->id}")->assertSessionHasErrors('item');
        $this->assertDatabaseHas('objects', ['id' => $object->id]);

        $this->post("/admin/options/objects/{$object->id}/deactivate")->assertSessionHasNoErrors();
        $this->assertFalse($object->fresh()->is_active);
    }

    public function test_an_unused_object_can_be_created_and_deleted(): void
    {
        $this->post('/admin/options/objects', ['name' => 'Lantern'])->assertSessionHasNoErrors();
        $object = AssetObject::where('name', 'Lantern')->firstOrFail();

        $this->delete("/admin/options/objects/{$object->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('objects', ['id' => $object->id]);
    }

    public function test_a_device_class_linked_to_a_target_cannot_be_deleted(): void
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

    public function test_target_form_stores_values_and_device_classes(): void
    {
        $classes = DeviceClass::whereIn('name', ['iPad', 'Android Tablet'])->pluck('id')->all();

        $this->post('/admin/options/targets', [
            'name' => 'Tablet', 'max_load_s' => '3.5', 'target_fps' => '60', 'max_file_size_mb' => '10', 'device_class_ids' => $classes,
        ])->assertSessionHasNoErrors();

        $target = Target::where('name', 'Tablet')->firstOrFail();
        $this->assertSame(3.5, $target->max_load_s);
        $this->assertSame(60, $target->target_fps);
        $this->assertSame(10.0, $target->max_file_size_mb);
        $this->assertEqualsCanonicalizing($classes, $target->deviceClasses->pluck('id')->all());
    }

    public function test_target_values_are_validated(): void
    {
        $this->post('/admin/options/targets', ['name' => 'Bad', 'max_load_s' => '0', 'target_fps' => '29.5', 'max_file_size_mb' => ''])
            ->assertSessionHasErrors(['max_load_s', 'target_fps', 'max_file_size_mb']);
    }

    public function test_the_default_target_cannot_be_deactivated(): void
    {
        $default = Target::where('is_default', true)->firstOrFail();

        $this->post("/admin/options/targets/{$default->id}/deactivate")->assertSessionHasErrors('item');
        $this->assertTrue($default->fresh()->is_active);
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
