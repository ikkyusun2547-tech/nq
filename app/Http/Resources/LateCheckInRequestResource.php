<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LateCheckInRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource) {
            return [];
        }

        return [
            'id' => $this->id,
            'activity_id' => $this->activity_id,
            'reason' => $this->reason,
            'proof_image_url' => $this->proof_image_path ? asset('storage/'.$this->proof_image_path) : null,
            'status' => $this->status,
            'hours_approved' => $this->hours_approved,
            'hours_credited' => $this->hours_credited,
            'reject_reason' => $this->reject_reason,
            'created_at' => $this->created_at,
        ];
    }
}
