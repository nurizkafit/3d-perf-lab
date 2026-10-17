<?php

namespace App\Support;

use App\Models\Report;
use Illuminate\Support\Number;

/**
 * The public report data boundary (SITEMAP.md §7). Builds the only data a public page may use: titles, texts,
 * method, motion scenarios, presets and Target values, aggregated results, labels, Target outcomes, best markers,
 * asset names/tools/objects/authors/metrics, device class, an environment summary, and token URLs for approved
 * assets. Never: database IDs, emails, uploader accounts, storage paths, unapproved asset URLs, raw samples, drafts.
 * Assets and groups are keyed by their position ("a1", "g1"), not by ID.
 */
class PublicReportPresenter
{
    /** Gallery card data. */
    public static function card(Report $report): array
    {
        $test = $report->test;
        $run = $test->latestCompletedRun();

        return [
            'slug' => $report->public_slug,
            'title' => $report->title,
            'question' => $report->question,
            'method' => $test->method,
            'device_class' => $run?->deviceClass?->name,
            'asset_count' => $test->assets()->count(),
            'published_at' => $report->published_at,
        ];
    }

    public static function present(Report $report): array
    {
        $test = $report->test()->with(['assets.tool', 'assets.object', 'assets.metrics'])->first();
        $run = $test->latestCompletedRun();
        $run?->load('deviceClass');
        $c = $test->configuration_json;
        $tokens = $report->publicAssets()->get()->mapWithKeys(fn ($asset) => [$asset->id => $asset->pivot->token]);
        $keys = $test->assets->values()->mapWithKeys(fn ($asset, $i) => [$asset->id => 'a'.($i + 1)]);
        // A GLTF needs its file name in the URL, so its .bin and textures resolve to the same token folder.
        $url = fn ($asset) => isset($tokens[$asset->id])
            ? route('reports.asset', array_filter([$report->public_slug, $tokens[$asset->id], $asset->format === 'gltf' ? basename($asset->web_file_path) : null]))
            : null;

        $assets = $test->assets->map(fn ($asset) => [
            'key' => $keys[$asset->id],
            'name' => $asset->name,
            'tool' => $asset->tool->name,
            'object' => $asset->object?->name,
            'author' => $asset->author,
            'format' => strtoupper($asset->format),
            'file_size' => Number::fileSize($asset->file_size, maxPrecision: 2),
            'vertices' => $asset->metrics?->vertices,
            'triangles' => $asset->metrics?->triangles,
            'approved' => isset($tokens[$asset->id]),
            'url' => $url($asset),
        ])->values()->all();

        $data = [
            'slug' => $report->public_slug,
            'title' => $report->title,
            'question' => $report->question,
            'conclusion' => $report->conclusion,
            'limitations' => [...ReportLimitations::STANDARD, ...ReportLimitations::authorLines($report->limitations)],
            'published_at' => $report->published_at,
            'method' => $test->method,
            'scenario' => $test->scenario,
            'motions' => $c['motions'],
            'device_class' => $run?->deviceClass?->name,
            'target' => array_intersect_key($test->target_json, array_flip(['name', 'max_load_s', 'target_fps', 'max_file_size_mb'])),
            'configuration' => self::configuration($test->method, $test->scenario, $c),
            'environment' => self::environment($run?->environment_json ?? []),
            'assets' => $assets,
            'results' => null,
            'reproduction' => null,
        ];

        if ($run === null) {
            return $data;
        }

        if ($test->method === 'crowd') {
            $results = CrowdResults::for($test, $run);
            $groupKey = fn ($index) => 'g'.($index + 1);
            $groups = collect($results['groups'])->values();
            $data['results'] = [
                'cap_copies' => $results['cap_copies'],
                'objects' => $results['objects'],
                'largest' => $results['largest'],
                'groups' => $groups->map(fn ($g, $i) => [
                    'key' => $groupKey($i),
                    'label' => $g['label'],
                    'assets' => $g['assets']->map(fn ($asset) => $keys[$asset->id])->values()->all(),
                    'motions' => collect($g['motions'])->map(fn ($m) => [
                        'copies' => $m['copies'] ? array_intersect_key($m['copies'], array_flip(['median', 'min', 'max', 'n'])) : null,
                        'at_cap' => $m['at_cap'],
                        'time_limit' => $m['time_limit'],
                        'probes' => collect($m['probes'][1] ?? [])->map(fn ($p) => ['copies' => $p['copies'], 'label' => $p['label']])->values()->all(),
                    ])->all(),
                ])->all(),
                'markers' => collect($results['markers'])->map(fn ($marker) => [
                    'winners' => array_map(fn ($key) => $groupKey($groups->search(fn ($g) => $g['key'] === $key)), $marker['winners']),
                    'too_close' => $marker['too_close'],
                ])->all(),
            ];
            // Crowd reproduction: only groups whose assets are all approved; each starts at its Smooth count.
            $data['reproduction'] = [
                'camera' => $c['camera']['values'] ?? null,
                'lighting' => $c['lighting']['values'] ?? null,
                'background' => $c['background']['values'] ?? null,
                'lookCopies' => 16,
                'groups' => $groups->filter(fn ($g) => $g['assets']->every(fn ($asset) => isset($tokens[$asset->id])))->map(fn ($g) => [
                    'label' => $g['label'],
                    'assets' => $g['assets']->map(fn ($asset) => ['url' => $url($asset)])->values()->all(),
                    'defaults' => collect($g['motions'])->map(fn ($m) => (int) round($m['copies']['median'] ?? 1))->all(),
                ])->values()->all(),
            ];

            return $data;
        }

        $results = SideBySideResults::for($test, $run);
        $marker = fn ($m) => ['winners' => array_map(fn ($id) => $keys[$id], $m['winners']), 'too_close' => $m['too_close']];
        $data['results'] = [
            'assets' => collect($results['assets'])->map(fn ($entry, $id) => [
                'key' => $keys[$id],
                'checks' => $entry['checks'],
                'meets_all' => $entry['meets_all'],
                'labels' => $entry['labels'],
                'cold' => $entry['cold'],
                'warm_median' => $entry['warm']['median'] ?? null,
                'phases' => $entry['phases'],
                'markers' => $entry['markers'],
                'work_p95' => collect($entry['metrics'])->map(fn ($m) => $m['work_p95_ms'] ?? null)->all(),
                'interval_p95' => collect($entry['metrics'])->map(fn ($m) => $m['interval_p95_ms'] ?? null)->all(),
                'unstable' => $entry['unstable'],
            ])->values()->all(),
            'markers' => [
                'fastest_load' => $marker($results['markers']['fastest_load']),
                'smallest_file' => $marker($results['markers']['smallest_file']),
                'lightest' => collect($results['markers']['lightest'])->map($marker)->all(),
            ],
        ];
        // Side-by-Side reproduction: linked panes; an unapproved asset shows a placeholder with its metrics.
        $data['reproduction'] = [
            'camera' => $c['camera']['values'] ?? null,
            'lighting' => $c['lighting']['values'] ?? null,
            'background' => $c['background']['values'] ?? null,
            'motions' => $c['motions'],
            'assets' => collect($assets)->filter(fn ($a) => $a['approved'])->map(fn ($a) => ['id' => $a['key'], 'name' => $a['name'], 'url' => $a['url']])->values()->all(),
        ];

        return $data;
    }

