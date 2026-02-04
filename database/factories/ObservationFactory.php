<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DiagnosticReport;
use App\Models\Observation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Observation>
 */
final class ObservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'diagnostic_report_id' => DiagnosticReport::factory(),
            'biomarker_name' => fake()->randomElement(['Hemoglobin', 'Hematocrit', 'Glucose', 'Cholesterol']),
            'biomarker_code' => fake()->optional(0.7)->numerify('###-#'),
            'original_value' => fake()->randomFloat(2, 1, 50),
            'original_unit' => fake()->randomElement(['g/dL', '%', 'mg/dL', 'mmol/L']),
            'normalized_value' => fake()->optional(0.6)->randomFloat(2, 1, 50),
            'normalized_unit' => fake()->optional(0.6)->randomElement(['g/dL', '%', 'mg/dL']),
            'reference_range_min' => fake()->optional(0.5)->randomFloat(2, 0, 20),
            'reference_range_max' => fake()->optional(0.5)->randomFloat(2, 20, 100),
            'reference_unit' => fake()->optional(0.5)->randomElement(['g/dL', '%']),
        ];
    }
}
