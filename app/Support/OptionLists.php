<?php

namespace App\Support;

use App\Models\AssetObject;
use App\Models\DeviceClass;
use App\Models\OptionPreset;
use App\Models\Target;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The admin-managed option lists (PRD.md §11): which model stores each list,
 * its structured form fields, validation, and the "in use" check.
 * Values are always entered through fields; the admin never types JSON.
 */
class OptionLists
{
    public const LISTS = [
        'tools' => ['label' => 'Authoring tools', 'singular' => 'tool', 'model' => Tool::class, 'default' => false],
        'objects' => ['label' => 'Objects', 'singular' => 'object', 'model' => AssetObject::class, 'default' => false],
        'camera' => ['label' => 'Camera presets', 'singular' => 'camera preset', 'model' => OptionPreset::class, 'type' => 'camera', 'default' => true],
        'lighting' => ['label' => 'Lighting presets', 'singular' => 'lighting preset', 'model' => OptionPreset::class, 'type' => 'lighting', 'default' => true],
        'background' => ['label' => 'Background presets', 'singular' => 'background preset', 'model' => OptionPreset::class, 'type' => 'background', 'default' => true],
        'resolution' => ['label' => 'Resolution presets', 'singular' => 'resolution preset', 'model' => OptionPreset::class, 'type' => 'resolution', 'default' => true],
        'device-classes' => ['label' => 'Device classes', 'singular' => 'device class', 'model' => DeviceClass::class, 'default' => false],
        'targets' => ['label' => 'Targets', 'singular' => 'Target', 'model' => Target::class, 'default' => true],
    ];

    /** Lists that hold only a name, so several items can be added at once. */
    public const BULK_LISTS = ['tools', 'objects'];

    public const CATEGORIES = ['desktop' => 'Desktop', 'laptop' => 'Laptop', 'tablet' => 'Tablet', 'phone' => 'Phone'];

    public const PLATFORMS = ['any' => 'Any', 'android' => 'Android', 'ios' => 'iOS / iPadOS', 'windows' => 'Windows', 'macos' => 'macOS', 'linux' => 'Linux', 'chromeos' => 'ChromeOS'];

    /** Background colors come from the design tokens only (PRD.md §11). */
    public const BACKGROUND_TOKENS = [
        'neutral-0' => 'Neutral 0 (white)', 'neutral-50' => 'Neutral 50', 'neutral-100' => 'Neutral 100',
        'neutral-200' => 'Neutral 200', 'neutral-800' => 'Neutral 800', 'primary-tint' => 'Primary tint',
    ];

    public static function exists(string $list): bool
    {
        return array_key_exists($list, self::LISTS);
    }

    public static function definition(string $list): array
    {
        return self::LISTS[$list];
    }

    /** All items of a list in display order. */
    public static function query(string $list): Builder
    {
        $definition = self::LISTS[$list];
        $query = $definition['model']::query();

        if (isset($definition['type'])) {
            $query->where('type', $definition['type']);
        }

        return $query->orderBy('sort_order')->orderBy('id');
    }

    public static function find(string $list, int $id): Model
    {
        return self::query($list)->findOrFail($id);
    }

    /** Validation rules for the create/edit form. */
    public static function rules(string $list, ?Model $item = null): array
    {
        $definition = self::LISTS[$list];
        $table = (new $definition['model'])->getTable();
        $unique = Rule::unique($table, 'name')->ignore($item?->id);
        if (isset($definition['type'])) {
            $unique->where('type', $definition['type']);
        }

        $rules = ['name' => ['required', 'string', 'max:100', $unique]];

        return $rules + match ($list) {
            'tools', 'objects' => [],
            'camera' => [
                'fov' => ['required', 'numeric', 'between:10,120'],
                'position_mode' => ['required', Rule::in(['auto_frame', 'fixed'])],
                'distance_factor' => ['required', 'numeric', 'between:0.5,10'],
                'elevation_deg' => ['required', 'numeric', 'between:-90,90'],
                'azimuth_deg' => ['required', 'numeric', 'between:0,360'],
            ],
            'lighting' => [
                'ambient_intensity' => ['required', 'numeric', 'between:0,10'],
                'lights' => ['required', 'array', 'min:1', 'max:3'],
                'lights.*.intensity' => ['required', 'numeric', 'between:0,20'],
                'lights.*.elevation_deg' => ['required', 'numeric', 'between:-90,90'],
                'lights.*.azimuth_deg' => ['required', 'numeric', 'between:0,360'],
                'environment_map' => ['boolean'],
            ],
            'background' => [
                'color_token' => ['required', Rule::in(array_keys(self::BACKGROUND_TOKENS))],
            ],
            'resolution' => [
                'width' => ['required', 'integer', 'between:320,3840'],
                'height' => ['required', 'integer', 'between:320,3840'],
            ],
            'device-classes' => [
                'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
                'platform' => ['required', Rule::in(array_keys(self::PLATFORMS))],
            ],
            'targets' => [
                'device_class_ids' => ['array'],
                'device_class_ids.*' => ['integer', Rule::exists('device_classes', 'id')],
                ...collect(MetricDefinitions::TARGET_FIELDS)->mapWithKeys(fn ($field, $key) => [
                    $key => ['required', 'numeric', "between:{$field['min']},{$field['max']}", ...($key === 'target_fps' ? ['integer'] : [])],
                ])->all(),
            ],
        };
    }

