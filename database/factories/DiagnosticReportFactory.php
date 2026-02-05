<?php

declare(strict_types=1);

namespace Database\Factories;

use App\DiagnosticReport\Enums\ReportSource;
use App\Models\DiagnosticReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiagnosticReport>
 */
final class DiagnosticReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'report_type' => fake()->randomElement(['CBC', 'Lipid Panel', 'Metabolic Panel']),
            'source' => ReportSource::Manual,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
