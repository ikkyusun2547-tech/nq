<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivitySurveyAnswer extends Model
{
    public $timestamps = false;

    protected $fillable = ['response_id', 'question_id', 'score'];
}
