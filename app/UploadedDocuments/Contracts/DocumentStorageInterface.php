<?php

declare(strict_types=1);

namespace App\UploadedDocuments\Contracts;

/**
 * Abstraction for storing and retrieving uploaded document files.
 */
interface DocumentStorageInterface
{
    public function put(string $path, string $contents): void;

    /**
     * @return resource
     */
    public function readStream(string $path);

    public function exists(string $path): bool;

    public function delete(string $path): void;
}
