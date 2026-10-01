<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pictures attached to rules and todos, on the default disk (FILESYSTEM_DISK:
 * `public` self-hosted, S3/R2 in cloud). A todo shares its rule's file: paths
 * are copied, files never are.
 *
 * ponytail: files are never deleted (replaced/removed images, deleted rules
 * leak a few KB each); add a sweep of unreferenced paths if storage matters.
 */
class Images
{
    public const MAX_KB = 5120;

    public function store(UploadedFile $file): string
    {
        return $file->store('images');
    }

    public function url(?string $path): ?string
    {
        return $path === null ? null : Storage::url($path);
    }

    /**
     * Copies a remote image (a hub pack's) onto our own disk. Anything that is
     * not a reasonably small image is dropped rather than failing the import.
     */
    public function fetch(?string $url): ?string
    {
        if ($url === null || ! Str::startsWith($url, ['https://', 'http://'])) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get($url);
            $type = (string) $response->header('Content-Type');
            $extension = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'][$type] ?? null;

            if (! $response->successful() || $extension === null || strlen($response->body()) > self::MAX_KB * 1024) {
                return null;
            }

            $path = 'images/'.Str::random(40).'.'.$extension;
            Storage::put($path, $response->body());

            return $path;
        } catch (Throwable) {
            return null;
        }
    }
}
