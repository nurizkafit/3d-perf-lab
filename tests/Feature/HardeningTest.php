<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** M5.1 hardening: error pages, security headers, guest and role boundaries for every app route. */
class HardeningTest extends TestCase
{
    use DatabaseTransactions;

    public function test_error_pages_use_the_design_system_and_show_no_internals(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found')->assertSee('Published reports')->assertDontSee('Symfony');
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden()->assertSee('Not allowed');

        foreach ([419 => 'Page expired', 429 => 'Too many requests', 500 => 'Something went wrong', 503 => 'Maintenance'] as $code => $title) {
            $html = view("errors.{$code}")->render();
            $this->assertStringContainsString($title, $html);
            $this->assertStringContainsString('noindex', $html);
        }
    }

    public function test_every_web_response_carries_the_security_headers(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_guests_are_redirected_from_every_app_page_and_json_endpoints_return_401(): void
    {
        $public = ['/', 'up', 'reports', 'reports/{slug}', 'reports/{slug}/assets/{token}/{name?}', 'about', 'contact', 'login', 'storage/{path}'];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! in_array('GET', $route->methods(), true) || in_array($uri, $public, true) || str_starts_with($uri, '_')) {
                continue;
            }
            $url = '/'.preg_replace('/\{[^}]+\}/', '1', $uri);
            $response = $this->get($url);
            str_starts_with($uri, 'api/')
                ? $this->assertContains($response->status(), [401, 404], "{$uri} must not be open to guests")
                : $this->assertTrue($response->isRedirect(route('login')) || $response->status() === 404, "{$uri} must redirect guests to login");
        }
    }

    public function test_users_cannot_reach_any_admin_page(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'admin') && in_array('GET', $route->methods(), true)) {
                $this->get('/'.preg_replace('/\{list\}/', 'tools', preg_replace('/\{(?!list)[^}]+\}/', '1', $route->uri())))->assertForbidden();
            }
        }
    }
}
