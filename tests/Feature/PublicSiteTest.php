<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    private const SLUG = 'fruit-model-mid-range-android-x7k2';

    public function test_public_pages_render_without_login(): void
    {
        foreach (['/', '/reports', '/reports/'.self::SLUG, '/about', '/contact'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_sample_data_is_always_marked(): void
    {
        $this->get('/reports')->assertSee('Sample data');
        $this->get('/reports/'.self::SLUG)->assertSee('Sample data');
    }

    public function test_an_unknown_report_returns_404(): void
    {
        $this->get('/reports/does-not-exist')->assertNotFound();
    }

    public function test_gallery_filters_use_query_parameters(): void
    {
        $this->get('/reports?method=headroom')
            ->assertSee('Headroom for a simple book on iPhone')
            ->assertDontSee('Three tools, one budget');

        $this->get('/reports?device='.urlencode('Desktop — Dedicated GPU'))
            ->assertSee('How far does the chicken scale on desktop?')
            ->assertSee('1 report');

        $this->get('/reports?q=iphone')->assertSee('1 report');
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        $this->get('/reports?method=bogus&device=nope')->assertOk()->assertSee('4 reports');
    }

    public function test_no_match_shows_the_empty_state(): void
    {
        $this->get('/reports?q=zzzz')->assertSee('No published report matches these filters.');
    }

    public function test_preview_states_are_ignored_outside_local(): void
    {
        $this->get('/reports?_state=error')->assertDontSee('could not be loaded')->assertSee('4 reports');
    }

    public function test_header_links_to_login_for_guests(): void
    {
        $this->get('/')->assertSee('href="'.url('/login').'"', false);
    }

    public function test_report_detail_shows_the_reproduction_notice_and_standard_limitations(): void
    {
        $this->get('/reports/'.self::SLUG)
            ->assertSee('Interactive reproduction')
            ->assertSee('Your device is not measured')
            ->assertSee('Results apply to the recorded device and browser only.');
    }

    public function test_contact_page_does_not_invent_contact_data(): void
    {
        $this->get('/contact')->assertSee('Contact methods have not been added yet.')->assertDontSee('mailto:');
    }
}
