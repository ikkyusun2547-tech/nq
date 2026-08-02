<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreditTransferStoreRequest;
use App\Http\Resources\CreditTransferRequestResource;
use App\Models\CreditTransferPosition;
use App\Models\CreditTransferRequest;
use App\Models\User;
use App\Notifications\CreditTransferRequestSubmitted;
use App\Services\AcademicYearCalculator;
use App\Services\SafeNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        return response()->json([
            'data' => CreditTransferRequestResource::collection($requests->items()),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
            'academic_year_options' => $academicYearOptions,
        ]);
    }

    public function store(CreditTransferStoreRequest $request)
    {
        $validated = $request->validated();
        $position = $validated['position'];

        $creditTransferRequest = CreditTransferRequest::create([
            'user_id' => $request->user()->id,
            'position' => $position,
            'academic_year' => $validated['academic_year'],
            'hours_requested' => CreditTransferPosition::hoursMap()[$position],
            'proof_image_path' => $request->file('proof_image')->store('credit-transfer-proofs', 'public'),
            'status' => 'pending',
        ]);

        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        SafeNotifier::send($admins, new CreditTransferRequestSubmitted($creditTransferRequest->load('user')));

        return response()->json([
            'message' => __('ส่งคำร้องเทียบโอนชั่วโมงสำเร็จ รอเจ้าหน้าที่ตรวจสอบ'),
            'request' => new CreditTransferRequestResource($creditTransferRequest),
        ]);
    }

    /**
     * See Student\CreditTransferController::destroy — same "pending only,
     * hard delete" reasoning, mirrored here for the mobile app.
     */
    public function destroy(Request $request, CreditTransferRequest $creditTransferRequest)
    {
        abort_unless($creditTransferRequest->user_id === $request->user()->id, 403);
        abort_unless($creditTransferRequest->status === 'pending', 422, __('ยกเลิกได้เฉพาะคำร้องที่ยังรอตรวจสอบเท่านั้น'));

        Storage::disk('public')->delete($creditTransferRequest->proof_image_path);
        $creditTransferRequest->delete();

        return response()->json(['message' => __('ยกเลิกคำร้องสำเร็จ')]);
    }

    /**
     * So the Flutter app never hardcodes a duplicate of the admin-configured
     * position list (App\Models\CreditTransferPosition, managed at
     * admin/credit-transfer-positions).
     */
    public function positions()
    {
        $positions = CreditTransferPosition::orderBy('sort_order')
            ->get()
            ->map(fn (CreditTransferPosition $position) => [
                'key' => $position->key,
                'label' => __($position->label),
                'hours' => $position->hours,
            ])
            ->values();

        return response()->json(['data' => $positions]);
    }
}
