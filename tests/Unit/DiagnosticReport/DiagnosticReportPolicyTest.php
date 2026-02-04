<?php

declare(strict_types=1);

namespace Tests\Unit\DiagnosticReport;

use App\DiagnosticReport\Policies\DiagnosticReportPolicy;
use App\Models\DiagnosticReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiagnosticReportPolicyTest extends TestCase
{
    use RefreshDatabase;

    private DiagnosticReportPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DiagnosticReportPolicy;
    }

    public function test_view_allowed_for_owner(): void
    {
        $user = User::factory()->create();
        $report = new DiagnosticReport(['user_id' => $user->id]);
        $report->id = 1;

        self::assertTrue($this->policy->view($user, $report));
    }

    public function test_view_denied_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $report = new DiagnosticReport(['user_id' => $owner->id]);
        $report->id = 1;

        self::assertFalse($this->policy->view($other, $report));
    }

    public function test_update_denied_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $report = new DiagnosticReport(['user_id' => $owner->id]);
        $report->id = 1;

        self::assertFalse($this->policy->update($other, $report));
    }

    public function test_delete_denied_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $report = new DiagnosticReport(['user_id' => $owner->id]);
        $report->id = 1;

        self::assertFalse($this->policy->delete($other, $report));
    }
}
