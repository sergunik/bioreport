<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Auth\Services\AuthService;
use App\Models\DiagnosticReport;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class DiagnosticReportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        EncryptCookies::except(['access_token', 'refresh_token']);
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

    public function test_store_creates_report_without_observations(): void
    {
        $user = User::factory()->create([
            'email' => 'report-create@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        $response = $this->withAuth($user)->postJson('/api/diagnostic-reports', [
            'notes' => 'Fasting sample, morning draw',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('notes', 'Fasting sample, morning draw');
        $response->assertJsonCount(0, 'observations');

        $report = DiagnosticReport::withoutGlobalScope('user')->where('user_id', $user->id)->first();
        self::assertNotNull($report);
        self::assertSame(0, $report->observations()->count());
    }

    public function test_index_returns_only_authenticated_user_reports(): void
    {
        $user = User::factory()->create([
            'email' => 'report-list@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $other = User::factory()->create(['email' => 'other@example.com']);

        DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
        ]);
        DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $other->id,
        ]);

        $response = $this->withAuth($user)->getJson('/api/diagnostic-reports');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_show_returns_report_when_owner(): void
    {
        $user = User::factory()->create([
            'email' => 'report-show@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withAuth($user)->getJson('/api/diagnostic-reports/'.$report->id);

        $response->assertStatus(200);
        $response->assertJsonPath('id', $report->id);
    }

    public function test_show_returns_404_when_not_owner(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $other = User::factory()->create([
            'email' => 'other-show@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->withAuth($other)->getJson('/api/diagnostic-reports/'.$report->id);

        $response->assertStatus(404);
    }

    public function test_show_returns_404_when_missing(): void
    {
        $user = User::factory()->create([
            'email' => 'report-404@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        $response = $this->withAuth($user)->getJson('/api/diagnostic-reports/99999');

        $response->assertStatus(404);
    }

    public function test_update_notes_only(): void
    {
        $user = User::factory()->create([
            'email' => 'report-update@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
            'notes' => 'Original',
        ]);

        $response = $this->withAuth($user)->patchJson('/api/diagnostic-reports/'.$report->id, [
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('notes', 'Updated notes');

        $report->refresh();
        self::assertSame('Updated notes', $report->notes);
    }

    public function test_update_returns_404_when_not_owner(): void
    {
        $owner = User::factory()->create(['email' => 'owner-update@example.com']);
        $other = User::factory()->create([
            'email' => 'other-update@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->withAuth($other)->patchJson('/api/diagnostic-reports/'.$report->id, [
            'notes' => 'Updated',
        ]);

        $response->assertStatus(404);
    }

    public function test_destroy_deletes_report_and_cascades_to_observations(): void
    {
        $user = User::factory()->create([
            'email' => 'report-destroy@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
        ]);
        $obs = Observation::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
            'diagnostic_report_id' => $report->id,
            'biomarker_name' => 'Hemoglobin',
            'value' => 14,
            'unit' => 'g/dL',
        ]);

        $response = $this->withAuth($user)->deleteJson('/api/diagnostic-reports/'.$report->id);

        $response->assertStatus(204);
        self::assertNull(DiagnosticReport::withoutGlobalScope('user')->find($report->id));
        self::assertNull(Observation::withoutGlobalScope('user')->find($obs->id));
    }

    public function test_destroy_returns_404_when_not_owner(): void
    {
        $owner = User::factory()->create(['email' => 'owner-del@example.com']);
        $other = User::factory()->create([
            'email' => 'other-del@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->withAuth($other)->deleteJson('/api/diagnostic-reports/'.$report->id);

        $response->assertStatus(404);
        self::assertNotNull(DiagnosticReport::withoutGlobalScope('user')->find($report->id));
    }

    public function test_unauthenticated_requests_receive_401(): void
    {
        $this->postJson('/api/diagnostic-reports', [
            'notes' => 'Some notes',
        ])->assertStatus(401);

        $this->getJson('/api/diagnostic-reports')->assertStatus(401);
    }
}
