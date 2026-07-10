<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransferRequest extends Model
{
    /**
     * Fixed hour value per leadership position, per ข้อ 14 of the
     * university announcement — students don't choose the hours, they
     * choose the position and the hours follow.
     */
    public const POSITION_HOURS = [
        'student_council_president' => 60,
        'student_club_president' => 60,
        'student_parliament_president' => 60,
        'club_president' => 50,
        'dormitory_president' => 50,
        'class_leader' => 50,
        'class_representative' => 50,
    ];

    protected $fillable = [
        'user_id',
        'position',
        'academic_year',
        'hours_requested',
        'hours_approved',
        'activity_category',
        'proof_image_path',
        'status',
        'reject_reason',
        'admin_comment',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * The hours actually credited: what an admin overrode to, or the
     * position's standard hours if never adjusted.
     */
    public function getHoursCreditedAttribute(): int
    {
        return $this->hours_approved ?? $this->hours_requested;
    }

    /**
     * Whether a student already has a pending or approved credit-transfer
     * request for the given academic year — the announcement allows only
     * one claim per academic year. A rejection frees the slot back up.
     */
    public static function hasClaimedAcademicYear(int $userId, int $academicYear): bool
    {
        return static::where('user_id', $userId)
            ->where('academic_year', $academicYear)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
    }
}
