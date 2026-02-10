<?php

declare(strict_types=1);

namespace Tests\Unit\DiagnosticReport;

use App\DiagnosticReport\Requests\UpdateDiagnosticReportRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class UpdateDiagnosticReportRequestTest extends TestCase
{
    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        $request = new UpdateDiagnosticReportRequest;
        $request->setContainer($this->app);

        return $request->rules();
    }

    public function test_notes_optional(): void
    {
        $v = Validator::make([], $this->rules());
        self::assertFalse($v->fails());
    }

    public function test_partial_update_payload_passes(): void
    {
        $v = Validator::make([
            'title' => 'Updated title',
            'notes' => 'Updated notes',
        ], $this->rules());
        self::assertFalse($v->fails());
    }
}
