<?php

namespace Database\Seeders;

use App\Models\OptionPreset;
use Illuminate\Database\Seeder;

class OptionPresetSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            // PRD.md §11 seeds.
            ['resolution', '1280×720', ['width' => 1280, 'height' => 720], true],
            ['resolution', '1920×1080', ['width' => 1920, 'height' => 1080], false],
            ['resolution', '720×1280', ['width' => 720, 'height' => 1280], false],

            // One default each, decided by the user at M0.2 (decision D23).
            ['camera', 'Auto-frame', [
                'fov' => 45,
                'position_mode' => 'auto_frame',
                'distance_factor' => 1.5,
                'elevation_deg' => 20,
                'azimuth_deg' => 45,
            ], true],
            ['lighting', 'Studio', [
                'ambient_intensity' => 0.6,
                'directional_lights' => [
                    ['intensity' => 2.0, 'elevation_deg' => 45, 'azimuth_deg' => 45],
                ],
                'environment_map' => false,
            ], true],
            ['background', 'Neutral', ['color_token' => 'neutral-100'], true],
        ];

        foreach ($presets as $order => [$type, $name, $values, $isDefault]) {
            OptionPreset::firstOrCreate(
                ['type' => $type, 'name' => $name],
                ['values_json' => $values, 'is_default' => $isDefault, 'sort_order' => $order],
            );
        }
    }
}
