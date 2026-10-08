<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetObject;
use App\Models\DeviceClass;
use App\Models\OptionPreset;
use App\Models\Target;
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
            'users', 'tools', 'objects', 'option_presets', 'device_classes', 'targets', 'device_class_target',
            'assets', 'asset_metrics', 'upload_sessions', 'upload_items',
            'test_sessions', 'test_session_assets', 'test_runs', 'test_results', 'target_checks',
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

        foreach (['camera', 'lighting', 'background', 'resolution'] as $type) {
            $this->assertSame(1, OptionPreset::where('type', $type)->where('is_default', true)->count(), "Default {$type}");
        }
        $this->assertFalse(OptionPreset::where('type', 'instance_steps')->exists(), 'The instance-step list was removed in M4.2');
        $this->assertSame(['Apple', 'Chicken', 'Book'], AssetObject::orderBy('sort_order')->pluck('name')->all(), 'Objects from the fixture register');
        $this->assertFalse(Asset::whereNull('object_id')->exists(), 'Every fixture asset has an object');
    }

    public function test_seed_targets_match_benchmark_and_link_to_their_device_classes(): void
    {
        // BENCHMARK.md §6.2: load time (s), goal frame rate (fps), file size (MB).
        $expected = [
            'Desktop' => [3.0, 60, 20.0, 4],
            'Phone — Flagship' => [3.0, 60, 12.0, 2],
            'Phone — Mid-range' => [4.0, 30, 8.0, 2],
            'Phone — Entry' => [6.0, 30, 5.0, 1],
        ];
        $targets = Target::with('deviceClasses')->orderBy('sort_order')->get()->keyBy('name');
        $this->assertSame(array_keys($expected), $targets->keys()->all());
        foreach ($expected as $name => [$load, $fps, $size, $classes]) {
            $this->assertSame([$load, $fps, $size, $classes], [$targets[$name]->max_load_s, $targets[$name]->target_fps, $targets[$name]->max_file_size_mb, $targets[$name]->deviceClasses->count()], $name);
        }
        $this->assertTrue($targets['Desktop']->is_default);

        $iphone = DeviceClass::where('name', 'iPhone — Standard')->firstOrFail();
        $this->assertSame(['Phone — Mid-range'], $iphone->targets->pluck('name')->all());
    }

    public function test_seeders_do_not_create_duplicates(): void
    {
        $before = [User::count(), Tool::count(), OptionPreset::count(), DeviceClass::count(), Target::count(), DB::table('device_class_target')->count()];

        $this->seed();

        $after = [User::count(), Tool::count(), OptionPreset::count(), DeviceClass::count(), Target::count(), DB::table('device_class_target')->count()];
        $this->assertSame($before, $after);
    }

    public function test_the_ui_kit_page_is_not_routed_outside_local(): void
    {
        $this->get('/_ui')->assertNotFound();
    }
}
