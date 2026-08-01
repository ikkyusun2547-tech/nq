<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A cohort's (enrollment_year + program_type) graduation requirement —
 * required activity count, required hours, and per-year-of-study hour
 * targets. See ActivityEvaluationService::criteria() for how a student's
 * effective criteria is resolved from these rows (exact cohort match,
 * falling back to the nearest earlier configured cohort, falling back to
 * DEFAULT_CRITERIA if nothing has ever been configured).
 */
class GraduationCriteria extends Model
{
    // "Criteria" is already plural (of "criterion"), but Eloquent's naive
    // pluralizer doesn't know that and guesses "graduation_criterias" —
    // pin the real table name instead of renaming the migration to match.
    protected $table = 'graduation_criteria';

    protected $fillable = [
        'enrollment_year',
        'program_type',
        'required_activities',
        'required_hours',
        'yearly_targets',
    ];

    protected function casts(): array
    {
        return [
            'enrollment_year' => 'integer',
            'required_activities' => 'integer',
            'required_hours' => 'integer',
            'yearly_targets' => 'array',
        ];
    }
}
