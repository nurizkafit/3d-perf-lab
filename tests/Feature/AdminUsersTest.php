<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);
    }

    public function test_the_user_list_shows_role_status_and_last_login(): void
    {
        $user = User::factory()->create(['name' => 'Listed Person', 'last_login_at' => '2026-10-01 08:30:00']);

        $this->get('/admin/users')->assertOk()
            ->assertSee('Listed Person')->assertSee($user->email)->assertSee('2026-10-01 08:30');
    }

    public function test_an_admin_creates_a_user_who_can_then_log_in(): void
    {
        $this->post('/admin/users', [
            'name' => 'New Tester', 'email' => 'new.tester@example.test', 'role' => 'user', 'password' => 'temporary-pass',
        ])->assertRedirect('/admin/users')->assertSessionHasNoErrors();

        $this->post('/logout');
        $this->post('/login', ['email' => 'new.tester@example.test', 'password' => 'temporary-pass'])->assertRedirect('/dashboard');

        $user = User::where('email', 'new.tester@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('user', $user->role);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_create_validates_input(): void
    {
        $this->post('/admin/users', ['name' => '', 'email' => $this->admin->email, 'role' => 'owner', 'password' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'role', 'password']);
    }

    public function test_an_admin_edits_name_email_and_role(): void
    {
        $user = User::factory()->create();

        $this->put("/admin/users/{$user->id}", ['name' => 'Renamed', 'email' => 'renamed@example.test', 'role' => 'admin'])
            ->assertRedirect('/admin/users');

        $this->assertSame(['Renamed', 'renamed@example.test', 'admin'], [$user->fresh()->name, $user->fresh()->email, $user->fresh()->role]);
    }

    public function test_the_last_active_admin_cannot_be_demoted(): void
    {
        User::where('role', 'admin')->whereKeyNot($this->admin->id)->update(['is_active' => false]);

        $this->put("/admin/users/{$this->admin->id}", ['name' => 'Me', 'email' => $this->admin->email, 'role' => 'user'])
            ->assertSessionHasErrors('role');
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_an_admin_cannot_deactivate_their_own_account(): void
    {
        $this->post("/admin/users/{$this->admin->id}/deactivate")->assertForbidden();
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_an_admin_can_deactivate_another_admin(): void
    {
        $other = User::factory()->create(['role' => 'admin']);

        $this->post("/admin/users/{$other->id}/deactivate")->assertSessionHasNoErrors();
        $this->assertFalse($other->fresh()->is_active);
    }

    public function test_the_last_active_admin_guard_counts_only_other_active_admins(): void
    {
        User::where('role', 'admin')->whereKeyNot($this->admin->id)->update(['is_active' => false]);

        $this->assertSame(0, $this->admin->otherActiveAdminCount());
        User::factory()->create(['role' => 'admin']);
        $this->assertSame(1, $this->admin->otherActiveAdminCount());
    }

    public function test_deactivate_and_activate_a_user(): void
    {
        $user = User::factory()->create();

        $this->post("/admin/users/{$user->id}/deactivate")->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->is_active);

        $this->post("/admin/users/{$user->id}/activate")->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_reset_password_sets_a_new_temporary_password(): void
    {
        $user = User::factory()->create();

        $this->post("/admin/users/{$user->id}/reset-password", ['password' => 'brand-new-pass'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_a_user_cannot_manage_accounts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/admin/users', ['name' => 'X', 'email' => 'x@example.test', 'role' => 'admin', 'password' => 'password1'])->assertForbidden();
        $this->post("/admin/users/{$this->admin->id}/deactivate")->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'x@example.test']);
    }
}
