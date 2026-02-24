<?php

declare(strict_types=1);

namespace App\UploadedDocuments\Resources;

use App\Models\UploadedDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for document metadata and ML results.
 *
 * @mixin UploadedDocument
 */
final class DocumentMetadataResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'file_size_bytes' => $this->file_size_bytes,
            'mime_type' => $this->mime_type,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'job_status' => $this->relationLoaded('pdfJob') ? $this->pdfJob?->status : null,
            'parsed_result' => $this->parsed_result,
            'anonymised_result' => $this->anonymised_result,
            'anonymised_artifacts' => $this->anonymised_artifacts,
            'normalized_result' => $this->normalized_result,
        ];
    }
}
