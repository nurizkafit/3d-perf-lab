<?php

namespace Tests\Feature;

use App\Support\ViewerConfig;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ViewerTest extends TestCase
{
    public function test_the_viewer_component_passes_configuration_as_data_not_script(): void
    {
        $html = Blade::render('<x-performance.viewer src="/model.glb" :config="$config" />', ['config' => ViewerConfig::preview()]);

        $this->assertStringContainsString('data-src="/model.glb"', $html);
        $this->assertStringContainsString('data-mode="preview"', $html);
        $this->assertStringContainsString('<script type="application/json" data-viewer-config>', $html);
        $this->assertStringContainsString('aria-label="Reset view"', $html);
        $this->assertStringContainsString('aria-label="Fullscreen"', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_preview_config_uses_the_default_presets(): void
    {
        $config = ViewerConfig::preview();

        $this->assertSame('preview', $config['mode']);
        $this->assertSame('auto_frame', $config['camera']['position_mode']);
        $this->assertArrayHasKey('directional_lights', $config['lighting']);
        $this->assertSame('neutral-100', $config['background']['color_token']);
    }

    public function test_without_a_source_the_viewer_shows_a_placeholder(): void
    {
        $html = Blade::render('<x-performance.viewer />');

        $this->assertStringNotContainsString('data-src', $html);
        $this->assertStringContainsString('No model to show.', $html);
    }

    public function test_the_viewer_lab_and_fixture_files_are_not_served_outside_local(): void
    {
        $this->get('/_viewer')->assertNotFound();
        $this->get('/_fixtures/blender/apel.glb')->assertNotFound();
    }
}
