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
    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        $request = new UpdateDiagnosticReportRequest;
        $request->setContainer($this->app);

        return $request->rules();
    }

    public function test_observations_can_be_empty(): void
    {
        $v = Validator::make(['observations' => []], $this->rules());
        self::assertFalse($v->fails());
    }

    public function test_observation_requires_biomarker_name_and_values(): void
    {
        $v = Validator::make([
            'observations' => [
                ['id' => 1, 'biomarker_name' => '', 'original_value' => 1, 'original_unit' => 'g/dL'],
            ],
        ], $this->rules());
        self::assertTrue($v->errors()->has('observations.0.biomarker_name'));
    }

    public function test_partial_update_payload_passes(): void
    {
        $v = Validator::make([
            'report_type' => 'Lipid Panel',
            'observations' => [
                [
                    'id' => 42,
                    'biomarker_name' => 'Hemoglobin',
                    'original_value' => 13.8,
                    'original_unit' => 'g/dL',
                    'reference_range_max' => 15.5,
                ],
                [
                    'biomarker_name' => 'Hematocrit',
                    'biomarker_code' => '4544-3',
                    'original_value' => 42.1,
                    'original_unit' => '%',
                ],
            ],
        ], $this->rules());
        self::assertFalse($v->fails());
    }
}
