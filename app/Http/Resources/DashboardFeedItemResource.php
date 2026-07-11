<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps the stdClass feed items DashboardController builds by merging
 * check-ins/external-requests/credit-transfers — each type only sets a
 * subset of these keys, so every field is read via $this->resource->x ?? null
 * (JsonResource's own __get has no null-coalescing and would warn on an
 * unset stdClass property otherwise).
 */
class DashboardFeedItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->resource->title ?? null,
            'date' => $this->resource->date ?? null,
            'hours' => $this->resource->hours ?? null,
            'type' => $this->resource->type ?? null,
            'is_approved' => $this->resource->is_approved ?? null,
            'activity_id' => $this->resource->activity_id ?? null,
            'checkin_method' => $this->resource->checkin_method ?? null,
            'location_name' => $this->resource->location_name ?? null,
            'photo_url' => $this->resource->photo_url ?? null,
            'reject_reason' => $this->resource->reject_reason ?? null,
        ];
    }
}
