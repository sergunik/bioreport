<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single measured biomarker within a diagnostic report (original + normalized values).
 */
final class Observation extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'diagnostic_report_id',
        'biomarker_name',
        'biomarker_code',
        'original_value',
        'original_unit',
        'normalized_value',
        'normalized_unit',
        'reference_range_min',
        'reference_range_max',
        'reference_unit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_value' => 'decimal:5',
            'normalized_value' => 'decimal:5',
            'reference_range_min' => 'decimal:5',
            'reference_range_max' => 'decimal:5',
        ];
    }

    /**
     * @return BelongsTo<DiagnosticReport, $this>
     */
    public function diagnosticReport(): BelongsTo
    {
        return $this->belongsTo(DiagnosticReport::class);
    }

    public function hasReferenceRange(): bool
    {
        return $this->reference_range_min !== null || $this->reference_range_max !== null;
    }

    public function isNormalized(): bool
    {
        return $this->normalized_value !== null && $this->normalized_unit !== null;
    }
}
