<?php

declare(strict_types=1);

namespace App\UploadedDocuments\Services;

use App\Models\PdfJob;
use App\Models\UploadedDocument;
use App\Models\User;
use App\UploadedDocuments\Contracts\DocumentStorageInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Handles document upload, deduplication by hash, storage, and job creation.
 */
final readonly class UploadedDocumentService
{
    private const MIME_PDF = 'application/pdf';

    public function __construct(
        private User $user,
        private DocumentStorageInterface $storage,
        private string $storageDiskDriver,
    ) {}

    /**
     * Stores PDF contents. Returns existing uuid if same hash exists for user.
     */
    public function uploadFromContents(string $contents): string
    {
        $fileHash = hash('sha256', $contents);
        $existing = $this->findByUserAndHash($fileHash);
        if ($existing !== null) {
            return $existing->uuid;
        }

        $uuid = (string) Str::uuid();
        $path = $this->relativePath($uuid);
        $this->storage->put($path, $contents);

        try {
            $document = DB::transaction(function () use ($uuid, $fileHash, $contents): UploadedDocument {
                $document = new UploadedDocument([
                    'uuid' => $uuid,
                    'storage_disk' => $this->storageDiskDriver,
                    'file_size_bytes' => strlen($contents),
                    'mime_type' => self::MIME_PDF,
                    'file_hash_sha256' => $fileHash,
                ]);
                $document->user_id = $this->user->id;
                $document->save();

                PdfJob::create([
                    'uploaded_document_id' => $document->id,
                    'status' => 'pending',
                ]);

                return $document;
            });

            return $document->uuid;
        } catch (QueryException) {
            $this->storage->delete($path);
            $existingAfterConflict = $this->findByUserAndHash($fileHash);
            if ($existingAfterConflict !== null) {
                return $existingAfterConflict->uuid;
            }

            throw new RuntimeException('Unable to persist uploaded document');
        } catch (Throwable) {
            $this->storage->delete($path);

            throw new RuntimeException('Unable to persist uploaded document');
        }
    }

    /**
     * Returns all documents for the current user ordered by created_at desc.
     *
     * @return Collection<int, UploadedDocument>
     */
    public function list(): Collection
    {
        return $this->baseQuery()
            ->orderByDesc('created_at')
            ->with('pdfJob')
            ->get();
    }

    /**
     * Returns the document for the current user by uuid or null.
     */
    public function getByUuid(string $uuid): ?UploadedDocument
    {
        return $this->baseQuery()
            ->where('uuid', $uuid)
            ->with('pdfJob')
            ->first();
    }

    /**
     * Opens a read stream for the document file. Caller must close the stream.
     *
     * @return resource
     */
    public function readStream(UploadedDocument $document)
    {
        $path = $this->relativePath($document->uuid);
        if (! $this->storage->exists($path)) {
            throw new RuntimeException('Document file does not exist in storage');
        }

        $stream = $this->storage->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException('Document file stream cannot be opened');
        }

        return $stream;
    }

    private function findByUserAndHash(string $fileHash): ?UploadedDocument
    {
        return $this->baseQuery()
            ->where('file_hash_sha256', $fileHash)
            ->first();
    }

    private function relativePath(string $uuid): string
    {
        return $this->user->id.'/'.$uuid.'.pdf';
    }

    private function baseQuery(): Builder
    {
        return UploadedDocument::withoutGlobalScope('user')
            ->where('user_id', $this->user->id);
    }
}
