<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class UiComponentsTest extends TestCase
{
    public function test_budget_badges_show_an_icon_and_a_word(): void
    {
        foreach (['pass' => 'Pass', 'fail' => 'Fail', 'not-measured' => 'Not measured'] as $status => $word) {
            $html = Blade::render('<x-ui.badge :status="$status" />', ['status' => $status]);

            $this->assertStringContainsString('<svg', $html);
            $this->assertStringContainsString($word, $html);
        }
    }

    public function test_an_input_with_an_error_is_marked_invalid_and_described(): void
    {
        $html = Blade::render('<x-ui.input name="hours" label="Production time (h)" error="Must be 0 or more." />');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="hours-error"', $html);
        $this->assertStringContainsString('id="hours-error"', $html);
    }

    public function test_user_content_is_escaped(): void
    {
        $html = Blade::render('<x-ui.empty-state :message="$message" />', ['message' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_a_loading_button_is_disabled_and_busy(): void
    {
        $html = Blade::render('<x-ui.button loading>Saving</x-ui.button>');

        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('aria-busy="true"', $html);
    }
}
