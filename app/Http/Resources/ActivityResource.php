<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'banner_url' => $this->banner_url ? asset('storage/'.$this->banner_url) : null,
            'organizer_name' => $this->organizer_name,
            'dress_code' => $this->dress_code,
            'activity_level' => $this->activity_level,
            'activity_category' => $this->activity_category,
            'activity_type' => $this->activity_type,
            'academic_year' => $this->academic_year,
            'semester' => $this->semester,
            'credit_hours' => $this->credit_hours,
            'capacity' => $this->capacity,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'location_name' => $this->location_name,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'checkin_method' => $this->checkin_method,
            'checkin_opens_at' => $this->checkin_opens_at,
            'checkin_closes_at' => $this->checkin_closes_at,
            'status' => $this->status,
            'was_recently_updated_significantly' => $this->wasRecentlyUpdatedSignificantly(),
        ];
    }
}
