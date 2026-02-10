<?php

declare(strict_types=1);

namespace App\DiagnosticReport\Resources;

use App\Models\Observation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Observation
 */
final class ObservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'biomarker_name' => $this->biomarker_name,
            'biomarker_code' => $this->biomarker_code,
            'value' => (float) $this->value,
            'unit' => $this->unit,
            'reference_range_min' => $this->reference_range_min !== null ? (float) $this->reference_range_min : null,
            'reference_range_max' => $this->reference_range_max !== null ? (float) $this->reference_range_max : null,
            'reference_unit' => $this->reference_unit,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
