<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExternalActivityStoreRequest;
use App\Http\Resources\ExternalActivityRequestResource;
use App\Models\ExternalActivityRequest;
use App\Models\User;
use App\Notifications\ExternalActivityRequestSubmitted;
use App\Services\AcademicYearCalculator;
use App\Services\SafeNotifier;
use Illuminate\Http\Request;

class ExternalActivityController extends Controller
{
    public function index(Request $request)
    {
        $requests = ExternalActivityRequest::where('user_id', $request->user()->id)
            ->latest('activity_date')
            ->paginate(10);

        $currentAcademicYear = AcademicYearCalculator::forDate(now());
        $hoursUsed = ExternalActivityRequest::hoursUsedInAcademicYear(
            $request->user()->id, $currentAcademicYear, 'created_at'
        );
        $hoursRemaining = max(0, ExternalActivityRequest::ANNUAL_HOUR_CAP - $hoursUsed);

        return response()->json([
            'data' => ExternalActivityRequestResource::collection($requests->items()),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
            'current_academic_year' => $currentAcademicYear,
            'hours_remaining' => $hoursRemaining,
        ]);
    }

    public function store(ExternalActivityStoreRequest $request)
    {
        $validated = $request->validated();

        $externalActivityRequest = ExternalActivityRequest::create([
            ...collect($validated)->except('proof_image')->all(),
            'user_id' => $request->user()->id,
            'proof_image_path' => $request->file('proof_image')->store('external-activity-proofs', 'public'),
            'status' => 'pending',
        ]);

        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        SafeNotifier::send($admins, new ExternalActivityRequestSubmitted($externalActivityRequest->load('user')));

        return response()->json([
            'message' => __('ส่งคำร้องสำเร็จ รอเจ้าหน้าที่ตรวจสอบ'),
            'request' => new ExternalActivityRequestResource($externalActivityRequest),
        ]);
    }
}
