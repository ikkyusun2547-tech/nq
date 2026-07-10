<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreditTransferStoreRequest;
use App\Models\CreditTransferRequest;
use App\Models\User;
use App\Notifications\CreditTransferRequestSubmitted;
use App\Services\AcademicYearCalculator;
use App\Services\SafeNotifier;
use Illuminate\Http\Request;

class CreditTransferController extends Controller
{
    public function index(Request $request)
    {
        $requests = CreditTransferRequest::where('user_id', $request->user()->id)
            ->latest('academic_year')
            ->paginate(10);

        $currentAcademicYear = AcademicYearCalculator::forDate(now());
        $earliestYear = min($currentAcademicYear, $request->user()->enrollment_year ?? $currentAcademicYear);
        $academicYearOptions = collect(range($currentAcademicYear, $earliestYear))
            ->mapWithKeys(fn (int $year) => [$year => (string) $year]);

        return view('student.credit-transfers.index', compact('requests', 'academicYearOptions'));
    }

    public function store(CreditTransferStoreRequest $request)
    {
        $validated = $request->validated();
        $position = $validated['position'];

        $creditTransferRequest = CreditTransferRequest::create([
            'user_id' => $request->user()->id,
            'position' => $position,
            'academic_year' => $validated['academic_year'],
            'hours_requested' => CreditTransferRequest::POSITION_HOURS[$position],
            'proof_image_path' => $request->file('proof_image')->store('credit-transfer-proofs', 'public'),
            'status' => 'pending',
        ]);

        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        SafeNotifier::send($admins, new CreditTransferRequestSubmitted($creditTransferRequest->load('user')));

        return redirect()
            ->route('credit-transfers.index')
            ->with('status', __('ส่งคำร้องเทียบโอนชั่วโมงสำเร็จ รอเจ้าหน้าที่ตรวจสอบ'));
    }
}
