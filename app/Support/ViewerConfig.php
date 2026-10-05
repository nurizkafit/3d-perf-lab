<?php

namespace App\Support;

use App\Models\OptionPreset;

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
}
