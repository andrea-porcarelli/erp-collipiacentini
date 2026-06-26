<?php

namespace App\Support;

use Illuminate\Support\Str;

class ImageAltNormalizer
{
    public static function normalize(?string $fileName, ?string $fallback = null): string
    {
        if (! $fileName) {
            return trim((string) $fallback);
        }

        $base = pathinfo($fileName, PATHINFO_FILENAME);

        if ($base === '' || $base === null) {
            return trim((string) $fallback);
        }

        $headline = Str::headline($base);

        return $headline !== '' ? $headline : trim((string) $fallback);
    }
}
