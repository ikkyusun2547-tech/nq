<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'status' => $this->status,
            'context_type' => $this->context_type,
            'context_id' => $this->context_id,
            'last_message_at' => $this->last_message_at,
            'unread' => (bool) $this->student_unread,
            // Same field the web contact chat shows — see
            // Student\ContactController's contact/show.blade.php. Not
            // gated behind whenLoaded(): Api\Student\ContactController
            // eager-loads assignedAdmin everywhere this resource is used,
            // but resolving it directly here (same as the web controllers)
            // means the field is never silently missing if that ever slips.
            'assigned_admin_name' => $this->assignedAdmin?->name_thai ?? $this->assignedAdmin?->name,
            'created_at' => $this->created_at,
        ];
    }
}
