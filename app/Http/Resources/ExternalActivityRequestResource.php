<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalActivityRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'organization' => $this->organization,
            'activity_date' => $this->activity_date,
            'activity_category' => $this->activity_category,
            'hours_requested' => $this->hours_requested,
            'hours_approved' => $this->hours_approved,
            'hours_credited' => $this->hours_credited,
            'status' => $this->status,
            'reject_reason' => $this->reject_reason,
            'proof_image_url' => $this->proof_image_path ? asset('storage/'.$this->proof_image_path) : null,
            'created_at' => $this->created_at,
        ];
    }
}
