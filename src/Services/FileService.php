<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class FileService
{
    public function prepareOutputDirectory(string $directory): void
    {
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        File::cleanDirectory($directory);
    }

    public function isDirectory(string $path): bool
    {
        return File::isDirectory($path);
    }

    public function writeFile(string $path, string $content): void
    {
        File::put($path, $content);
    }

    public function get(string $path): string
    {
        if (! File::exists($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        return File::get($path);
    }

    public function getPhpFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = new \SplFileInfo($file->getPathname());
            }
        }

        return $files;
    }
}
