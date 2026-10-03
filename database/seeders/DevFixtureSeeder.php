<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Registers the development models listed in fixtures/models/README.md as assets.
 * Verification environment only (AGENT.md §9). Derived browser metrics are not
 * available here, so no asset_metrics rows are written.
 */
class DevFixtureSeeder extends Seeder
{
    public function run(): void
    {
        $this->guardVerificationEnvironment();

        $uploader = User::where('role', 'admin')->orderBy('id')->first()
            ?? throw new RuntimeException('Run DatabaseSeeder first: no admin user exists.');

        foreach ($this->registerRows() as $row) {
            $source = base_path('fixtures/models/'.$row['file']);

            if (! is_file($source)) {
                $this->command?->warn("Skipped {$row['file']}: file not found.");

                continue;
            }

            $format = $this->detectFormat($source);
            if ($format === null) {
                $this->command?->warn("Skipped {$row['file']}: not a valid GLB or GLTF file.");

                continue;
            }

            // GLTF files with external .bin/textures need the upload grouping from M3.2.
            if ($format === 'gltf') {
                $this->command?->warn("Skipped {$row['file']}: GLTF fixtures are registered through upload (M3.2).");

                continue;
            }

            $tool = Tool::where('name', $row['tool'])->first();
            if ($tool === null) {
                $this->command?->warn("Skipped {$row['file']}: unknown tool \"{$row['tool']}\".");

                continue;
            }

            $sha256 = hash_file('sha256', $source);
            if (Asset::withTrashed()->where('sha256', $sha256)->where('uploaded_by', $uploader->id)->exists()) {
                continue;
            }

            $path = 'assets/web/'.Str::uuid().'.glb';
            Storage::disk('local')->put($path, fopen($source, 'rb'));

            Asset::create([
                'name' => $row['object'],
                'tool_id' => $tool->id,
                'author' => $row['author'],
                'uploaded_by' => $uploader->id,
                'description' => "Development fixture ({$row['file']}).",
                'production_time_hours' => $row['hours'],
                'format' => $format,
                'web_file_path' => $path,
                'file_size' => filesize($source),
                'sha256' => $sha256,
            ]);

            $this->command?->info("Registered {$row['file']}.");
        }
    }

    private function guardVerificationEnvironment(): void
    {
        $database = DB::connection()->getDatabaseName();
        $root = config('filesystems.disks.local.root');

        if ($database !== config('lab.verify_database') || ! str_starts_with($root, storage_path('verify'))) {
            throw new RuntimeException(
                "DevFixtureSeeder runs only against the verification environment. Database: {$database}; storage: {$root}."
            );
        }
    }

    /** @return list<array{file: string, object: string, tool: string, author: string, hours: float}> */
    private function registerRows(): array
    {
        $rows = [];
        $lines = file(base_path('fixtures/models/README.md'), FILE_IGNORE_NEW_LINES);

        foreach ($lines as $line) {
            // Data rows start with the file path in backticks: | `tool/file.glb` | ...
            if (! preg_match('/^\|\s*`([^`]+)`\s*\|/', $line)) {
                continue;
            }

            $cells = array_map(fn ($cell) => trim($cell, " `_\t"), array_slice(explode('|', $line), 1, 6));
            [$file, $object, $tool, $author, $hours] = $cells;

            $rows[] = [
                'file' => $file,
                'object' => $object,
                'tool' => $tool,
                'author' => $author !== '' ? $author : 'Unknown',
                'hours' => is_numeric($hours) ? (float) $hours : 0.0,
            ];
        }

        return $rows;
    }

    private function detectFormat(string $path): ?string
    {
        $handle = fopen($path, 'rb');
        $magic = fread($handle, 4);
        fclose($handle);

        if ($magic === 'glTF') {
            return 'glb';
        }

        $json = json_decode((string) file_get_contents($path), true);

        return isset($json['asset']['version']) ? 'gltf' : null;
    }
}
