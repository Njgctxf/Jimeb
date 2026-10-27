<?php

namespace App\Support;

final class ImageUpload
{
    public const MIME_EXTENSIONS = 'jpeg,png,jpg,gif,svg,webp';

    public const NULLABLE_MIMES_MAX_2048 = 'nullable|image|mimes:' . self::MIME_EXTENSIONS . '|max:2048';

    /** Photos et vidéos d'un plat : un seul champ, jusqu'à 50 Mo par fichier. */
    public const MEDIA_MIMES_MAX_50MB = 'file|mimes:jpeg,png,jpg,gif,webp,mp4,webm,mov,m4v|max:51200';

    public const IMAGE_MIMES_MAX_2048 = 'image|mimes:' . self::MIME_EXTENSIONS . '|max:2048';

    public static function mimesRule(int $maxKb = 2048, bool $nullable = true): string
    {
        $prefix = $nullable ? 'nullable|' : '';

        return $prefix . 'image|mimes:' . self::MIME_EXTENSIONS . '|max:' . $maxKb;
    }

    public static function requiredMimesRule(int $maxKb = 2048): string
    {
        return 'required|image|mimes:' . self::MIME_EXTENSIONS . '|max:' . $maxKb;
    }

    public static function imageMimesRule(int $maxKb = 2048): string
    {
        return 'image|mimes:' . self::MIME_EXTENSIONS . '|max:' . $maxKb;
    }
}
