<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\UploadItem;
use App\Models\UploadSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Staging of uploaded models (PRD.md §7.1). Validates by content, never by client file name,
 * stores files under generated names in private storage, and records one upload item per model.
 *
 * Layout: staging/{session}/{uuid}/{uuid}.glb, or {uuid}.gltf plus its dependents renamed to
 * {uuid}-{n}.{ext}; the stored .gltf is rewritten to reference the new names.
 */
class UploadService
{
    /** Effective maximum per file in bytes: the app setting, capped by the PHP limits. */
    public static function maxBytes(): int
    {
        $app = config('lab.asset_max_upload_mb') * 1024 * 1024;

        return min($app, self::iniBytes('upload_max_filesize'), self::iniBytes('post_max_size'));
    }

    /**
     * Stage one model with its dependents. Always returns an item: `staged`, or `invalid` with a reason.
     *
     * @param  array<int, UploadedFile>  $dependents
     */
    public function stage(UploadSession $session, UploadedFile $file, array $dependents, ?array $metrics, ?UploadedFile $thumbnail): UploadItem
    {
        $item = $session->items()->create([
            'original_filename' => Str::limit(basename($file->getClientOriginalName()), 250, ''),
            'file_size' => $file->getSize() + collect($dependents)->sum(fn (UploadedFile $d) => $d->getSize()),
            'status' => 'uploading',
        ]);

        try {
            $result = $this->validateAndStore($session, $item, $file, $dependents);
        } catch (UploadRejected $rejected) {
            $item->update(['status' => 'invalid', 'error_json' => ['file' => $rejected->getMessage()]]);

            return $item;
        }

        $item->update([
            'status' => 'staged',
            'format' => $result['format'],
            'staged_path' => $result['path'],
            'sha256' => $result['sha256'],
            'metrics_json' => $metrics,
            'thumbnail_path' => $thumbnail ? $this->storeThumbnail($result['dir'], $thumbnail) : null,
            'metadata_json' => ['name' => self::defaultName($item->original_filename)],
            'error_json' => null,
        ]);

        return $item;
    }

    /** A registered asset with the same file, uploaded by the same account (warning only). */
    public function duplicateOf(UploadItem $item, int $userId): ?Asset
    {
        return $item->sha256
            ? Asset::where('uploaded_by', $userId)->where('sha256', $item->sha256)->first()
            : null;
    }

    /** Remove a staged item: the files go, the record stays as `removed` (PRD.md §7.1). */
    public function remove(UploadItem $item): void
    {
        if ($item->staged_path) {
            Storage::disk('local')->deleteDirectory(dirname($item->staged_path));
        }
        $item->update(['status' => 'removed', 'staged_path' => null, 'thumbnail_path' => null]);
    }

    /** Asset name default: file name without extension; - and _ become spaces; each word capitalised. */
    public static function defaultName(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);

