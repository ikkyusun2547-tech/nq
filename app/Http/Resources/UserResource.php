<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_thai' => $this->name_thai,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'student_id' => $this->student_id,
            'role' => $this->role,
            'faculty_id' => $this->faculty_id,
            'major_id' => $this->major_id,
            'faculty' => new FacultyResource($this->whenLoaded('faculty')),
            'major' => new MajorResource($this->whenLoaded('major')),
            'enrollment_year' => $this->enrollment_year,
            'year_level' => $this->year_level,
            'program_type' => $this->program_type,
            'account_status' => $this->account_status,
            'profile_completed' => $this->hasCompletedProfile(),
        ];
    }
}
