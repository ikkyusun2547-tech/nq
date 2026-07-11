<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditTransferRequestResource extends JsonResource
{
    /**
     * Thai labels for CreditTransferRequest::POSITION_HOURS keys — kept as
     * a small duplicate of the same map in Student\DashboardController and
     * Student\TranscriptController rather than extracting a shared class,
     * to avoid touching those working web controllers for this API addition.
     */
    private const POSITION_LABELS = [
        'student_council_president' => 'นายกองค์การบริหารนักศึกษา',
        'student_club_president' => 'นายกสโมสรนักศึกษา',
        'student_parliament_president' => 'ประธานสภานักศึกษา',
        'club_president' => 'ประธานชมรม',
        'dormitory_president' => 'ประธานหอพักมหาวิทยาลัย',
        'class_leader' => 'หัวหน้าหมู่เรียน',
        'class_representative' => 'ตัวแทนหมู่เรียน',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'position_label' => __(self::POSITION_LABELS[$this->position] ?? $this->position),
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
