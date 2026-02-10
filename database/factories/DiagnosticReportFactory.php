<?php

declare(strict_types=1);

namespace Database\Factories;

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
            'title' => fake()->optional(0.7)->sentence(3),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
