<?php

namespace Tests\Feature;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    private const APP_ROUTES = [
        '/dashboard', '/device', '/assets', '/assets/upload', '/assets/1', '/assets/1/edit',
        '/lab', '/lab/comparison', '/lab/runs/1', '/results', '/results/1', '/results/1/report',
        '/admin/users', '/admin/options/tools',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('');
    }

    public function test_guests_are_redirected_to_login_from_every_app_route(): void
    {
        foreach (self::APP_ROUTES as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_the_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Log in');
    }

    public function test_there_is_no_registration_route(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_an_active_user_can_log_in_and_lands_on_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_deactivated_user_cannot_log_in_and_sees_why(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => LoginRequest::DEACTIVATED]);
        $this->assertGuest();
    }

    public function test_the_deactivated_message_is_not_shown_for_a_wrong_password(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
    }

    public function test_a_user_deactivated_during_a_session_is_signed_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get('/dashboard')->assertRedirect('/login')->assertSessionHasErrors(['email' => LoginRequest::DEACTIVATED]);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_a_user_cannot_open_admin_routes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));

        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/options/tools')->assertForbidden();
        $this->get('/dashboard')->assertOk();
    }

    public function test_an_admin_can_open_admin_routes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get('/admin/users')->assertOk();
    }

    public function test_a_user_does_not_see_the_administration_menu(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));

        $this->get('/dashboard')->assertDontSee('Administration')->assertDontSee('Option lists');
    }

    public function test_a_signed_in_user_is_sent_from_login_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/login')->assertRedirect('/dashboard');
    }
}
