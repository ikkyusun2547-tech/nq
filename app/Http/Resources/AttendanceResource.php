<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'activity_id' => $this->activity_id,
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'status' => $this->status,
            'checkin_method' => $this->checkin_method,
            'checkin_time' => $this->checkin_time,
            'distance_meters' => $this->distance_meters,
            'credited_hours' => $this->credited_hours,
            'photo_url' => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
        ];
    }
}
