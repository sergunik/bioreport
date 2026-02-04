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
            'original_value' => (float) $this->original_value,
            'original_unit' => $this->original_unit,
            'normalized_value' => $this->normalized_value !== null ? (float) $this->normalized_value : null,
            'normalized_unit' => $this->normalized_unit,
            'reference_range_min' => $this->reference_range_min !== null ? (float) $this->reference_range_min : null,
            'reference_range_max' => $this->reference_range_max !== null ? (float) $this->reference_range_max : null,
            'reference_unit' => $this->reference_unit,
        ];
    }
}