    /** Readable field names for validation messages. */
    public static function attributes(string $list): array
    {
        $names = [
            'fov' => 'field of view', 'distance_factor' => 'distance factor', 'elevation_deg' => 'elevation',
            'azimuth_deg' => 'azimuth', 'ambient_intensity' => 'ambient intensity', 'color_token' => 'color',
            'lights.*.intensity' => 'light intensity', 'lights.*.elevation_deg' => 'light elevation', 'lights.*.azimuth_deg' => 'light azimuth',
        ];
        foreach (MetricDefinitions::TARGET_FIELDS as $key => $field) {
            $names[$key] = strtolower($field['label']);
        }

        return $names;
    }

    /** Model attributes from validated form input. */
    public static function attributesFromInput(string $list, array $input): array
    {
        $definition = self::LISTS[$list];
        $attributes = ['name' => trim($input['name'])];

        if (isset($definition['type'])) {
            $attributes['type'] = $definition['type'];
            $attributes['values_json'] = match ($list) {
                'camera' => [
                    'fov' => (float) $input['fov'],
                    'position_mode' => $input['position_mode'],
                    'distance_factor' => (float) $input['distance_factor'],
                    'elevation_deg' => (float) $input['elevation_deg'],
                    'azimuth_deg' => (float) $input['azimuth_deg'],
                ],
                'lighting' => [
                    'ambient_intensity' => (float) $input['ambient_intensity'],
                    'directional_lights' => array_values(array_map(fn ($light) => [
                        'intensity' => (float) $light['intensity'],
                        'elevation_deg' => (float) $light['elevation_deg'],
                        'azimuth_deg' => (float) $light['azimuth_deg'],
                    ], $input['lights'])),
                    'environment_map' => (bool) ($input['environment_map'] ?? false),
                ],
                'background' => ['color_token' => $input['color_token']],
                'resolution' => ['width' => (int) $input['width'], 'height' => (int) $input['height']],
            };
        }

        if ($list === 'device-classes') {
            $attributes += ['category' => $input['category'], 'platform' => $input['platform']];
        }

        if ($list === 'targets') {
            $attributes += [
                'max_load_s' => (float) $input['max_load_s'],
                'target_fps' => (int) $input['target_fps'],
                'max_file_size_mb' => (float) $input['max_file_size_mb'],
            ];
        }

        return $attributes;
    }

    /** Current values for the form fields of an item (or the defaults for a new one). */
    public static function formValues(string $list, ?Model $item): array
    {
        $values = $item?->values_json ?? [];

        return ['name' => $item?->name ?? ''] + match ($list) {
            'camera' => [
                'fov' => $values['fov'] ?? 45, 'position_mode' => $values['position_mode'] ?? 'auto_frame',
                'distance_factor' => $values['distance_factor'] ?? 1.5, 'elevation_deg' => $values['elevation_deg'] ?? 20,
                'azimuth_deg' => $values['azimuth_deg'] ?? 45,
            ],
            'lighting' => [
                'ambient_intensity' => $values['ambient_intensity'] ?? 0.6,
                'lights' => $values['directional_lights'] ?? [['intensity' => 2.0, 'elevation_deg' => 45, 'azimuth_deg' => 45]],
                'environment_map' => $values['environment_map'] ?? false,
            ],
            'background' => ['color_token' => $values['color_token'] ?? 'neutral-100'],
            'resolution' => ['width' => $values['width'] ?? 1280, 'height' => $values['height'] ?? 720],
            'device-classes' => ['category' => $item?->category ?? 'phone', 'platform' => $item?->platform ?? 'any'],
            'targets' => [
                'device_class_ids' => $item?->deviceClasses->pluck('id')->all() ?? [],
                'max_load_s' => $item?->max_load_s ?? 3,
                'target_fps' => $item?->target_fps ?? 60,
                'max_file_size_mb' => $item?->max_file_size_mb ?? 20,
            ],
            default => [],
        };
    }

    /** One-line summary of an item's values for the list table. */
    public static function summary(string $list, Model $item): string
    {
        $v = $item->values_json ?? [];

        return match ($list) {
            'tools', 'objects' => '—',
            'camera' => sprintf('FOV %s° · %s · distance %s · %s° / %s°', $v['fov'], $v['position_mode'] === 'fixed' ? 'fixed' : 'auto-frame', $v['distance_factor'], $v['elevation_deg'], $v['azimuth_deg']),
            'lighting' => sprintf('Ambient %s · %d directional · environment map %s', $v['ambient_intensity'], count($v['directional_lights'] ?? []), ($v['environment_map'] ?? false) ? 'on' : 'off'),
            'background' => self::BACKGROUND_TOKENS[$v['color_token']] ?? $v['color_token'],
            'resolution' => "{$v['width']} × {$v['height']}",
            'device-classes' => self::CATEGORIES[$item->category].' · '.self::PLATFORMS[$item->platform],
            'targets' => sprintf('Loads in ≤ %s s · Smooth at %d fps · File ≤ %s MB · %d device %s',
                rtrim(rtrim(number_format($item->max_load_s, 2), '0'), '.'), $item->target_fps, rtrim(rtrim(number_format($item->max_file_size_mb, 2), '0'), '.'),
                $item->deviceClasses->count(), $item->deviceClasses->count() === 1 ? 'class' : 'classes'),
        };
    }

    /**
     * True when a stored record refers to the item, so it can only be deactivated (PRD.md §11).
     * Presets are copied into tests as snapshots, so tests never refer to them.
     */
    public static function isUsed(string $list, Model $item): bool
    {
        return match ($list) {
            'tools' => DB::table('assets')->where('tool_id', $item->id)->exists()
                || DB::table('test_session_assets')->where('group_tool_id', $item->id)->exists(),
            'objects' => DB::table('assets')->where('object_id', $item->id)->exists(),
            'device-classes' => DB::table('test_runs')->where('device_class_id', $item->id)->exists()
                || DB::table('device_class_target')->where('device_class_id', $item->id)->exists(),
            default => false,
        };
    }
}
