<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExternalActivityStoreRequest;
use App\Models\ExternalActivityRequest;
use App\Models\User;
use App\Notifications\ExternalActivityRequestSubmitted;
use App\Services\SafeNotifier;

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
}
