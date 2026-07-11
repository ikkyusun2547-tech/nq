<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Every concrete notification already stores a uniform
 * {icon, title_key, body_key, body_params, url} array (see
 * App\Notifications\BaseNotification::toDatabase) — this just translates
 * the keys, the same way toMail()/toFcm() already do.
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data;

        return [
            'id' => $this->id,
            'icon' => $data['icon'] ?? null,
            'title' => __($data['title_key'] ?? ''),
            'body' => __($data['body_key'] ?? '', $data['body_params'] ?? []),
            'url' => $data['url'] ?? null,
            'read' => $this->read_at !== null,
            'created_at' => $this->created_at,
        ];
    }
}
