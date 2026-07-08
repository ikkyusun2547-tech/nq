<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'activity_id',
        'checkin_time',
        'student_lat',
        'student_lng',
        'distance_meters',
        'device_uuid',
        'photo_path',
        'status',
        'flag_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'checkin_time' => 'datetime',
            'student_lat' => 'decimal:8',
            'student_lng' => 'decimal:8',
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

    public function scopeFlagged($query)
    {
        return $query->where('status', 'flagged');
    }

    public function scopeCredited($query)
    {
        return $query->where('status', 'auto_approved');
    }
}
