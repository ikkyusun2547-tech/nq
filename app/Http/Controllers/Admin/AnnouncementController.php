<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\Announcement;
use App\Services\SafeNotifier;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function create()
    {
        $faculties = Faculty::orderBy('name_th')->get();

        return view('admin.announcements.create', compact('faculties'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            'faculty_id' => ['nullable', 'exists:faculties,id'],
            'year_level' => ['nullable', 'integer', 'between:1,4'],
        ]);

        $recipients = User::query()
            ->where('role', 'student')
            ->where('account_status', 'active')
            ->when($validated['faculty_id'] ?? null, fn ($q) => $q->where('faculty_id', $validated['faculty_id']))
            ->when($validated['year_level'] ?? null, fn ($q) => $q->where('year_level', $validated['year_level']))
            ->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', __('ไม่พบนักศึกษาที่ตรงกับเงื่อนไขที่เลือก'));
        }

        SafeNotifier::send($recipients, new Announcement($validated['subject'], $validated['body']));

        return redirect()
            ->route('admin.announcements.create')
            ->with('status', __('ส่งประกาศถึงนักศึกษา :count คนแล้ว', ['count' => $recipients->count()]));
    }
}
