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
     * `@return` array<string, array<int, string>>
     */
    private function rules(): array
    {
        $request = new StoreDiagnosticReportRequest;
        $request->setContainer($this->app);

        return $request->rules();
    }

    public function test_report_type_required(): void
    {
        $v = Validator::make(
            ['report_type' => '', 'observations' => [['biomarker_name' => 'X', 'original_value' => 1, 'original_unit' => 'g/dL']]],
            $this->rules(),
        );
        $v->passes();
        self::assertTrue($v->errors()->has('report_type'));
    }

    public function test_observations_required_and_min_one(): void
    {
        $v = Validator::make(['report_type' => 'CBC', 'observations' => []], $this->rules());
        $v->passes();
        self::assertTrue($v->errors()->has('observations'));

        $v = Validator::make([
            'report_type' => 'CBC',
            'observations' => [
                ['biomarker_name' => 'Hemoglobin', 'original_value' => 14.2, 'original_unit' => 'g/dL'],
            ],
        ], $this->rules());
        self::assertFalse($v->fails());
    }

    public function test_observation_biomarker_name_required(): void
    {
        $v = Validator::make([
            'report_type' => 'CBC',
            'observations' => [
                ['biomarker_name' => '', 'original_value' => 14.2, 'original_unit' => 'g/dL'],
            ],
        ], $this->rules());
        self::assertTrue($v->errors()->has('observations.0.biomarker_name'));
    }

    public function test_observation_original_value_must_be_numeric(): void
    {
        $v = Validator::make([
            'report_type' => 'CBC',
            'observations' => [
                ['biomarker_name' => 'Hemoglobin', 'original_value' => 'invalid', 'original_unit' => 'g/dL'],
            ],
        ], $this->rules());
        self::assertTrue($v->errors()->has('observations.0.original_value'));
    }

    public function test_valid_payload_passes(): void
    {
        $v = Validator::make([
            'report_type' => 'CBC',
            'notes' => 'Fasting',
            'observations' => [
                [
                    'biomarker_name' => 'Hemoglobin',
                    'biomarker_code' => '718-7',
                    'original_value' => 14.2,
                    'original_unit' => 'g/dL',
                    'normalized_value' => 14.2,
                    'normalized_unit' => 'g/dL',
                    'reference_range_min' => 12,
                    'reference_range_max' => 16,
                    'reference_unit' => 'g/dL',
                ],
            ],
        ], $this->rules());
        self::assertFalse($v->fails());
    }
}
