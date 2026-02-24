<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Auth\Services\AuthService;
use App\Models\PdfJob;
use App\Models\UploadedDocument;
use App\Models\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DocumentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        EncryptCookies::except(['access_token', 'refresh_token']);
        Storage::fake('uploaded_documents');
    }

    private function authTokens(User $user): array
    {
        return $this->app->make(AuthService::class)->issueTokenPair($user);
    }

    private function withAuth(User $user): self
    {
        $tokens = $this->authTokens($user);

        return $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access']);
    }

    private function createUploadedDocumentForUser(
        User $user,
        string $uuid,
        int $fileSizeBytes,
        string $fileHashSha256,
        ?string $parsedResult = null,
        ?string $anonymisedResult = null,
        ?array $anonymisedArtifacts = null,
        ?array $normalizedResult = null
    ): UploadedDocument {
        $doc = new UploadedDocument([
            'uuid' => $uuid,
            'storage_disk' => 'local',
            'file_size_bytes' => $fileSizeBytes,
            'mime_type' => 'application/pdf',
            'file_hash_sha256' => $fileHashSha256,
            'parsed_result' => $parsedResult,
            'anonymised_result' => $anonymisedResult,
            'anonymised_artifacts' => $anonymisedArtifacts,
            'normalized_result' => $normalizedResult,
        ]);
        $doc->user_id = $user->id;
        $doc->save();

        return $doc;
    }

    public function test_store_creates_document_and_job(): void
    {
        $user = User::factory()->create([
            'email' => 'doc-upload@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $response = $this->withAuth($user)->post('/api/documents', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['uuid']);

        $doc = UploadedDocument::withoutGlobalScope('user')->where('user_id', $user->id)->first();
        self::assertNotNull($doc);
        self::assertSame($response->json('uuid'), $doc->uuid);
        self::assertNotNull(PdfJob::where('uploaded_document_id', $doc->id)->first());
    }

    public function test_store_duplicate_returns_existing_and_creates_no_new_job(): void
    {
        $user = User::factory()->create([
            'email' => 'doc-dup@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $content = '%PDF-1.4\n%âãÏÓ\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n';
        $file1 = UploadedFile::fake()->createWithContent('report.pdf', $content);

        $firstResponse = $this->withAuth($user)->post('/api/documents', ['file' => $file1], ['Accept' => 'application/json']);
        $firstResponse->assertStatus(201);
        $firstUuid = $firstResponse->json('uuid');

        $file2 = UploadedFile::fake()->createWithContent('other.pdf', $content);
        $file2->mimeType('application/pdf');
        $secondResponse = $this->withAuth($user)->post('/api/documents', ['file' => $file2], ['Accept' => 'application/json']);

        $secondResponse->assertStatus(201);
        self::assertSame($firstUuid, $secondResponse->json('uuid'));

        $docCount = UploadedDocument::withoutGlobalScope('user')->where('user_id', $user->id)->count();
        self::assertSame(1, $docCount);
        $jobCount = PdfJob::count();
        self::assertSame(1, $jobCount);
    }

    public function test_store_rejects_non_pdf(): void
    {
        $user = User::factory()->create([
            'email' => 'doc-invalid@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $file = UploadedFile::fake()->create('report.txt', 100, 'text/plain');

        $response = $this->withAuth($user)->post('/api/documents', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_rejects_file_over_max_size(): void
    {
        config(['uploaded_documents.max_size_kb' => 1]);

        $user = User::factory()->create([
            'email' => 'doc-too-large@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $file = UploadedFile::fake()->create('report.pdf', 2, 'application/pdf');

        $response = $this->withAuth($user)->post('/api/documents', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_requires_file(): void
    {
        $user = User::factory()->create([
            'email' => 'doc-nofile@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        $response = $this->withAuth($user)->post('/api/documents', [], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(422);
    }

    public function test_index_returns_only_authenticated_user_documents(): void
    {
        $user = User::factory()->create([
            'email' => 'doc-list@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $other = User::factory()->create(['email' => 'other-doc@example.com']);

        $this->createUploadedDocumentForUser($user, '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d', 100, str_repeat('a', 64));
        $this->createUploadedDocumentForUser($other, '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c4e', 200, str_repeat('b', 64));

        $response = $this->withAuth($user)->getJson('/api/documents');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.uuid', '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d');
    }

    public function test_show_streams_pdf_for_owner(): void
    {
        $user = User::factory()->create([
            'email' => 'doc-stream@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $uuid = '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d';
        $this->createUploadedDocumentForUser($user, $uuid, 100, str_repeat('a', 64));
        Storage::disk('uploaded_documents')->put(
            $user->id.'/'.$uuid.'.pdf',
            'binary pdf content'
        );

        $response = $this->withAuth($user)->get('/api/documents/'.$uuid);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $cacheControl = (string) $response->headers->get('Cache-Control');
        self::assertStringContainsString('private', $cacheControl);
        self::assertStringContainsString('no-store', $cacheControl);
        self::assertStringContainsString('no-cache', $cacheControl);
        self::assertStringContainsString('must-revalidate', $cacheControl);
        self::assertSame('binary pdf content', $response->streamedContent());
    }

    public function test_show_returns_404_when_storage_file_missing(): void
    {
        $user = User::factory()->create([
            'email' => 'doc-stream-missing@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $uuid = '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d';
        $this->createUploadedDocumentForUser($user, $uuid, 100, str_repeat('a', 64));

        $response = $this->withAuth($user)->get('/api/documents/'.$uuid);

        $response->assertStatus(404);
    }

    public function test_show_returns_404_when_not_owner(): void
    {
        $owner = User::factory()->create(['email' => 'owner-stream@example.com']);
        $other = User::factory()->create([
            'email' => 'other-stream@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $uuid = '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d';
        $this->createUploadedDocumentForUser($owner, $uuid, 100, str_repeat('a', 64));

        $response = $this->withAuth($other)->get('/api/documents/'.$uuid);

        $response->assertStatus(404);
    }

    public function test_metadata_returns_document_metadata_for_owner(): void
    {
        $user = User::factory()->create([
            'email' => 'doc-meta@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $uuid = '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d';
        $this->createUploadedDocumentForUser(
            $user,
            $uuid,
            256,
            str_repeat('a', 64),
            'parsed-text',
            'anonymised-text',
            ['entities' => []],
            ['key' => 'normalized']
        );

        $response = $this->withAuth($user)->getJson('/api/documents/'.$uuid.'/metadata');

        $response->assertStatus(200);
        $response->assertJsonPath('uuid', $uuid);
        $response->assertJsonPath('file_size_bytes', 256);
        $response->assertJsonPath('parsed_result', 'parsed-text');
        $response->assertJsonPath('anonymised_result', 'anonymised-text');
        $response->assertJsonPath('anonymised_artifacts.entities', []);
        $response->assertJsonPath('normalized_result.key', 'normalized');
    }

    public function test_metadata_returns_404_when_not_owner(): void
    {
        $owner = User::factory()->create(['email' => 'owner-meta@example.com']);
        $other = User::factory()->create([
            'email' => 'other-meta@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $uuid = '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d';
        $this->createUploadedDocumentForUser($owner, $uuid, 100, str_repeat('a', 64));

        $response = $this->withAuth($other)->getJson('/api/documents/'.$uuid.'/metadata');

        $response->assertStatus(404);
    }

    public function test_unauthenticated_requests_receive_401(): void
    {
        $this->post('/api/documents', [], ['Accept' => 'application/json'])->assertStatus(401);
        $this->getJson('/api/documents')->assertStatus(401);
    }

    public function test_show_with_invalid_uuid_format_returns_404(): void
    {
        $user = User::factory()->create([
            'email' => 'doc-invalid-uuid@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        $response = $this->withAuth($user)->get('/api/documents/not-a-uuid');

        $response->assertStatus(404);
    }
}
