<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExternalActivityStoreRequest;
use App\Models\ExternalActivityRequest;
use App\Models\User;
use App\Notifications\ExternalActivityRequestSubmitted;
use App\Services\SafeNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExternalActivityController extends Controller
{
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

        return redirect()
            ->route('hour-requests.index', ['tab' => 'external'])
            ->with('status', __('ส่งคำร้องสำเร็จ รอเจ้าหน้าที่ตรวจสอบ'));
    }

    /**
     * A pending request hasn't been reviewed yet, so there's nothing to
     * preserve a history of — cancelling it is a hard delete, same as if
     * it was never submitted. Once an admin has acted on it (approved or
     * rejected), it's part of the review record and can no longer be
     * withdrawn this way.
     */
    public function destroy(Request $request, ExternalActivityRequest $externalActivityRequest)
    {
        abort_unless($externalActivityRequest->user_id === $request->user()->id, 403);

        // A plain 422 abort here would render Laravel's raw exception page
        // on a normal form submit (not fetch/XHR) — e.g. an admin approves
        // it in the moment between the student opening the page and
        // tapping cancel. A graceful redirect-with-flash keeps that race
        // from ever showing a crash screen.
        if ($externalActivityRequest->status !== 'pending') {
            return back()->with('error', __('ยกเลิกได้เฉพาะคำร้องที่ยังรอตรวจสอบเท่านั้น'));
        }

        Storage::disk('public')->delete($externalActivityRequest->proof_image_path);
        $externalActivityRequest->delete();

        return redirect()
            ->route('hour-requests.index', ['tab' => 'external'])
            ->with('status', __('ยกเลิกคำร้องสำเร็จ'));
    }
}
