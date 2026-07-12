<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    /**
     * Thai text for the automation codes stored in flag_reason
     * (comma-joined when more than one applies). Public and reused verbatim
     * by resources/views/admin/attendance/index.blade.php so the reason an
     * admin sees and the reason a student sees for the same flagged
     * check-in never drift apart again (they briefly did, in two separately
     * hand-maintained copies, before this became the single source).
     */
    public const REASON_LABELS = [
        'GPS_OUT_OF_BOUNDS' => 'ตำแหน่ง GPS อยู่นอกพื้นที่จัดกิจกรรม',
        'DEVICE_SHARING_SUSPECTED' => 'ระบบตรวจพบว่าอาจใช้เครื่องร่วมกับผู้อื่น',
        'SELF_REPORTED' => 'รายงานตนเองโดยไม่มีการยืนยัน GPS',
        'PRINTED_QR_USED' => 'เช็คชื่อด้วย QR สำรอง (แบบพิมพ์)',
    ];

    protected $fillable = [
        'user_id',
        'activity_id',
        'checkin_method',
        'checkin_time',
        'student_lat',
        'student_lng',
        'distance_meters',
        'credited_hours',
        'device_uuid',
        'photo_path',
        'status',
        'flag_reason',
        'reject_reason',
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

    /**
     * Thai, student-facing translation of flag_reason's comma-joined codes.
     * Null when there's nothing to show (approved, or no codes recorded).
     */
    public function flagReasonLabel(): ?string
    {
        if (! $this->flag_reason) {
            return null;
        }

        $labels = collect(explode(',', $this->flag_reason))
            ->map(fn ($code) => __(self::REASON_LABELS[$code] ?? $code))
            ->all();

        return implode(' / ', $labels);
    }

    public function scopeCredited($query)
    {
        return $query->where('status', 'auto_approved');
    }
}
