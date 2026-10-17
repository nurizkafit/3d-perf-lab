<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** Public pages without login (SITEMAP.md §2). Published reports are covered by ReportsTest. */
class PublicSiteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_pages_render_without_login(): void
    {
        foreach (['/', '/reports', '/about', '/contact'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('Sample data');
        }
    }

    public function test_an_unknown_report_returns_404(): void
    {
        $this->get('/reports/does-not-exist')->assertNotFound();
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        $this->get('/reports?method=bogus&device=nope&q='.str_repeat('x', 300))->assertOk();
    }

    public function test_no_match_shows_the_empty_state(): void
    {
        $this->get('/reports?q=zzzz-no-such-report')->assertSee('No published report matches these filters.');
    }

    public function test_header_links_to_login_for_guests(): void
    {
        $this->get('/')->assertSee('href="'.url('/login').'"', false);
    }

    public function test_contact_page_does_not_invent_contact_data(): void
    {
        $this->get('/contact')->assertSee('Contact methods have not been added yet.')->assertDontSee('mailto:');
    }
}
