<?php

namespace Tests\Feature;

use App\Models\BudgetPreset;
use App\Models\DeviceClass;
use App\Models\OptionPreset;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Needs bin/verify-db-setup.sh to have run. Uses transactions, so the
 * verification data stays in place for browser checks.
 */
class VerificationEnvironmentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_runs_against_the_verification_database_and_storage(): void
    {
        $this->assertSame('render_lab_verify', DB::connection()->getDatabaseName());
        $this->assertStringStartsWith(storage_path('verify'), config('filesystems.disks.local.root'));
    }

    public function test_the_full_schema_exists(): void
    {
        foreach ([
            'users', 'tools', 'option_presets', 'device_classes', 'budget_presets', 'budget_preset_device_class',
            'assets', 'asset_metrics', 'upload_sessions', 'upload_items',
            'test_sessions', 'test_session_assets', 'test_runs', 'test_results', 'budget_evaluations',
            'reports', 'report_public_assets',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }
    }

    public function test_base_seed_data_is_present(): void
    {
        $this->assertTrue(User::where('role', 'admin')->where('is_active', true)->exists());
        $this->assertSame(['Blender', 'Prisma3D', 'Spline'], Tool::orderBy('sort_order')->pluck('name')->all());
        $this->assertSame(11, DeviceClass::count());

        foreach (['camera', 'lighting', 'background', 'resolution', 'instance_steps'] as $type) {
            $this->assertSame(1, OptionPreset::where('type', $type)->where('is_default', true)->count(), "Default {$type}");
        }
    }

    public function test_budget_presets_link_to_their_device_classes(): void
    {
        $desktop = BudgetPreset::where('name', 'Desktop — Standard')->firstOrFail();

        $this->assertCount(4, $desktop->deviceClasses);
        $this->assertSame(16.7, $desktop->items_json['max_p95_frame_ms']['limit']);
        $this->assertFalse($desktop->items_json['min_instances_at_budget']['enabled']);

        $iphone = DeviceClass::where('name', 'iPhone — Standard')->firstOrFail();
        $this->assertSame(['Mobile — Mid-range'], $iphone->budgetPresets->pluck('name')->all());
    }

    public function test_seeders_do_not_create_duplicates(): void
    {
        $before = [User::count(), Tool::count(), OptionPreset::count(), DeviceClass::count(), BudgetPreset::count(), DB::table('budget_preset_device_class')->count()];

        $this->seed();

        $after = [User::count(), Tool::count(), OptionPreset::count(), DeviceClass::count(), BudgetPreset::count(), DB::table('budget_preset_device_class')->count()];
        $this->assertSame($before, $after);
    }

    public function test_the_ui_kit_page_is_not_routed_outside_local(): void
    {
        $this->get('/_ui')->assertNotFound();
    }
}
