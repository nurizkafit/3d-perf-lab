<?php

namespace App\Support;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/** Paths and file operations for a registered asset (assets/web/{uuid}/ + assets/thumbnails/{uuid}.ext). */
class AssetFiles
{
    public static function directory(Asset $asset): string
    {
        return dirname($asset->web_file_path);
    }

    /** One file of the asset by its stored name (the model or a GLTF dependent), or null. */
    public static function file(Asset $asset, string $name): ?string
    {
        if ($name !== basename($name)) {
            return null;
        }
        $path = self::directory($asset).'/'.$name;

        return Storage::disk('local')->exists($path) ? $path : null;
    }

    /** Download: the GLB itself, or a ZIP with the .gltf and its dependents. */
    public static function download(Asset $asset): BinaryFileResponse
    {
        $disk = Storage::disk('local');
        $base = Str::slug($asset->name) ?: 'asset';

        if ($asset->format === 'glb') {
            return response()->download($disk->path($asset->web_file_path), "{$base}.glb");
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'asset').'.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Could not create the download archive.');
        }
        foreach ($disk->files(self::directory($asset)) as $file) {
            $zip->addFile($disk->path($file), basename($file));
        }
        $zip->close();

        return response()->download($zipPath, "{$base}.zip")->deleteFileAfterSend();
    }

    public static function delete(Asset $asset): void
    {
        $disk = Storage::disk('local');
        $disk->deleteDirectory(self::directory($asset));
        if ($asset->thumbnail_path) {
            $disk->delete($asset->thumbnail_path);
        }
    }
}
