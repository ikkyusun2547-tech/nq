<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityNotificationLog as Log;
use App\Models\Attendance;
use App\Models\User;
use App\Notifications\ActivityCancelled;
use App\Notifications\ActivityCreated;
use App\Notifications\ActivityEndedSummary;
use App\Notifications\ActivityStartingSoon;
use App\Notifications\CheckInClosingSoon;
use App\Notifications\CheckInFlagSurge;
use App\Notifications\CheckInOpened;
use Illuminate\Support\Collection;

/**
 * Activity notifications that are about *timing* or *state changes* rather
 * than a single request: published, cancelled, check-in opened / closing,
 * starting soon (for the organiser) and GPS-flag surges. Each one is sent at
 * most once per activity — ActivityNotificationLog::claim() is the guard —
 * so the scheduler can sweep often and admins can toggle statuses freely.
 */
class ActivityAlerts
{
    /** How far back a just-opened window still counts as "just opened" (covers scheduler gaps without waking up old activities). */
    public const OPENED_GRACE_MINUTES = 30;

    /** "Starting soon" / "closing soon" lead time. */
    public const LEAD_MINUTES = 60;

    /** A window this short gets no separate "closing soon" — the "opened" notice already said when it closes. */
    public const MIN_WINDOW_FOR_CLOSING_REMINDER_MINUTES = 120;

    /** Flag-surge thresholds: at least this many GPS-flagged QR check-ins, making up at least this share of them. */
    public const SURGE_MIN_FLAGGED = 5;
    public const SURGE_MIN_RATIO = 0.3;

    // --- State changes (called from Admin\ActivityController) -------------

    /** The activity just became joinable (created open, or a draft went live). */
    public function published(Activity $activity): void
    {
        if (! in_array($activity->status, ['open', 'ongoing'], true)) {
            return;
        }
        if (! Log::claim($activity, Log::PUBLISHED)) {
            return;
        }

        $students = $activity->eligibleStudentsQuery()->get();
        if ($students->isNotEmpty()) {
            SafeNotifier::send($students, new ActivityCreated($activity));
        }
    }

    /** The activity was just cancelled: tell everyone it was meant for. */
    public function cancelled(Activity $activity): void
    {
        if (! Log::claim($activity, Log::CANCELLED)) {
            return;
        }

        $students = $activity->eligibleStudentsQuery()->get();
        if ($students->isNotEmpty()) {
            SafeNotifier::send($students, new ActivityCancelled($activity));
        }
    }

    /** A cancelled activity was brought back: a later cancellation should notify again. */
    public function uncancelled(Activity $activity): void
    {
        Log::release($activity, Log::CANCELLED);
    }

    /** The activity just closed (by the hourly sweep or an admin): how it went, for the organiser. */
    public function ended(Activity $activity): void
    {
        if (! Log::claim($activity, Log::ENDED_SUMMARY)) {
            return;
        }

        $attended = $activity->attendances()->count();
        $eligible = $activity->eligibleStudentsCount();
        $toReview = $activity->attendances()->where('status', 'flagged')->count();

        SafeNotifier::send($this->organisers($activity), new ActivityEndedSummary($activity, $attended, $eligible, $toReview));
    }

    // --- Timed reminders (the app:send-activity-reminders command) --------

    /**
     * @return array{opened: int, closing: int, starting_soon: int}  activities notified per kind
     */
    public function sendDueReminders(): array
    {
        return [
            'opened' => $this->sendCheckInOpened(),
            'closing' => $this->sendCheckInClosing(),
            'starting_soon' => $this->sendStartingSoon(),
        ];
    }

    /** Students: check-in has just opened (QR: the start time; self-report: the window opening). */
    private function sendCheckInOpened(): int
    {
        $now = now();
        $since = $now->copy()->subMinutes(self::OPENED_GRACE_MINUTES);

        $realtime = Activity::query()
            ->whereIn('status', ['open', 'ongoing'])
            ->where('checkin_method', 'realtime')
            ->whereBetween('start_at', [$since, $now])
            ->where('end_at', '>', $now)
            ->get();

        $selfReport = Activity::query()
            ->whereIn('status', ['open', 'ongoing'])
            ->where('checkin_method', 'self_report')
            ->whereNotNull('checkin_opens_at')
            ->whereBetween('checkin_opens_at', [$since, $now])
            ->where('checkin_closes_at', '>', $now)
            ->get();

        return $this->notifyMissingOnce($realtime->concat($selfReport), Log::CHECK_IN_OPENED, fn ($a) => new CheckInOpened($a));
    }

