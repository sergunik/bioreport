<?php

declare(strict_types=1);

namespace Tests\Unit\UploadedDocuments;

use App\Models\PdfJob;
use App\Models\UploadedDocument;
use App\Models\User;
use App\UploadedDocuments\Contracts\DocumentStorageInterface;
use App\UploadedDocuments\Services\UploadedDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Tests\TestCase;

final class UploadedDocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploaded_documents');
    }

    /**
     * @throws Exception
     */
    public function test_upload_creates_document_and_job(): void
    {
        $user = User::factory()->create();
        $storage = $this->createMock(DocumentStorageInterface::class);
        $storage->expects(self::once())->method('put')->with(
            self::stringContains((string) $user->id),
            self::identicalTo('pdf content')
        );
        $storage->expects(self::any())->method('exists')->willReturn(false);

        $service = new UploadedDocumentService($user, $storage, 'local');
        $uuid = $service->uploadFromContents('pdf content');

        self::assertNotEmpty($uuid);

        $doc = UploadedDocument::withoutGlobalScope('user')->where('user_id', $user->id)->first();
        self::assertNotNull($doc);
        self::assertSame($uuid, $doc->uuid);
        self::assertSame(11, $doc->file_size_bytes);
        self::assertNotNull(PdfJob::where('uploaded_document_id', $doc->id)->first());
    }

    /**
     * @throws Exception
     */
    public function test_upload_duplicate_returns_existing_uuid_and_creates_no_job(): void
    {
        $user = User::factory()->create();
        $content = 'same content';
        $hash = hash('sha256', $content);
        $existing = UploadedDocument::withoutGlobalScope('user')->create([
            'uuid' => 'existing-uuid-123',
            'user_id' => $user->id,
            'storage_disk' => 'local',
            'file_size_bytes' => strlen($content),
            'mime_type' => 'application/pdf',
            'file_hash_sha256' => $hash,
        ]);

        $storage = $this->createMock(DocumentStorageInterface::class);
        $storage->expects(self::never())->method('put');

        $service = new UploadedDocumentService($user, $storage, 'local');
        $uuid = $service->uploadFromContents($content);

        self::assertSame($existing->uuid, $uuid);

        $jobCount = PdfJob::count();
        self::assertSame(0, $jobCount);
    }

    /**
     * @throws Exception
     */
    public function test_upload_same_hash_for_different_users_creates_two_documents(): void
    {
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();
        $content = 'same-content-for-two-users';

        $storage = $this->createMock(DocumentStorageInterface::class);
        $storage->expects(self::exactly(2))->method('put');

        $serviceOne = new UploadedDocumentService($userOne, $storage, 'local');
        $serviceTwo = new UploadedDocumentService($userTwo, $storage, 'local');

        $uuidOne = $serviceOne->uploadFromContents($content);
        $uuidTwo = $serviceTwo->uploadFromContents($content);

        self::assertNotSame($uuidOne, $uuidTwo);
        self::assertSame(2, UploadedDocument::withoutGlobalScope('user')->count());
        self::assertSame(2, PdfJob::count());
    }

    /**
     * @throws Exception
     */
    public function test_read_stream_throws_when_file_missing_in_storage(): void
    {
        $user = User::factory()->create();
        $document = UploadedDocument::withoutGlobalScope('user')->create([
            'uuid' => '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d',
            'user_id' => $user->id,
            'storage_disk' => 'local',
            'file_size_bytes' => 100,
            'mime_type' => 'application/pdf',
            'file_hash_sha256' => str_repeat('a', 64),
        ]);

        $storage = $this->createMock(DocumentStorageInterface::class);
        $storage->expects(self::once())->method('exists')->willReturn(false);
        $storage->expects(self::never())->method('readStream');

        $service = new UploadedDocumentService($user, $storage, 'local');

        $this->expectException(RuntimeException::class);
        $service->readStream($document);
    }
}
