<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'name_thai',
        'email',
        'google_id',
        'avatar_url',
        'password',
        'student_id',
        'role',
        'faculty_id',
        'major_id',
        'enrollment_year',
        'year_level',
        'program_type',
        'account_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'enrollment_year' => 'integer',
            'year_level' => 'integer',
        ];
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function createdActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'created_by');
    }

    public function externalActivityRequests(): HasMany
    {
        return $this->hasMany(ExternalActivityRequest::class);
    }

    public function creditTransferRequests(): HasMany
    {
        return $this->hasMany(CreditTransferRequest::class);
    }

    /**
     * Current year of study, self-reported at profile setup rather than
     * derived from enrollment_year — students who repeated a year or took
     * a leave of absence would otherwise show the wrong year.
     */
    public function getCurrentYearAttribute(): ?int
    {
        return $this->year_level;
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

    public function hasCompletedProfile(): bool
    {
        return ! is_null($this->faculty_id) && ! is_null($this->student_id) && ! is_null($this->year_level);
    }
}
