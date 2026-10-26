<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class ImageOptimizationTest extends TestCase
{
    public function test_large_photo_is_webp_resized_without_distorting_ratio(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 3000, 1500);
        $result = app(ImageOptimizer::class)->encode($file->getPathname());
        $this->assertSame('webp', $result['extension']);
        $this->assertSame(2560, $result['width']);
        $this->assertSame(1280, $result['height']);
        $this->assertLessThan($file->getSize(), strlen($result['bytes']));
        $this->assertSame('image/webp', getimagesizefromstring($result['bytes'])['mime']);
    }

    public function test_transparent_illustration_retains_alpha_and_exact_color(): void
    {
        $image = imagecreatetruecolor(80, 60);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagesetpixel($image, 20, 20, imagecolorallocatealpha($image, 25, 90, 160, 40));
        ob_start();
        imagepng($image);
        $file = UploadedFile::fake()->createWithContent('logo.png', ob_get_clean());
        $result = app(ImageOptimizer::class)->encode($file->getPathname());
        $decoded = imagecreatefromstring($result['bytes']);
        $this->assertSame(80, imagesx($decoded));
        $this->assertSame(60, imagesy($decoded));
        $this->assertSame(127, imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0))['alpha']);
        $color = imagecolorsforindex($decoded, imagecolorat($decoded, 20, 20));
        $this->assertSame(25, $color['red']);
        $this->assertSame(90, $color['green']);
        $this->assertSame(160, $color['blue']);
        $this->assertEqualsWithDelta(40, $color['alpha'], 1);
    }

    public function test_gif_is_not_flattened_or_reencoded(): void
    {
        $file = UploadedFile::fake()->image('animation.gif', 20, 20);
        $result = app(ImageOptimizer::class)->encode($file->getPathname());
        $this->assertSame('gif', $result['extension']);
        $this->assertSame(file_get_contents($file->getPathname()), $result['bytes']);
    }

    public function test_exif_orientation_is_applied_before_encoding(): void
    {
        $file = UploadedFile::fake()->image('rotated.jpg', 80, 40);
        $jpeg = file_get_contents($file->getPathname());
        $exif = "Exif\0\0".'II'.pack('v', 42).pack('V', 8).pack('v', 1)
            .pack('v', 0x0112).pack('v', 3).pack('V', 1).pack('v', 6).pack('v', 0).pack('V', 0);
        $oriented = UploadedFile::fake()->createWithContent('rotated.jpg', substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2));
        $result = app(ImageOptimizer::class)->encode($oriented->getPathname());
        $this->assertSame(40, $result['width']);
        $this->assertSame(80, $result['height']);
    }
}
