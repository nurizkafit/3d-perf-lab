<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Report;
use App\Models\TestSession;
use App\Models\Tool;
use App\Models\UploadSession;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Authorization rules from PRD.md §4 and SITEMAP.md §3. */
class PolicyTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $owner;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
    }

    private function asset(): Asset
    {
        return Asset::create([
            'name' => 'Policy test asset',
            'tool_id' => Tool::firstOrCreate(['name' => 'Policy test tool'])->id,
            'author' => 'Someone',
            'uploaded_by' => $this->owner->id,
            'production_time_hours' => 1,
            'format' => 'glb',
            'web_file_path' => 'assets/web/'.Str::uuid().'.glb',
            'file_size' => 100,
            'sha256' => str_repeat('a', 64),
        ]);
    }

    private function test(): TestSession
    {
        return TestSession::create([
            'name' => 'Policy test',
            'method' => 'side_by_side',
            'configuration_json' => [],
            'target_json' => [],
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_every_user_can_browse_upload_and_download_assets(): void
    {
        $asset = $this->asset();

        foreach ([$this->admin, $this->owner, $this->other] as $user) {
            $this->assertTrue($user->can('viewAny', Asset::class));
            $this->assertTrue($user->can('view', $asset));
            $this->assertTrue($user->can('download', $asset));
            $this->assertTrue($user->can('create', Asset::class));
        }
    }

    public function test_only_an_admin_can_set_the_author(): void
    {
        $this->assertTrue($this->admin->can('setAuthor', Asset::class));
        $this->assertFalse($this->owner->can('setAuthor', Asset::class));
    }

    public function test_owner_and_admin_can_edit_delete_and_restore_an_asset(): void
    {
        $asset = $this->asset();

        foreach (['update', 'delete', 'restore'] as $ability) {
            $this->assertTrue($this->owner->can($ability, $asset), "owner {$ability}");
            $this->assertTrue($this->admin->can($ability, $asset), "admin {$ability}");
            $this->assertFalse($this->other->can($ability, $asset), "other {$ability}");
        }
    }

    public function test_only_an_admin_can_permanently_delete_an_asset(): void
    {
        $asset = $this->asset();

        $this->assertTrue($this->admin->can('forceDelete', $asset));
        $this->assertFalse($this->owner->can('forceDelete', $asset));
    }

    public function test_users_see_their_own_tests_and_admins_see_all(): void
    {
        $test = $this->test();

        foreach (['view', 'update', 'delete', 'export'] as $ability) {
            $this->assertTrue($this->owner->can($ability, $test), "owner {$ability}");
            $this->assertTrue($this->admin->can($ability, $test), "admin {$ability}");
            $this->assertFalse($this->other->can($ability, $test), "other {$ability}");
        }

        $this->assertTrue($this->other->can('create', TestSession::class));
    }

    public function test_owner_and_admin_can_manage_and_publish_a_report(): void
    {
        $report = Report::create([
            'test_session_id' => $this->test()->id,
            'title' => 'Policy report',
            'created_by' => $this->owner->id,
        ]);

        foreach (['view', 'update', 'publish', 'unpublish'] as $ability) {
            $this->assertTrue($this->owner->can($ability, $report), "owner {$ability}");
            $this->assertTrue($this->admin->can($ability, $report), "admin {$ability}");
            $this->assertFalse($this->other->can($ability, $report), "other {$ability}");
        }
    }

    public function test_account_management_is_admin_only(): void
    {
        foreach (['update', 'resetPassword', 'deactivate', 'activate'] as $ability) {
            $this->assertTrue($this->admin->can($ability, $this->owner), "admin {$ability}");
            $this->assertFalse($this->other->can($ability, $this->owner), "user {$ability}");
        }

        $this->assertTrue($this->admin->can('viewAny', User::class));
        $this->assertFalse($this->owner->can('viewAny', User::class));
        $this->assertFalse($this->owner->can('create', User::class));
    }

    public function test_an_admin_cannot_deactivate_their_own_account(): void
    {
        $this->assertFalse($this->admin->can('deactivate', $this->admin));
    }

    public function test_only_the_creator_can_use_an_upload_session(): void
    {
        $session = UploadSession::create(['created_by' => $this->owner->id]);

        $this->assertTrue($this->owner->can('view', $session));
        $this->assertTrue($this->owner->can('update', $session));
        $this->assertFalse($this->other->can('view', $session));
        $this->assertFalse($this->admin->can('view', $session));
    }
}
