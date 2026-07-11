<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MajorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource) {
            return [];
        }

        return [
            'id' => $this->id,
            'name_th' => $this->name_th,
            'name_en' => $this->name_en,
            'degree_abbr' => $this->degree_abbr,
        ];
    }
}
