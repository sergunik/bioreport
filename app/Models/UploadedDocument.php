<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

/**
 * Uploaded PDF document with optional ML results. User-scoped via global scope.
 */
final class UploadedDocument extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'storage_disk',
        'file_size_bytes',
        'mime_type',
        'file_hash_sha256',
        'parsed_result',
        'anonymised_result',
        'anonymised_artifacts',
        'normalized_result',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'anonymised_artifacts' => 'array',
            'normalized_result' => 'array',
            'processed_at' => 'datetime',
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
            $builder->where('uploaded_documents.user_id', $userId);
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
     * @return HasOne<PdfJob, $this>
     */
    public function pdfJob(): HasOne
    {
        return $this->hasOne(PdfJob::class);
    }
}
