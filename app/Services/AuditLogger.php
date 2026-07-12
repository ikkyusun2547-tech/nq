<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Thin write-side helper for App\Models\AuditLog, kept as a single call site
 * so every sensitive admin action logs in the same shape — see that model's
 * docblock for what "sensitive" means here.
 */
class AuditLogger
{
    public static function log(string $action, string $typeLabel, string $title, ?User $subjectUser = null): void
    {
        AuditLog::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'type_label' => $typeLabel,
            'title' => $title,
            'subject_user_id' => $subjectUser?->id,
        ]);
    }
}
