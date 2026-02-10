<?php

declare(strict_types=1);

namespace Tests\Unit\DiagnosticReport;

use App\DiagnosticReport\Requests\StoreDiagnosticReportRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class StoreDiagnosticReportRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        $request = new StoreDiagnosticReportRequest;
        $request->setContainer($this->app);

        return $request->rules();
    }

    public function test_notes_optional(): void
    {
        $v = Validator::make([], $this->rules());
        self::assertFalse($v->fails());
    }

    public function test_valid_payload_passes(): void
    {
        $v = Validator::make([
            'notes' => 'Fasting sample',
        ], $this->rules());
        self::assertFalse($v->fails());
    }
}
