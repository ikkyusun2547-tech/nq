<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Activity extends Model
{
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
        'location_lat',
        'location_lng',
        'allowed_radius',
        'qr_secret',
        'status',
    ];

    protected $hidden = [
        'qr_secret',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
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

    public function isOpenToEveryone(): bool
    {
        return $this->restrictions()->doesntExist();
    }

    public function acceptsCheckIn(): bool
    {
        return in_array($this->status, ['open', 'ongoing'], true);
    }

    public function isEligibleFor(User $user): bool
    {
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
        $query = User::where('role', 'student');

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
