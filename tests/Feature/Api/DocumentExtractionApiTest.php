<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Auth\Services\AuthService;
use App\Models\DiagnosticReport;
use App\Models\Observation;
use App\Models\UploadedDocument;
use App\Models\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class DocumentExtractionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        EncryptCookies::except(['access_token', 'refresh_token']);
    }

    private function withAuth(User $user): self
    {
        $tokens = $this->app->make(AuthService::class)->issueTokenPair($user);

        return $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access']);
    }

    private function createDocumentForUser(User $user): UploadedDocument
    {
        $doc = new UploadedDocument([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'storage_disk' => 'local',
            'file_size_bytes' => 100,
            'mime_type' => 'application/pdf',
            'file_hash_sha256' => str_repeat('a', 64),
        ]);
        $doc->user_id = $user->id;
        $doc->save();

        return $doc;
    }

    public function test_store_creates_report_with_document_and_observations(): void
    {
        $user = User::factory()->create([
            'email' => 'extract@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $document = $this->createDocumentForUser($user);

        $response = $this->withAuth($user)->postJson('/api/document-extraction', [
            'document_uuid' => $document->uuid,
            'title' => 'CBC Panel',
            'notes' => 'Fasting',
            'observations' => [
                [
                    'biomarker_name' => 'Hemoglobin',
                    'biomarker_code' => '718-7',
                    'value_type' => 'numeric',
                    'value' => 14.2,
                    'unit' => 'g/dL',
                ],
                [
                    'biomarker_name' => 'COVID Antigen',
                    'value_type' => 'boolean',
                    'value' => false,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('title', 'CBC Panel');
        $response->assertJsonPath('notes', 'Fasting');
        $response->assertJsonPath('document_uuids', [$document->uuid]);
        $response->assertJsonCount(2, 'observations');

        $report = DiagnosticReport::withoutGlobalScope('user')->where('user_id', $user->id)->first();
        self::assertNotNull($report);
        self::assertSame([$document->uuid], $report->uploadedDocuments()->pluck('uuid')->values()->all());
        self::assertSame(2, $report->observations()->count());
    }

    public function test_store_returns_404_when_document_not_owned(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $other = User::factory()->create([
            'email' => 'other@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $document = $this->createDocumentForUser($owner);

        $response = $this->withAuth($other)->postJson('/api/document-extraction', [
            'document_uuid' => $document->uuid,
            'title' => 'CBC',
            'observations' => [
                [
                    'biomarker_name' => 'Hemoglobin',
                    'value_type' => 'numeric',
                    'value' => 14,
                    'unit' => 'g/dL',
                ],
            ],
        ]);

        $response->assertStatus(404);
        self::assertSame(0, DiagnosticReport::withoutGlobalScope('user')->where('user_id', $other->id)->count());
    }

    public function test_store_returns_404_when_document_missing(): void
    {
        $user = User::factory()->create([
            'email' => 'extract-404@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        $response = $this->withAuth($user)->postJson('/api/document-extraction', [
            'document_uuid' => '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2fff',
            'observations' => [
                [
                    'biomarker_name' => 'Hemoglobin',
                    'value_type' => 'numeric',
                    'value' => 14,
                    'unit' => 'g/dL',
                ],
            ],
        ]);

        $response->assertStatus(404);
        self::assertSame(0, DiagnosticReport::withoutGlobalScope('user')->where('user_id', $user->id)->count());
    }

    public function test_store_returns_422_when_observations_invalid_and_creates_nothing(): void
    {
        $user = User::factory()->create([
            'email' => 'extract-422@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $document = $this->createDocumentForUser($user);

        $response = $this->withAuth($user)->postJson('/api/document-extraction', [
            'document_uuid' => $document->uuid,
            'observations' => [
                [
                    'biomarker_name' => 'Hemoglobin',
                    'value_type' => 'numeric',
                    'value' => 14.2,
                    'unit' => 'g/dL',
                ],
                [
                    'biomarker_name' => 'COVID Antigen',
                    'value_type' => 'boolean',
                    'value' => false,
                    'reference_range_min' => 0.0,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['observations.1.reference_range_min']);
        self::assertSame(0, DiagnosticReport::withoutGlobalScope('user')->where('user_id', $user->id)->count());
        self::assertSame(0, Observation::withoutGlobalScope('user')->where('user_id', $user->id)->count());
    }

    public function test_unauthenticated_request_receives_401(): void
    {
        $this->postJson('/api/document-extraction', [
            'document_uuid' => '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d',
            'observations' => [
                [
                    'biomarker_name' => 'X',
                    'value_type' => 'numeric',
                    'value' => 1,
                    'unit' => 'g/dL',
                ],
            ],
        ])->assertStatus(401);
    }
}
