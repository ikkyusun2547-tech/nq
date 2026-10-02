<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Remembers which one-off notifications an activity has already triggered
 * (see the create_activity_notification_logs_table migration).
 */
class ActivityNotificationLog extends Model
{
    public const PUBLISHED = 'published';
    public const CANCELLED = 'cancelled';
    public const CHECK_IN_OPENED = 'check_in_opened';
    public const CHECK_IN_CLOSING = 'check_in_closing';
    public const STARTING_SOON = 'starting_soon';
    public const FLAG_SURGE = 'flag_surge';
    public const ENDED_SUMMARY = 'ended_summary';

    public $timestamps = false;

    protected $fillable = ['activity_id', 'kind', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    /**
     * Atomically mark $kind as sent for $activity. Returns true only for the
     * caller that actually inserted the row, i.e. the one that should send.
     */
    public static function claim(Activity $activity, string $kind): bool
    {
        return static::query()->insertOrIgnore([
            'activity_id' => $activity->id,
            'kind' => $kind,
            'sent_at' => now(),
        ]) === 1;
    }

    /** Forget a kind so it can fire again (e.g. a cancelled activity that is reopened). */
    public static function release(Activity $activity, string $kind): void
    {
        static::query()->where('activity_id', $activity->id)->where('kind', $kind)->delete();
    }
}
