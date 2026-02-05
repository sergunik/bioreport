<?php

declare(strict_types=1);

namespace App\Models;

use App\DiagnosticReport\Enums\ReportSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * Single lab report (e.g. CBC, Lipid Panel). User-scoped via global scope.
 */
final class DiagnosticReport extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'report_type',
        'source',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => ReportSource::class,
        ];
    }

    protected static function booted(): void
    {
        self::addGlobalScope('user', function (Builder $builder): void {
            $userId = Auth::guard('jwt')->id();
            if ($userId === null) {
                $builder->whereRaw('1 = 0');
                return;
            }
            $builder->where('diagnostic_reports.user_id', $userId);
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
     * @return HasMany<Observation, $this>
     */
    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }
}
