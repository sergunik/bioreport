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

final class ObservationApiTest extends TestCase
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

    public function test_store_creates_observation_for_report(): void
    {
        $user = User::factory()->create([
            'email' => 'obs-store@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withAuth($user)->postJson('/api/diagnostic-reports/'.$report->id.'/observations', [
            'biomarker_name' => 'Hemoglobin',
            'biomarker_code' => '718-7',
            'value' => 14.2,
            'unit' => 'g/dL',
            'reference_range_min' => 12.0,
            'reference_range_max' => 16.0,
            'reference_unit' => 'g/dL',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('biomarker_name', 'Hemoglobin');
        $response->assertJsonPath('value', 14.2);
        $response->assertJsonPath('unit', 'g/dL');

        $observation = Observation::withoutGlobalScope('user')
            ->where('diagnostic_report_id', $report->id)
            ->where('user_id', $user->id)
            ->first();
        self::assertNotNull($observation);
        self::assertSame('Hemoglobin', $observation->biomarker_name);
    }

    public function test_store_returns_404_when_report_not_owner(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $other = User::factory()->create([
            'email' => 'other@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->withAuth($other)->postJson('/api/diagnostic-reports/'.$report->id.'/observations', [
            'biomarker_name' => 'Hemoglobin',
            'value' => 14.2,
            'unit' => 'g/dL',
        ]);

        $response->assertStatus(404);
        self::assertSame(0, Observation::withoutGlobalScope('user')->where('diagnostic_report_id', $report->id)->count());
    }

    public function test_show_returns_observation_when_owner(): void
    {
        $user = User::factory()->create([
            'email' => 'obs-show@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create(['user_id' => $user->id]);
        $observation = Observation::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
            'diagnostic_report_id' => $report->id,
            'biomarker_name' => 'Glucose',
            'value_type' => 'numeric',
            'value_number' => 5.2,
            'unit' => 'mmol/L',
        ]);

        $response = $this->withAuth($user)->getJson('/api/observations/'.$observation->id);

        $response->assertStatus(200);
        $response->assertJsonPath('id', $observation->id);
        $response->assertJsonPath('biomarker_name', 'Glucose');
    }

    public function test_show_returns_404_when_not_owner(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $other = User::factory()->create([
            'email' => 'other-show@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create(['user_id' => $owner->id]);
        $observation = Observation::withoutGlobalScope('user')->create([
            'user_id' => $owner->id,
            'diagnostic_report_id' => $report->id,
            'biomarker_name' => 'Glucose',
            'value_type' => 'numeric',
            'value_number' => 5.2,
            'unit' => 'mmol/L',
        ]);

        $response = $this->withAuth($other)->getJson('/api/observations/'.$observation->id);

        $response->assertStatus(404);
    }

    public function test_update_returns_404_when_not_owner(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $other = User::factory()->create([
            'email' => 'other-update@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create(['user_id' => $owner->id]);
        $observation = Observation::withoutGlobalScope('user')->create([
            'user_id' => $owner->id,
            'diagnostic_report_id' => $report->id,
            'biomarker_name' => 'Glucose',
            'value_type' => 'numeric',
            'value_number' => 5.2,
            'unit' => 'mmol/L',
        ]);

        $response = $this->withAuth($other)->patchJson('/api/observations/'.$observation->id, [
            'value' => 6.0,
        ]);

        $response->assertStatus(404);
    }

    public function test_update_observation(): void
    {
        $user = User::factory()->create([
            'email' => 'obs-update@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create(['user_id' => $user->id]);
        $observation = Observation::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
            'diagnostic_report_id' => $report->id,
            'biomarker_name' => 'Glucose',
            'value_type' => 'numeric',
            'value_number' => 5.2,
            'unit' => 'mmol/L',
        ]);

        $response = $this->withAuth($user)->patchJson('/api/observations/'.$observation->id, [
            'value' => 5.5,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('value', 5.5);

        $observation->refresh();
        self::assertSame(5.5, (float) $observation->value_number);
    }

    public function test_destroy_observation(): void
    {
        $user = User::factory()->create([
            'email' => 'obs-destroy@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create(['user_id' => $user->id]);
        $observation = Observation::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
            'diagnostic_report_id' => $report->id,
            'biomarker_name' => 'Glucose',
            'value_type' => 'numeric',
            'value_number' => 5.2,
            'unit' => 'mmol/L',
        ]);

        $response = $this->withAuth($user)->deleteJson('/api/observations/'.$observation->id);

        $response->assertStatus(204);
        self::assertNull(Observation::withoutGlobalScope('user')->find($observation->id));
    }

    public function test_store_creates_boolean_observation(): void
    {
        $user = User::factory()->create([
            'email' => 'obs-store-bool@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withAuth($user)->postJson('/api/diagnostic-reports/'.$report->id.'/observations', [
            'biomarker_name' => 'Pregnancy Test',
            'value_type' => 'boolean',
            'value' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('value_type', 'boolean');
        $response->assertJsonPath('value', true);
        $response->assertJsonPath('unit', null);
    }

    public function test_store_returns_422_for_numeric_without_unit(): void
    {
        $user = User::factory()->create([
            'email' => 'obs-store-422@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withAuth($user)->postJson('/api/diagnostic-reports/'.$report->id.'/observations', [
            'biomarker_name' => 'Hemoglobin',
            'value_type' => 'numeric',
            'value' => 14.2,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['unit']);
    }

    public function test_store_returns_422_for_reference_range_on_non_numeric(): void
    {
        $user = User::factory()->create([
            'email' => 'obs-store-422-ref@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withAuth($user)->postJson('/api/diagnostic-reports/'.$report->id.'/observations', [
            'biomarker_name' => 'Pregnancy Test',
            'value_type' => 'boolean',
            'value' => false,
            'reference_range_min' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reference_range_min']);
    }

    public function test_update_allows_value_type_change_and_clears_reference_fields(): void
    {
        $user = User::factory()->create([
            'email' => 'obs-update-type@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create(['user_id' => $user->id]);
        $observation = Observation::withoutGlobalScope('user')->create([
            'user_id' => $user->id,
            'diagnostic_report_id' => $report->id,
            'biomarker_name' => 'CRP',
            'value_type' => 'numeric',
            'value_number' => 5.2,
            'unit' => 'mg/L',
            'reference_range_min' => 0.0,
            'reference_range_max' => 5.0,
            'reference_unit' => 'mg/L',
        ]);

        $response = $this->withAuth($user)->patchJson('/api/observations/'.$observation->id, [
            'value_type' => 'text',
            'value' => 'negative',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('value_type', 'text');
        $response->assertJsonPath('value', 'negative');
        $response->assertJsonPath('reference_range_min', null);
        $response->assertJsonPath('reference_range_max', null);
        $response->assertJsonPath('reference_unit', null);
    }

    public function test_store_batch_creates_observations_transactionally(): void
    {
        $user = User::factory()->create([
            'email' => 'obs-batch@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create(['user_id' => $user->id]);

        $response = $this->withAuth($user)->postJson('/api/diagnostic-reports/'.$report->id.'/observations/batch', [
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
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonCount(2);
        self::assertSame(2, Observation::withoutGlobalScope('user')->where('diagnostic_report_id', $report->id)->count());
    }

    public function test_store_batch_rolls_back_when_one_item_invalid(): void
    {
        $user = User::factory()->create([
            'email' => 'obs-batch-rollback@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);
        $report = DiagnosticReport::withoutGlobalScope('user')->create(['user_id' => $user->id]);

        $response = $this->withAuth($user)->postJson('/api/diagnostic-reports/'.$report->id.'/observations/batch', [
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
        self::assertSame(0, Observation::withoutGlobalScope('user')->where('diagnostic_report_id', $report->id)->count());
    }

    public function test_unauthenticated_requests_receive_401(): void
    {
        $this->postJson('/api/diagnostic-reports/1/observations', [
            'biomarker_name' => 'X',
            'value' => 1,
            'unit' => 'g/dL',
        ])->assertStatus(401);

        $this->getJson('/api/observations/1')->assertStatus(401);
    }
}
