<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'title',
        'description',
        'banner_url',
        'organizer_name',
        'dress_code',
        'activity_level',
        'activity_category',
        'activity_type',
        'academic_year',
        'semester',
        'credit_hours',
        'capacity',
        'start_at',
        'end_at',
        'location_name',
        'location_lat',
        'location_lng',
        'allowed_radius',
        'qr_secret',
        'checkin_method',
        'self_report_auto_approve',
        'requires_gps',
        'checkin_opens_at',
        'checkin_closes_at',
        'status',
        'target_program',
    ];

    /** target_program values; null means both. */
    public const PROGRAMS = ['normal', 'special'];

    protected $hidden = [
        'qr_secret',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'requires_gps' => 'boolean',
            'self_report_auto_approve' => 'boolean',
            'checkin_opens_at' => 'datetime',
            'checkin_closes_at' => 'datetime',
            'important_updated_at' => 'datetime',
            'academic_year' => 'integer',
            'location_lat' => 'decimal:8',
            'location_lng' => 'decimal:8',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function restrictions(): HasMany
    {
        return $this->hasMany(ActivityRestriction::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function lateCheckInRequests(): HasMany
    {
        return $this->hasMany(LateCheckInRequest::class);
    }

    public function restrictedFaculties(): BelongsToMany
    {
        return $this->belongsToMany(Faculty::class, 'activity_restrictions')
            ->whereNotNull('activity_restrictions.faculty_id')
            ->withTimestamps();
    }

    public function restrictedMajors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'activity_restrictions')
            ->whereNotNull('activity_restrictions.major_id')
            ->withTimestamps();
    }

    /**
     * Activities that run at any point within [$from, $to] — including
     * multi-day ones that started before or end after the range.
     */
    public function scopeOverlapping($query, \DateTimeInterface $from, \DateTimeInterface $to)
    {
        return $query->where('start_at', '<=', $to)->where('end_at', '>=', $from);
    }

    /**
     * Pre-filled "add to Google Calendar" link. Times are stored as Thai
     * wall-clock time, so they're sent as floating local times pinned to
     * Asia/Bangkok via ctz rather than converted to UTC.
     */
    public function googleCalendarUrl(): string
    {
        $details = trim(\Illuminate\Support\Str::limit((string) $this->description, 500)."\n\n".route('activities.show', $this));

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $this->title,
            'dates' => $this->start_at->format('Ymd\THis').'/'.$this->end_at->format('Ymd\THis'),
            'ctz' => 'Asia/Bangkok',
            'details' => $details,
            'location' => (string) $this->location_name,
        ]);
    }

    public function isOpenToEveryone(): bool
    {
        return $this->restrictions()->doesntExist();
    }

    /**
     * Statuses an admin can set. "กำลังจัดกิจกรรม" (ongoing) behaves like
     * open — check-in, reminders, the hourly auto-close — but lets an admin
     * mark an activity as running whatever its times say. "เต็มแล้ว" is
     * gone: there's no sign-up to fill, and it silently blocked check-in.
     * Old 'full' rows were moved to 'open' by a migration; the code below
     * still tolerates them.
     */
    public const SETTABLE_STATUSES = ['draft', 'open', 'ongoing', 'closed', 'cancelled'];

    /**
     * The status to *show*: an open activity between its start and end time
     * reads "ongoing" without anyone having to switch it by hand, and one an
     * admin set to ongoing always does.
     */
    public function displayStatus(): string
    {
        $status = match ($this->status) {
            'full' => 'open',
            default => $this->status,
        };

        if ($status === 'open' && $this->start_at && $this->end_at) {
            return now()->between($this->start_at, $this->end_at) ? 'ongoing' : 'open';
        }

        return $status;
    }

    public function acceptsCheckIn(): bool
    {
        if (! in_array($this->status, ['open', 'ongoing'], true)) {
            return false;
        }

        if ($this->usesSelfReportCheckIn()) {
            return $this->checkin_opens_at
                && $this->checkin_closes_at
                && now()->between($this->checkin_opens_at, $this->checkin_closes_at);
        }

        return true;
    }

    public function usesSelfReportCheckIn(): bool
    {
        return $this->checkin_method === 'self_report';
    }

    /**
     * Whether a realtime check-in must pass the GPS-radius check. Only
     * meaningful for checkin_method 'realtime' — self-report never checks
     * GPS regardless of this flag.
     */
    public function requiresGpsCheck(): bool
    {
        return $this->checkin_method === 'realtime' && $this->requires_gps;
    }

    /**
     * A student can only ask to be checked in retroactively once the
     * activity has genuinely wrapped up (status closed) — not while it's
     * still open/ongoing, where the normal check-in flow already applies.
     */
    public function acceptsLateRequest(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Whether to show the "อัปเดตแล้ว" badge to students — true for a
     * week after an edit that changed something they'd actually need to
     * know about (time/location/check-in method), then fades on its own
     * without needing per-student dismissal tracking.
     */
    public function wasRecentlyUpdatedSignificantly(): bool
    {
        return $this->important_updated_at !== null
            && $this->important_updated_at->gt(now()->subDays(7));
    }

    public function isEligibleFor(User $user): bool
    {
        // A student with no programme on file counts as ภาคปกติ, the same
        // default ActivityEvaluationService uses for their criteria.
        if ($this->target_program && ($user->program_type ?? 'normal') !== $this->target_program) {
            return false;
        }

        if ($this->isOpenToEveryone()) {
            return true;
        }

        return $this->restrictions()
            ->where(function ($query) use ($user) {
                $query->whereNull('faculty_id')->orWhere('faculty_id', $user->faculty_id);
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('major_id')->orWhere('major_id', $user->major_id);
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('target_year')->orWhere('target_year', $user->current_year);
            })
            ->exists();
    }

    /**
     * Enrolled students eligible to attend, per the same faculty/major/year
     * targeting rules as isEligibleFor() — a single query rather than
     * looping every student through that method. Shared base for the
     * headline count and the "who hasn't checked in" list/export.
     *
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function eligibleStudentsQuery()
    {
        // Graduated students aren't part of the current student body an
        // activity's headcount/reminders/missing-list are meant for.
        $query = User::where('role', 'student')->whereNull('graduated_at');

        if ($this->target_program === 'special') {
            $query->where('program_type', 'special');
        } elseif ($this->target_program === 'normal') {
            $query->where(fn ($q) => $q->whereNull('program_type')->orWhere('program_type', 'normal'));
        }

        if ($this->isOpenToEveryone()) {
            return $query;
        }

        return $query->whereExists(function ($q) {
            $q->select(DB::raw(1))
                ->from('activity_restrictions')
                ->where('activity_restrictions.activity_id', $this->id)
                ->where(function ($qq) {
                    $qq->whereNull('activity_restrictions.faculty_id')
                        ->orWhereColumn('activity_restrictions.faculty_id', 'users.faculty_id');
                })
                ->where(function ($qq) {
                    $qq->whereNull('activity_restrictions.major_id')
                        ->orWhereColumn('activity_restrictions.major_id', 'users.major_id');
                })
                ->where(function ($qq) {
                    $qq->whereNull('activity_restrictions.target_year')
                        ->orWhereColumn('activity_restrictions.target_year', 'users.year_level');
                });
        });
    }

    public function eligibleStudentsCount(): int
    {
        return $this->eligibleStudentsQuery()->count();
    }

    /**
     * Eligible students who have not (yet) checked in to this activity.
     *
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function missingStudentsQuery()
    {
        return $this->eligibleStudentsQuery()
            ->whereNotIn('users.id', $this->attendances()->pluck('user_id'));
    }
}
