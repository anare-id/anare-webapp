<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class OptimizeImages extends Command
{
    protected $signature = 'images:optimize';
    protected $description = 'Generate optimized public image copies; keep source assets unchanged';

    public function handle(ImageOptimizer $optimizer): int
    {
        $directory = public_path('images/optimized');
        File::ensureDirectoryExists($directory);
        File::put($directory.'/.htaccess', "<IfModule mod_headers.c>\n<FilesMatch \"^[a-f0-9]{64}\\.(webp|png|jpg)$\">\nHeader set Cache-Control \"public, max-age=31536000, immutable\"\n</FilesMatch>\n</IfModule>\n");
        $manifest = [];
        $originalBytes = $optimizedBytes = 0;
        foreach (File::files(public_path('images')) as $file) {
            if (!in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'], true)) {
                continue;
            }
            $result = $optimizer->encode($file->getPathname());
            $originalBytes += $file->getSize();
            $optimizedBytes += strlen($result['bytes']);
            if (strlen($result['bytes']) >= $file->getSize()) {
                continue;
            }
            $name = hash('sha256', $result['bytes']).'.'.$result['extension'];
            File::put($directory.'/'.$name, $result['bytes']);
            $manifest['/images/'.$file->getFilename()] = '/images/optimized/'.$name;
        }
        File::put($directory.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $this->table(['Images optimized', 'Source bytes', 'Served bytes'], [[count($manifest), $originalBytes, $optimizedBytes]]);
        return self::SUCCESS;
    }
}
