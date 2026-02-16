<?php

declare(strict_types=1);

namespace App\UploadedDocuments\Services;

use App\Models\User;
use App\UploadedDocuments\Contracts\DocumentStorageInterface;

/**
 * Builds UploadedDocumentService for a given user with injected storage.
 */
final readonly class UploadedDocumentServiceFactory
{
    public function __construct(
        private DocumentStorageInterface $documentStorage,
    ) {}

    /**
     * Creates a document service scoped to the given user.
     */
    public function make(User $user): UploadedDocumentService
    {
        $diskName = config('filesystems.disks.uploaded_documents.driver', 'local');

        return new UploadedDocumentService($user, $this->documentStorage, $diskName);
    }
}
