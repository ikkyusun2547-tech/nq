<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementLog;
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

    /** ?sort= value => real column to order by. Whitelisted so the query string can never inject an arbitrary column/expression into orderBy(). */
    private const SORTABLE = [
        'subject' => 'subject',
        'recipient_count' => 'recipient_count',
        'created_at' => 'created_at',
    ];

    public function index(Request $request)
    {
        $sortField = $request->input('sort');
        $sortColumn = self::SORTABLE[$sortField] ?? null;
        $sortDir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $logsQuery = AnnouncementLog::with(['faculty', 'sender']);

        // sender name lives on the related user, not announcement_logs —
        // needs an explicit join (with `select(...*)` so the join's own
        // id/created_at/updated_at columns don't collide with this table's).
        if ($sortField === 'sender') {
            $logsQuery->join('users', 'users.id', '=', 'announcement_logs.sent_by')
                ->select('announcement_logs.*')
                ->orderBy('users.name_thai', $sortDir);
        } elseif ($sortColumn) {
            $logsQuery->orderBy($sortColumn, $sortDir);
        } else {
            $logsQuery->latest();
        }

        $logs = $logsQuery->paginate(20)->withQueryString();

        return view('admin.announcements.index', compact('logs'));
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
            ->whereNull('graduated_at')
            ->where('account_status', 'active')
            ->when($validated['faculty_id'] ?? null, fn ($q) => $q->where('faculty_id', $validated['faculty_id']))
            ->when($validated['year_level'] ?? null, fn ($q) => $q->where('year_level', $validated['year_level']))
            ->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', __('ไม่พบนักศึกษาที่ตรงกับเงื่อนไขที่เลือก'));
        }

        SafeNotifier::send($recipients, new Announcement($validated['subject'], $validated['body']));

        AnnouncementLog::create([
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'faculty_id' => $validated['faculty_id'] ?? null,
            'year_level' => $validated['year_level'] ?? null,
            'recipient_count' => $recipients->count(),
            'sent_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.announcements.create')
            ->with('status', __('ส่งประกาศถึงนักศึกษา :count คนแล้ว', ['count' => $recipients->count()]));
    }
}