    private static function configuration(string $method, ?string $scenario, array $c): array
    {
        $motions = collect($c['motions'])->map(fn ($m) => MetricDefinitions::MOTIONS[$m]['label'])->join(', ');
        $rows = ['Method' => MetricDefinitions::METHODS[$method]['label']];
        if ($scenario) {
            $rows['Scenario'] = $scenario === 'per_tool' ? 'Per tool' : 'Per asset';
        }
        $rows['Motion scenarios'] = $motions;
        $rows['Repetitions'] = $c['repetitions'];
        if ($method === 'crowd') {
            $rows['Cap'] = number_format($c['cap']).' instances';
            $rows['Probe'] = "{$c['probe_warmup_s']} s warm-up (Physics {$c['physics_probe_warmup_s']} s), {$c['probe_window_s']} s window; {$c['time_limit_s']} s per search";
            $rows['Instance mode'] = $c['instance_mode'];
        } else {
            $rows['Warm-up'] = "{$c['warmup_s']} s";
            $rows['Measurement'] = "{$c['measurement_s']} s per motion scenario";
            $rows['Animation'] = ($c['animation'] ?? false) ? 'On (first clip)' : 'Off';
        }
        foreach (['resolution' => 'Resolution', 'camera' => 'Camera', 'lighting' => 'Lighting', 'background' => 'Background'] as $key => $label) {
            $rows[$label] = $c[$key]['name'] ?? 'Default';
        }

        return $rows;
    }

    /** Environment summary only: browser, OS, GPU string when exposed, refresh rate, Three.js version. */
    private static function environment(array $env): array
    {
        return array_filter([
            'Browser' => $env['user_agent'] ?? null,
            'Operating system' => $env['user_agent_data']['platform'] ?? ($env['platform'] ?? null),
            'GPU (browser-reported)' => $env['gpu_renderer'] ?? null,
            'Refresh rate estimate' => isset($env['refresh_hz_estimate']) ? $env['refresh_hz_estimate'].' Hz' : null,
            'Three.js revision' => $env['three_revision'] ?? null,
            'Drawing buffer' => isset($env['resolution']['width']) ? $env['resolution']['width'].' × '.$env['resolution']['height'] : null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
