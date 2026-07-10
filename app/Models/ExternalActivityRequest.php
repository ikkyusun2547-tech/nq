<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalActivityRequest extends Model
{
    /**
     * Max hours a student may request across external activity requests
     * in a single academic year (pending + approved, rejected excluded).
     */
    public const ANNUAL_HOUR_CAP = 10;

    protected $fillable = [
        'user_id',
        'title',
        'organization',
        'activity_date',
        'activity_category',
        'hours_requested',
        'hours_approved',
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
            'activity_date' => 'date',
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
     * student's original request if never adjusted.
     */
    public function getHoursCreditedAttribute(): int
    {
        return $this->hours_approved ?? $this->hours_requested;
    }

    /**
     * Hours already claimed by a student against a given academic year's
     * ANNUAL_HOUR_CAP, counting pending + approved requests (rejected
     * requests free up the quota). $dateColumn selects whether the
     * academic year is judged by when the activity happened
     * (activity_date) or when it was submitted (created_at).
     */
    public static function hoursUsedInAcademicYear(int $userId, int $academicYear, string $dateColumn): int
    {
        [$start, $end] = \App\Services\AcademicYearCalculator::rangeFor($academicYear);

        return (int) static::where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereBetween($dateColumn, [$start, $end])
            ->selectRaw('COALESCE(SUM(COALESCE(hours_approved, hours_requested)), 0) as total')
            ->value('total');
    }
}