        return Str::of($base)->replace(['-', '_'], ' ')->squish()->title()->limit(150, '')->value() ?: 'Untitled';
    }

    /** @return array{format: string, path: string, dir: string, sha256: string} */
    private function validateAndStore(UploadSession $session, UploadItem $item, UploadedFile $file, array $dependents): array
    {
        $max = self::maxBytes();
        foreach ([$file, ...$dependents] as $upload) {
            if (! $upload->isValid()) {
                throw new UploadRejected("{$upload->getClientOriginalName()} did not upload completely.");
            }
            if ($upload->getSize() > $max) {
                throw new UploadRejected(sprintf('%s is larger than the maximum of %s MB.', $upload->getClientOriginalName(), round($max / 1048576, 1)));
            }
        }

        $bytes = file_get_contents($file->getRealPath());
        $format = $this->detectFormat($bytes);
        $uuid = (string) Str::uuid();
        $dir = "staging/{$session->id}/{$uuid}";
        $disk = Storage::disk('local');

        if ($format === 'glb') {
            if ($dependents !== []) {
                throw new UploadRejected('A GLB file contains everything it needs; extra files are not allowed with it.');
            }
            $disk->put("{$dir}/{$uuid}.glb", $bytes);

            return ['format' => 'glb', 'path' => "{$dir}/{$uuid}.glb", 'dir' => $dir, 'sha256' => hash('sha256', $bytes)];
        }

        // GLTF: every relative URI must be one of the dropped dependents; store them under new names.
        $gltf = json_decode($bytes, true);
        $byName = collect($dependents)->keyBy(fn (UploadedFile $d) => basename($d->getClientOriginalName()));
        $hash = hash_init('sha256');
        hash_update($hash, $bytes);
        $stored = [];
        $counter = 0;

        foreach (['buffers', 'images'] as $section) {
            foreach ($gltf[$section] ?? [] as $index => $entry) {
                $uri = $entry['uri'] ?? null;
                if ($uri === null || str_starts_with($uri, 'data:')) {
                    continue;
                }
                $name = basename(rawurldecode($uri));
                if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $uri) || str_contains(rawurldecode($uri), '..')) {
                    throw new UploadRejected("The GLTF references \"{$uri}\", which is not a file in the same folder.");
                }
                $dependent = $byName->get($name) ?? throw new UploadRejected("The GLTF needs \"{$name}\", but it was not in the drop. Drop the .gltf together with its .bin and texture files.");

                if (! isset($stored[$name])) {
                    $content = file_get_contents($dependent->getRealPath());
                    $type = $this->detectDependentType($content);
                    if ($section === 'images' && $type === 'bin') {
                        throw new UploadRejected("{$name} is used as a texture but is not a PNG, JPEG, WebP, or KTX2 image.");
                    }
                    $newName = $uuid.'-'.(++$counter).'.'.$type;
                    $disk->put("{$dir}/{$newName}", $content);
                    hash_update($hash, $content);
                    $stored[$name] = $newName;
                }
                $gltf[$section][$index]['uri'] = $stored[$name];
            }
        }

        $disk->put("{$dir}/{$uuid}.gltf", json_encode($gltf, JSON_UNESCAPED_SLASHES));

        return ['format' => 'gltf', 'path' => "{$dir}/{$uuid}.gltf", 'dir' => $dir, 'sha256' => hash_final($hash)];
    }

    /** GLB: magic "glTF", version 2, declared length = file size, JSON chunk with asset.version. GLTF: JSON with asset.version. */
    private function detectFormat(string $bytes): string
    {
        if (str_starts_with($bytes, 'glTF')) {
            if (strlen($bytes) < 20) {
                throw new UploadRejected('The GLB file is truncated.');
            }
            ['version' => $version, 'length' => $length] = unpack('Vversion/Vlength', substr($bytes, 4, 8));
            if ($version !== 2) {
                throw new UploadRejected("Only glTF 2.0 is supported; this GLB is version {$version}.");
            }
            if ($length !== strlen($bytes)) {
                throw new UploadRejected('The GLB file is truncated or damaged (its declared length does not match).');
            }
            ['chunkLength' => $chunkLength, 'chunkType' => $chunkType] = unpack('VchunkLength/VchunkType', substr($bytes, 12, 8));
            $json = $chunkType === 0x4E4F534A ? json_decode(substr($bytes, 20, $chunkLength), true) : null;
            if (! isset($json['asset']['version'])) {
                throw new UploadRejected('The GLB file has no valid glTF JSON header.');
            }

            return 'glb';
        }

        $json = json_decode($bytes, true);
        if (is_array($json) && isset($json['asset']['version'])) {
            if (! str_starts_with((string) $json['asset']['version'], '2')) {
                throw new UploadRejected('Only glTF 2.0 is supported.');
            }

            return 'gltf';
        }

        throw new UploadRejected('Not a GLB or GLTF file: the content is neither a binary glTF (starting with "glTF") nor glTF JSON with an asset version.');
    }

    /** Image types by signature; anything else is treated as a geometry buffer (.bin has no signature). */
    private function detectDependentType(string $bytes): string
    {
        return match (true) {
            str_starts_with($bytes, "\x89PNG\r\n\x1a\n") => 'png',
            str_starts_with($bytes, "\xFF\xD8\xFF") => 'jpg',
            str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => 'webp',
            str_starts_with($bytes, "\xABKTX 20\xBB\r\n\x1A\n") => 'ktx2',
            default => 'bin',
        };
    }

    private function storeThumbnail(string $dir, UploadedFile $thumbnail): ?string
    {
        $extension = match ($thumbnail->getMimeType()) {
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            default => null,
        };

        return $extension ? $thumbnail->storeAs($dir, 'thumbnail.'.$extension, 'local') : null;
    }

    private static function iniBytes(string $key): int
    {
        $value = trim((string) ini_get($key));
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number > 0 ? $number : PHP_INT_MAX,
        };
    }
}
