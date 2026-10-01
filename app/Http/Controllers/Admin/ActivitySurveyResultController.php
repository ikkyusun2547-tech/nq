<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ActivitySurveyExport;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\ActivitySurvey;
use Maatwebsite\Excel\Facades\Excel;

class ActivitySurveyResultController extends Controller
{
    public function __construct(private ActivitySurvey $survey) {}

    public function show(Activity $activity)
    {
        return view('admin.activities.survey-results', [
            'activity' => $activity,
            'summary' => $this->survey->summary($activity),
        ]);
    }

    public function export(Activity $activity)
    {
        $filename = 'survey-'.($activity->activity_code ?: $activity->id).'.xlsx';

        return Excel::download(new ActivitySurveyExport($activity, $this->survey->summary($activity)), $filename);
    }
}
