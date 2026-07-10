<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LateCheckInRequest extends Model
{
    protected $fillable = [
        'user_id',
        'activity_id',
        'reason',
        'proof_image_path',
        'status',
        'hours_approved',
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

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
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
     * activity's normal credit_hours if never adjusted.
     */
    public function getHoursCreditedAttribute(): int
    {
        return $this->hours_approved ?? $this->activity->credit_hours;
    }
}
