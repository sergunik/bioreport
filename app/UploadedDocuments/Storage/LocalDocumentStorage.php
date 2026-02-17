<?php

declare(strict_types=1);

namespace App\UploadedDocuments\Storage;

use App\UploadedDocuments\Contracts\DocumentStorageInterface;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Local disk implementation of document storage using Laravel filesystem.
 */
final readonly class LocalDocumentStorage implements DocumentStorageInterface
{
    public function __construct(
        private Filesystem $disk,
    ) {}

    public function put(string $path, string $contents): void
    {
        $this->disk->put($path, $contents);
    }

    /**
     * @return resource
     */
    public function readStream(string $path)
    {
        return $this->disk->readStream($path);
    }

    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }

    public function delete(string $path): void
    {
        $this->disk->delete($path);
    }
}
