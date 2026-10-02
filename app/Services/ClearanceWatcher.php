<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\StudentCleared;

/**
 * Congratulates a student the first time they meet the graduation activity
 * criteria. Called whenever something can add hours (an approved check-in,
 * external request or position credit — see the model observers registered
 * in AppServiceProvider); users.cleared_notified_at makes it once-only.
 */
class ClearanceWatcher
{
    public function __construct(private ActivityEvaluationService $evaluator)
    {
    }

    public function check(?User $user): void
    {
        if (! $user || $user->role !== 'student' || $user->cleared_notified_at !== null) {
            return;
        }

        $summary = $this->evaluator->summarize($user);
        if (! $summary['is_cleared']) {
            return;
        }

        // Claim first (conditional update) so two approvals landing together
        // can't both congratulate.
        $claimed = User::whereKey($user->id)->whereNull('cleared_notified_at')->update(['cleared_notified_at' => now()]);
        if ($claimed !== 1) {
            return;
        }
        $user->cleared_notified_at = now();

        SafeNotifier::send($user, new StudentCleared((int) $summary['total_hours'], (int) $summary['total_activities']));
    }
}
