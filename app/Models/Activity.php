<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
