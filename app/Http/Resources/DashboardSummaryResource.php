<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps ActivityEvaluationService::summarize()'s plain array output.
 * Deliberately indexes $this->resource[...] rather than the usual
 * $this->key magic access, since JsonResource's __get delegates to
 * $this->resource->{$key} (object property syntax), which errors on a
 * plain array resource.
 */
class DashboardSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_activities' => $this->resource['total_activities'],
            'required_activities' => $this->resource['required_activities'],
            'total_hours' => $this->resource['total_hours'],
            'required_hours' => $this->resource['required_hours'],
            'current_year' => $this->resource['current_year'],
            'yearly_target_hours' => $this->resource['yearly_target_hours'],
            'category_hours' => $this->resource['category_hours'],
            'is_cleared' => $this->resource['is_cleared'],
        ];
    }
}
