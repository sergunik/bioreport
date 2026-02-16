<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Queue record for PDF processing. Consumed by Python worker.
 */
final class PdfJob extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uploaded_document_id',
        'status',
        'attempts',
        'error_message',
        'locked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'locked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<UploadedDocument, $this>
     */
    public function uploadedDocument(): BelongsTo
    {
        return $this->belongsTo(UploadedDocument::class);
    }
}
