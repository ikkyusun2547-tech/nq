<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CreditTransferRequest;
use App\Models\ExternalActivityRequest;
use App\Services\AcademicYearCalculator;
use Illuminate\Http\Request;

class HourRequestController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = old('_tab', $request->query('tab') === 'credit' ? 'credit' : 'external');
        $activeTab = in_array($activeTab, ['external', 'credit'], true) ? $activeTab : 'external';

        $externalRequests = ExternalActivityRequest::where('user_id', $request->user()->id)
            ->latest('activity_date')
            ->paginate(10, ['*'], 'external_page');

        $currentAcademicYear = AcademicYearCalculator::forDate(now());
        $hoursUsed = ExternalActivityRequest::hoursUsedInAcademicYear(
            $request->user()->id, $currentAcademicYear, 'created_at'
        );
        $hoursRemaining = max(0, ExternalActivityRequest::ANNUAL_HOUR_CAP - $hoursUsed);

        $creditRequests = CreditTransferRequest::where('user_id', $request->user()->id)
            ->latest('academic_year')
            ->paginate(10, ['*'], 'credit_page');

        $earliestYear = min($currentAcademicYear, $request->user()->enrollment_year ?? $currentAcademicYear);
        $academicYearOptions = collect(range($currentAcademicYear, $earliestYear))
            ->mapWithKeys(fn (int $year) => [$year => (string) $year]);

        return view('student.hour-requests.index', compact(
            'activeTab', 'externalRequests', 'currentAcademicYear', 'hoursRemaining',
            'creditRequests', 'academicYearOptions'
        ));
    }
}