    /** Students who still haven't sent self-report evidence: the window closes within the hour. */
    private function sendCheckInClosing(): int
    {
        $now = now();

        $activities = Activity::query()
            ->whereIn('status', ['open', 'ongoing'])
            ->where('checkin_method', 'self_report')
            ->where('checkin_opens_at', '<=', $now)
            ->whereBetween('checkin_closes_at', [$now, $now->copy()->addMinutes(self::LEAD_MINUTES)])
            ->get()
            ->filter(fn (Activity $a) => $a->checkin_opens_at->diffInMinutes($a->checkin_closes_at) >= self::MIN_WINDOW_FOR_CLOSING_REMINDER_MINUTES);

        return $this->notifyMissingOnce($activities, Log::CHECK_IN_CLOSING, fn ($a) => new CheckInClosingSoon($a));
    }

    /** The organiser: their activity starts within the hour. */
    private function sendStartingSoon(): int
    {
        $now = now();

        $activities = Activity::query()
            ->whereIn('status', ['open', 'full', 'ongoing'])
            ->whereBetween('start_at', [$now, $now->copy()->addMinutes(self::LEAD_MINUTES)])
            ->get();

        $sent = 0;
        foreach ($activities as $activity) {
            if (! Log::claim($activity, Log::STARTING_SOON)) {
                continue;
            }
            SafeNotifier::send($this->organisers($activity), new ActivityStartingSoon($activity));
            $sent++;
        }

        return $sent;
    }

    /**
     * @param  Collection<int, Activity>  $activities
     */
    private function notifyMissingOnce(Collection $activities, string $kind, callable $make): int
    {
        $sent = 0;
        foreach ($activities as $activity) {
            if (! Log::claim($activity, $kind)) {
                continue;
            }
            $students = $activity->missingStudentsQuery()->get();
            if ($students->isNotEmpty()) {
                SafeNotifier::send($students, $make($activity));
            }
            $sent++;
        }

        return $sent;
    }

    // --- Live check-in monitoring (called after each QR check-in) ---------

    /** After a QR check-in lands flagged for GPS: alert the organiser once if that's become the pattern. */
    public function afterCheckIn(Attendance $attendance): void
    {
        if ($attendance->status !== 'flagged' || ! str_contains((string) $attendance->flag_reason, 'GPS_OUT_OF_BOUNDS')) {
            return;
        }

        $activity = $attendance->activity;
        $qrCheckIns = $activity->attendances()->where('checkin_method', 'realtime');
        $total = (clone $qrCheckIns)->count();
        $gpsFlagged = (clone $qrCheckIns)->where('flag_reason', 'like', '%GPS_OUT_OF_BOUNDS%')->count();

        if ($gpsFlagged < self::SURGE_MIN_FLAGGED || $gpsFlagged / max(1, $total) < self::SURGE_MIN_RATIO) {
            return;
        }
        if (! Log::claim($activity, Log::FLAG_SURGE)) {
            return;
        }

        SafeNotifier::send($this->organisers($activity), new CheckInFlagSurge($activity, $gpsFlagged, $total));
    }

    /**
     * Who runs an activity: the admin who created it, or — if that account
     * is gone or no longer an admin — every active admin, so it never goes
     * nowhere.
     *
     * @return Collection<int, User>
     */
    private function organisers(Activity $activity): Collection
    {
        $creator = $activity->created_by ? User::find($activity->created_by) : null;
        if ($creator && $creator->isAdmin() && $creator->account_status !== 'banned') {
            return collect([$creator]);
        }

        return User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->where(fn ($q) => $q->whereNull('account_status')->orWhere('account_status', '!=', 'banned'))
            ->get();
    }
}
