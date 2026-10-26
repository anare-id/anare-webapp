<?php

declare(strict_types=1);

namespace App\Services;

use Intervention\Image\ImageManager;
use RuntimeException;

final class ImageOptimizer
{
    /** @return array{bytes: string, extension: string, width: int, height: int} */
    public function encode(string $path): array
    {
        $info = getimagesize($path);
        if ($info === false) {
            throw new RuntimeException('Gambar tidak dapat dibaca.');
        }
        $mime = $info['mime'];
        $original = file_get_contents($path);
        if ($original === false) {
            throw new RuntimeException('File gambar tidak dapat dibaca.');
        }
        // Preserve animation rather than flattening GIF/WebP to its first frame.
        if ($mime === 'image/gif' || ($mime === 'image/webp' && strpos($original, 'ANIM') !== false)) {
            return ['bytes' => $original, 'extension' => $mime === 'image/gif' ? 'gif' : 'webp', 'width' => $info[0], 'height' => $info[1]];
        }
        $image = ImageManager::gd(autoOrientation: false, decodeAnimation: false)->read($path);
        $orientation = (int) ($image->exif('IFD0.Orientation') ?? 1);
        $image->orient();
        $photo = $mime === 'image/jpeg';
        if ($photo) {
            $limit = (int) config('images.photo_max_dimension');
            $image->scaleDown(width: $limit, height: $limit);
        }
        // PNG/WebP illustrations keep dimensions and use lossless encoding.
        $bytes = (string) $image->toWebp(quality: $photo ? (int) config('images.photo_quality') : 100, strip: true);
        $extension = 'webp';
        // Do not enlarge already-efficient files; orientation/resizing still wins for photos.
        if (strlen($bytes) >= strlen($original) && $image->width() === $info[0] && $image->height() === $info[1] && $orientation === 1) {
            $bytes = $original;
            $extension = match ($mime) {
                'image/jpeg' => 'jpg', 'image/png' => 'png', default => 'webp'
            };
        }
        return ['bytes' => $bytes, 'extension' => $extension, 'width' => $image->width(), 'height' => $image->height()];
    }
}
