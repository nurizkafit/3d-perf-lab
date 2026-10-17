<?php

namespace App\Support;

use App\Models\OptionPreset;
use App\Models\TestSession;

/**
 * Viewer configuration passed to components/performance/viewer as JSON.
 * Preview mode uses the default camera, lighting, and background presets (PRD.md §11).
 */
class ViewerConfig
{
    public static function preview(): array
    {
        $defaults = OptionPreset::whereIn('type', ['camera', 'lighting', 'background'])
            ->where('is_default', true)
            ->get()
            ->mapWithKeys(fn (OptionPreset $preset) => [$preset->type => $preset->values_json]);

        return [
            'mode' => 'preview',
            'camera' => $defaults['camera'] ?? null,
            'lighting' => $defaults['lighting'] ?? null,
            'background' => $defaults['background'] ?? null,
        ];
    }

    /**
     * Linked panes of a test (look phase on the results page): the presets the test stored, so the look matches
     * the measured scene. Not measured.
     */
    public static function forTest(TestSession $test): array
    {
        $configuration = $test->configuration_json;

        return [
            'camera' => $configuration['camera']['values'] ?? null,
            'lighting' => $configuration['lighting']['values'] ?? null,
            'background' => $configuration['background']['values'] ?? null,
            'resolution' => $configuration['resolution']['values'] ?? null,
            'motions' => $configuration['motions'] ?? ['static'],
            'assets' => $test->assets->map(fn ($asset) => [
                'id' => $asset->id,
                'name' => $asset->name,
                'url' => route('assets.file', [$asset, basename($asset->web_file_path)]),
            ])->values()->all(),
        ];
    }
}
