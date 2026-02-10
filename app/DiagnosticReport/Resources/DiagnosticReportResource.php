<?php

declare(strict_types=1);

namespace App\DiagnosticReport\Resources;

use App\Models\DiagnosticReport;
use App\Observation\Resources\ObservationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DiagnosticReport
 */
final class DiagnosticReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'observations' => ObservationResource::collection($this->whenLoaded('observations')),
        ];
    }
}
