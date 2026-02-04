<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Auth\Services\AuthService;
use App\DiagnosticReport\Enums\ReportSource;
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

    public function test_store_creates_report_with_observations(): void
    {
        $user = User::factory()->create([
            'email' => 'report-create@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        $response = $this->withAuth($user)->postJson('/api/diagnostic-reports', [
            'report_type' => 'CBC',
            'notes' => 'Fasting sample, morning draw',
            'observations' => [
                [
                    'biomarker_name' => 'Hemoglobin',
                    'biomarker_code' => '718-7',
                    'original_value' => 14.2,
                    'original_unit' => 'g/dL',
                    'normalized_value' => 14.2,
                    'normalized_unit' => 'g/dL',
                    'reference_range_min' => 12.0,
                    'reference_range_max' => 16.0,
                    'reference_unit' => 'g/dL',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('report_type', 'CBC');
        $response->assertJsonPath('notes', 'Fasting sample, morning draw');
        $response->assertJsonPath('source', 'manual');
        $response->assertJsonCount(1, 'observations');
        $response->assertJsonPath('observations.0.biomarker_name', 'Hemoglobin');
        $response->assertJsonPath('observations.0.original_value', 14.2);

        $report = DiagnosticReport::withoutGlobalScope('user')->where('user_id', $user->id)->first();
        self::assertNotNull($report);
        self::assertSame(1, $report->observations()->count());
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
            'report_type' => 'Mine',
            'source' => ReportSource::Manual,
        ]);
        DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $other->id,
            'report_type' => 'Other',
            'source' => ReportSource::Manual,
        ]);

        $response = $this->withAuth($user)->getJson('/api/diagnostic-reports');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.report_type', 'Mine');
    }

    public function test_show_returns_report_when_owner(): void
    {
        $user = User::factory()->create([
            'email' => 'report-show@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
            'report_type' => 'CBC',
            'source' => ReportSource::Manual,
        ]);

        $response = $this->withAuth($user)->getJson('/api/diagnostic-reports/'.$report->id);

        $response->assertStatus(200);
        $response->assertJsonPath('id', $report->id);
        $response->assertJsonPath('report_type', 'CBC');
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
            'report_type' => 'CBC',
            'source' => ReportSource::Manual,
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

    public function test_update_upserts_observations_and_deletes_missing(): void
    {
        $user = User::factory()->create([
            'email' => 'report-update@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
            'report_type' => 'CBC',
            'source' => ReportSource::Manual,
        ]);
        $obs1 = $report->observations()->create([
            'biomarker_name' => 'Hemoglobin',
            'biomarker_code' => '718-7',
            'original_value' => 14.2,
            'original_unit' => 'g/dL',
        ]);
        $obs2 = $report->observations()->create([
            'biomarker_name' => 'Hematocrit',
            'biomarker_code' => '4544-3',
            'original_value' => 42.0,
            'original_unit' => '%',
        ]);

        $response = $this->withAuth($user)->patchJson('/api/diagnostic-reports/'.$report->id, [
            'observations' => [
                [
                    'id' => $obs1->id,
                    'biomarker_name' => 'Hemoglobin',
                    'biomarker_code' => '718-7',
                    'original_value' => 13.8,
                    'original_unit' => 'g/dL',
                    'reference_range_max' => 15.5,
                ],
                [
                    'biomarker_name' => 'New Biomarker',
                    'original_value' => 5.0,
                    'original_unit' => 'mmol/L',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'observations');

        $obs1->refresh();
        self::assertSame(13.8, (float) $obs1->original_value);
        self::assertSame(15.5, (float) $obs1->reference_range_max);

        self::assertNull(Observation::query()->find($obs2->id));

        $newObs = $report->observations()->where('biomarker_name', 'New Biomarker')->first();
        self::assertNotNull($newObs);
        self::assertSame(5.0, (float) $newObs->original_value);
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
            'report_type' => 'CBC',
            'source' => ReportSource::Manual,
        ]);

        $response = $this->withAuth($other)->patchJson('/api/diagnostic-reports/'.$report->id, [
            'report_type' => 'Updated',
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
            'report_type' => 'CBC',
            'source' => ReportSource::Manual,
        ]);
        $obs = $report->observations()->create([
            'biomarker_name' => 'Hemoglobin',
            'original_value' => 14,
            'original_unit' => 'g/dL',
        ]);

        $response = $this->withAuth($user)->deleteJson('/api/diagnostic-reports/'.$report->id);

        $response->assertStatus(204);
        self::assertNull(DiagnosticReport::withoutGlobalScope('user')->find($report->id));
        self::assertNull(Observation::query()->find($obs->id));
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
            'report_type' => 'CBC',
            'source' => ReportSource::Manual,
        ]);

        $response = $this->withAuth($other)->deleteJson('/api/diagnostic-reports/'.$report->id);

        $response->assertStatus(404);
        self::assertNotNull(DiagnosticReport::withoutGlobalScope('user')->find($report->id));
    }

    public function test_unauthenticated_requests_receive_401(): void
    {
        $this->postJson('/api/diagnostic-reports', [
            'report_type' => 'CBC',
            'observations' => [['biomarker_name' => 'X', 'original_value' => 1, 'original_unit' => 'g/dL']],
        ])->assertStatus(401);

        $this->getJson('/api/diagnostic-reports')->assertStatus(401);
    }
}
