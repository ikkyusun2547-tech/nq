<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One anonymous set of answers — intentionally has no user_id; who has
 * answered is tracked separately in activity_survey_submissions.
 */
class ActivitySurveyResponse extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['activity_id', 'comment'];

    public function answers(): HasMany
    {
        return $this->hasMany(ActivitySurveyAnswer::class, 'response_id');
    }
}
