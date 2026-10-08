<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetObject;
use App\Models\Tool;
use App\Models\UploadItem;
use App\Models\UploadSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Register staged upload items as assets (PRD.md §7.1 step 3). Each row is validated on its own:
 * valid rows become assets, invalid rows stay staged with their errors (partial success).
 */
class AssetRegistrationService
{
    /**
     * @param  array<int|string, array<string, mixed>>  $rows  metadata keyed by upload item id
     * @return array{registered: array<int, int>, errors: array<int, array<string, list<string>>>}
     */
    public function register(UploadSession $session, User $user, array $rows): array
    {
        $registered = [];
        $errors = [];

        $items = $session->items()->where('status', 'staged')->whereIn('id', array_keys($rows))->get();

        foreach ($items as $item) {
            $input = $this->normalise($rows[$item->id] ?? [], $user);
            $item->update(['metadata_json' => $input]);

            $validator = Validator::make($input, $this->rules($user), [], [
                'tool_id' => 'tool', 'object_id' => 'object', 'production_time_hours' => 'production time',
            ]);

            if ($validator->fails()) {
                $errors[$item->id] = $validator->errors()->toArray();
                $item->update(['error_json' => $errors[$item->id]]);

                continue;
            }

            $registered[$item->id] = $this->createAsset($item, $user, $validator->validated())->id;
        }

        $this->completeIfDone($session);

        return ['registered' => $registered, 'errors' => $errors];
    }

    /** Author: admins choose it; for users the system sets the display name and ignores input (PRD.md §4). */
    public function normalise(array $row, User $user): array
    {
        return [
            'name' => trim((string) ($row['name'] ?? '')),
            'author' => $user->can('setAuthor', Asset::class) ? trim((string) ($row['author'] ?? '')) : $user->name,
            'tool_id' => $row['tool_id'] ?? null,
            'object_id' => $row['object_id'] ?? null,
            'production_time_hours' => $row['production_time_hours'] ?? null,
            'description' => isset($row['description']) && trim((string) $row['description']) !== '' ? trim((string) $row['description']) : null,
        ];
    }

    private function rules(User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'author' => ['required', 'string', 'max:100'],
            'tool_id' => ['required', 'integer', Rule::exists(Tool::class, 'id')->where('is_active', true)],
            // Required for every new upload (decision D52).
            'object_id' => ['required', 'integer', Rule::exists(AssetObject::class, 'id')->where('is_active', true)],
            'production_time_hours' => ['required', 'numeric', 'min:0', 'max:10000'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function createAsset(UploadItem $item, User $user, array $data): Asset
    {
        $disk = Storage::disk('local');
        $stagedDir = dirname($item->staged_path);
        $uuid = basename($stagedDir);
        $webDir = "assets/web/{$uuid}";
        $moved = [];

        try {
            $thumbnail = null;
            if ($item->thumbnail_path && $disk->exists($item->thumbnail_path)) {
                $thumbnail = "assets/thumbnails/{$uuid}.".pathinfo($item->thumbnail_path, PATHINFO_EXTENSION);
                $disk->move($item->thumbnail_path, $thumbnail);
                $moved[$thumbnail] = $item->thumbnail_path;
            }
            foreach ($disk->files($stagedDir) as $file) {
                $target = $webDir.'/'.basename($file);
                $disk->move($file, $target);
                $moved[$target] = $file;
            }

            return DB::transaction(function () use ($item, $user, $data, $webDir, $thumbnail) {
                $asset = Asset::create([
                    'name' => $data['name'],
                    'tool_id' => $data['tool_id'],
                    'object_id' => $data['object_id'],
                    'author' => $data['author'],
                    'uploaded_by' => $user->id,
                    'description' => $data['description'],
                    'production_time_hours' => $data['production_time_hours'],
                    'format' => $item->format,
                    'web_file_path' => $webDir.'/'.basename($item->staged_path),
                    'thumbnail_path' => $thumbnail,
                    'file_size' => $item->file_size,
                    'sha256' => $item->sha256,
                ]);

                $metrics = $this->metrics($item->metrics_json);
                if ($metrics !== null) {
                    $asset->metrics()->create($metrics);
                }

                $item->update(['status' => 'registered', 'asset_id' => $asset->id, 'staged_path' => null, 'thumbnail_path' => null, 'error_json' => null]);

                return $asset;
            });
        } catch (Throwable $exception) {
            foreach ($moved as $target => $source) {
                $disk->move($target, $source);
            }
            throw $exception;
        } finally {
            if ($disk->exists($stagedDir) && $disk->files($stagedDir) === []) {
                $disk->deleteDirectory($stagedDir);
            }
        }
    }

    /** Browser-derived metrics (PRD.md §7.5), kept only when every count is a sane non-negative integer. */
    private function metrics(?array $metrics): ?array
    {
        if (! $metrics) {
            return null;
        }

        $counts = [];
        foreach (['vertices', 'triangles', 'meshes', 'materials', 'textures'] as $key) {
            $value = $metrics[$key] ?? null;
            if (! is_int($value) || $value < 0) {
                return null;
            }
            $counts[$key] = $value;
        }

        return $counts + [
            'texture_dimensions_json' => array_values(array_filter($metrics['texture_dimensions'] ?? [], fn ($pair) => is_array($pair) && count($pair) === 2)),
            'texture_memory_estimate_bytes' => max(0, (int) ($metrics['texture_memory_estimate'] ?? 0)),
        ];
    }

    private function completeIfDone(UploadSession $session): void
    {
        if (! $session->items()->whereIn('status', ['uploading', 'staged'])->exists()) {
            $session->update(['status' => 'completed', 'completed_at' => now()]);
        }
    }
}
