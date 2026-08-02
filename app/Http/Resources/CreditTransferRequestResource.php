<?php

namespace App\Http\Resources;

use App\Models\CreditTransferPosition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditTransferRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $positionLabels = CreditTransferPosition::labelsMap();

        return [
            'id' => $this->id,
            'position' => $this->position,
            'position_label' => __($positionLabels[$this->position] ?? $this->position),
            'academic_year' => $this->academic_year,
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
