<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreditTransferStoreRequest;
use App\Models\CreditTransferRequest;
use App\Models\User;
use App\Notifications\CreditTransferRequestSubmitted;
use App\Services\SafeNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CreditTransferController extends Controller
{
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
            ->route('hour-requests.index', ['tab' => 'credit'])
            ->with('status', __('ส่งคำร้องเทียบโอนชั่วโมงสำเร็จ รอเจ้าหน้าที่ตรวจสอบ'));
    }

    /**
     * See ExternalActivityController::destroy — same "pending only, hard
     * delete, graceful redirect instead of a raw abort" reasoning.
     */
    public function destroy(Request $request, CreditTransferRequest $creditTransferRequest)
    {
        abort_unless($creditTransferRequest->user_id === $request->user()->id, 403);

        if ($creditTransferRequest->status !== 'pending') {
            return back()->with('error', __('ยกเลิกได้เฉพาะคำร้องที่ยังรอตรวจสอบเท่านั้น'));
        }

        Storage::disk('public')->delete($creditTransferRequest->proof_image_path);
        $creditTransferRequest->delete();

        return redirect()
            ->route('hour-requests.index', ['tab' => 'credit'])
            ->with('status', __('ยกเลิกคำร้องสำเร็จ'));
    }
}
