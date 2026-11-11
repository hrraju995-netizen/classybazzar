<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class Media
{
    /**
     * Turn a stored path into a browser-usable URL. Absolute URLs are passed
     * through untouched so seeded/remote images keep working.
     */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }

    /**
     * @param  array<int, string>|null  $paths
     * @return array<int, string>
     */
    public static function urls(?array $paths): array
    {
        return collect($paths ?? [])
            ->map(fn ($path) => self::url($path))
            ->filter()
            ->values()
            ->all();
    }
}
