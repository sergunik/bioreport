<?php

declare(strict_types=1);

namespace Tests\Unit\DiagnosticReport;

use App\Models\Observation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ObservationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_reference_range_returns_true_when_min_set(): void
    {
        $obs = new Observation([
            'reference_range_min' => 10.0,
            'reference_range_max' => null,
        ]);
        $obs->id = 1;
        self::assertTrue($obs->hasReferenceRange());
    }

    public function test_has_reference_range_returns_true_when_max_set(): void
    {
        $obs = new Observation([
            'reference_range_min' => null,
            'reference_range_max' => 20.0,
        ]);
        $obs->id = 1;
        self::assertTrue($obs->hasReferenceRange());
    }

    public function test_has_reference_range_returns_false_when_both_null(): void
    {
        $obs = new Observation([
            'reference_range_min' => null,
            'reference_range_max' => null,
        ]);
        $obs->id = 1;
        self::assertFalse($obs->hasReferenceRange());
    }

    public function test_value_accessor_returns_numeric_value_for_numeric_type(): void
    {
        $obs = new Observation([
            'value_type' => 'numeric',
            'value_number' => 10.5,
        ]);

        self::assertSame(10.5, $obs->value);
    }

    public function test_value_accessor_returns_boolean_value_for_boolean_type(): void
    {
        $obs = new Observation([
            'value_type' => 'boolean',
            'value_boolean' => true,
        ]);

        self::assertTrue($obs->value);
    }

    public function test_value_accessor_returns_text_value_for_text_type(): void
    {
        $obs = new Observation([
            'value_type' => 'text',
            'value_text' => 'negative',
        ]);

        self::assertSame('negative', $obs->value);
    }
}
