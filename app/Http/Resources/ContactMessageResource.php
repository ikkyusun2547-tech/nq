<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'sender_name' => $this->sender->name_thai ?? $this->sender->name,
            'sender_avatar' => $this->sender->avatar_url,
            'is_mine' => $this->sender_id === $request->user()->id,
            'created_at' => $this->created_at,
            'attachment_url' => $this->attachment_path ? asset('storage/'.$this->attachment_path) : null,
            'attachment_name' => $this->attachment_name,
            'is_image_attachment' => $this->isImageAttachment(),
        ];
    }
}
