<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalActivityRequest extends Model
{
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
}
