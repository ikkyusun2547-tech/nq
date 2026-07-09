<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\User;
use App\Services\ActivityEvaluationService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = User::query()
            ->where('role', 'student')
            ->with(['faculty', 'major'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name_thai', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('faculty_id'), fn ($query) => $query->where('faculty_id', $request->input('faculty_id')))
            ->when($request->filled('major_id'), fn ($query) => $query->where('major_id', $request->input('major_id')))
            ->when($request->filled('year_level'), fn ($query) => $query->where('year_level', $request->input('year_level')))
            ->orderBy('student_id')
            ->paginate(20)
            ->withQueryString();

        $faculties = Faculty::with(['majors' => fn ($query) => $query->orderBy('name_th')])->orderBy('name_th')->get();

        return view('admin.students.index', compact('students', 'faculties'));
    }

    public function show(User $student, ActivityEvaluationService $evaluationService)
    {
        abort_unless($student->role === 'student', 404);

        $student->load(['faculty', 'major']);

        $summary = $evaluationService->summarize($student);

        $attendances = $student->attendances()
            ->with('activity')
            ->latest('checkin_time')
            ->take(10)
            ->get();

        $externalRequests = $student->externalActivityRequests()
            ->latest('activity_date')
            ->take(10)
            ->get();

        return view('admin.students.show', compact('student', 'summary', 'attendances', 'externalRequests'));
    }
}
