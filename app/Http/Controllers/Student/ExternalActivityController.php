<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExternalActivityStoreRequest;
use App\Models\ExternalActivityRequest;
use App\Models\User;
use App\Notifications\ExternalActivityRequestSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ExternalActivityController extends Controller
{
    public function index(Request $request)
    {
        $requests = ExternalActivityRequest::where('user_id', $request->user()->id)
            ->latest('activity_date')
            ->paginate(10);

        return view('student.external-activities.index', compact('requests'));
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
        Notification::send($admins, new ExternalActivityRequestSubmitted($externalActivityRequest->load('user')));

        return redirect()
            ->route('external-activities.index')
            ->with('status', __('ส่งคำร้องสำเร็จ รอเจ้าหน้าที่ตรวจสอบ'));
    }
}
