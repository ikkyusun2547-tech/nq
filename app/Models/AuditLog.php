<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks sensitive admin actions that don't already carry their own
 * reviewed_by/reviewed_at trail (unlike Attendance/ExternalActivityRequest/
 * CreditTransferRequest/LateCheckInRequest, which AuditLogController reads
 * directly) — role changes, account bans, faculty/major edits, and
 * graduation-criteria updates. See App\Services\AuditLogger for how entries
 * get written.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'action', 'type_label', 'title', 'subject_user_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subjectUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }
}
