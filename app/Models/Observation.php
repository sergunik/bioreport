<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Single measured biomarker within a diagnostic report.
 */
final class Observation extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'diagnostic_report_id',
        'biomarker_name',
        'biomarker_code',
        'value_type',
        'value_number',
        'value_boolean',
        'value_text',
        'unit',
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
            'value_number' => 'decimal:5',
            'value_boolean' => 'boolean',
            'reference_range_min' => 'decimal:5',
            'reference_range_max' => 'decimal:5',
        ];
    }

    public function getValueAttribute(): float|bool|string|null
    {
        return match ($this->value_type) {
            'numeric' => $this->value_number !== null ? (float) $this->value_number : null,
            'boolean' => $this->value_boolean,
            'text' => $this->value_text,
            default => null,
        };
    }

    protected static function booted(): void
    {
        self::addGlobalScope('user', function (Builder $builder): void {
            $userId = Auth::guard('jwt')->id();
            if ($userId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }
            $builder->where('observations.user_id', $userId);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
