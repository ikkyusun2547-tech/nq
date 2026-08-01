<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The one place an admin's access level and account standing can actually
 * be changed — until this existed, promoting the first extra admin or
 * banning a misbehaving account required a direct database edit, since the
 * only place `role`/`account_status` were ever written outside student
 * self-service was the one-time DatabaseSeeder run.
 */
class UserManagementController extends Controller
{
    private const ROLES = ['student', 'admin', 'super_admin'];

    /** ?sort= value => real column to order by. Whitelisted so the query string can never inject an arbitrary column/expression into orderBy(). */
    private const SORTABLE = [
        'name' => 'name_thai',
        'email' => 'email',
    ];

    public function index(Request $request)
    {
        $role = $request->input('role', 'all');
        $role = in_array($role, self::ROLES, true) ? $role : 'all';
        $sortColumn = self::SORTABLE[$request->input('sort')] ?? null;
        $sortDir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $users = User::query()
            ->with(['faculty', 'major'])
            ->when($role !== 'all', fn ($query) => $query->where('role', $role))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name_thai', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->when(
                $sortColumn,
                fn ($query) => $query->orderBy($sortColumn, $sortDir),
                // Default: role-priority (super_admin, admin, student) then name — an
                // explicit column sort above means "ignore that grouping, just sort".
                fn ($query) => $query->orderByRaw("case role when 'super_admin' then 0 when 'admin' then 1 else 2 end")->orderBy('name_thai')
            )
            ->paginate(25)
            ->withQueryString();

        // Tab-pill counts — independent of the search box so they always
        // reflect the true size of each role bucket, not just the current view.
        $roleCounts = User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');
        $roleCounts['all'] = $roleCounts->sum();

        return view('admin.users.index', compact('users', 'role', 'roleCounts'));
    }

    public function promote(Request $request, User $user)
    {
        // A plain 422 abort here would render Laravel's raw exception page
        // on a normal form submit (not fetch/XHR) — these forms are simple
        // <form method="POST"> submits, so a stale page (two admins acting
        // on the same row) needs a graceful redirect, not a crash screen.
        if (! $this->applyPromote($user)) {
            return back()->with('error', __('ผู้ใช้นี้เป็นแอดมินอยู่แล้ว'));
        }

        return back()->with('status', __('เลื่อนสิทธิ์ :name เป็นแอดมินแล้ว', ['name' => $user->name_thai ?? $user->name]));
    }

    public function demote(Request $request, User $user)
    {
        if ($user->role === 'student') {
            return back()->with('error', __('ผู้ใช้นี้เป็นนักศึกษาอยู่แล้ว'));
        }

        // Checked before the self-demote guard below so the last super admin
        // demoting themselves gets the accurate reason (must keep at least
        // one) rather than the generic "can't touch yourself" message —
        // with only one super_admin ever able to reach this action (the
        // route itself requires that role), self-demotion is the only way
        // this branch is actually reachable.
        if ($user->role === 'super_admin' && User::where('role', 'super_admin')->count() <= 1) {
            return back()->with('error', __('ต้องมีผู้ดูแลระบบสูงสุดอย่างน้อย 1 คนเสมอ'));
        }

        if ($user->id === $request->user()->id) {
            return back()->with('error', __('ไม่สามารถลดสิทธิ์ตัวเองได้'));
        }

        $user->update(['role' => 'student']);
        AuditLogger::log('demoted', __('ผู้ใช้งาน'), __(':name เป็นนักศึกษา', ['name' => $user->name_thai ?? $user->name]), $user);

        return back()->with('status', __('ลดสิทธิ์ :name เป็นนักศึกษาแล้ว', ['name' => $user->name_thai ?? $user->name]));
    }

    public function ban(Request $request, User $user)
    {
        if (! $this->applyBan($user, $request->user())) {
            return back()->with('error', __('ไม่สามารถระงับบัญชีตัวเองได้'));
        }

        return back()->with('status', __('ระงับการใช้งานบัญชี :name แล้ว', ['name' => $user->name_thai ?? $user->name]));
    }

    public function unban(Request $request, User $user)
    {
        $user->update(['account_status' => 'active']);
        AuditLogger::log('unbanned', __('ผู้ใช้งาน'), __('บัญชี :name', ['name' => $user->name_thai ?? $user->name]), $user);

        return back()->with('status', __('ปลดระงับบัญชี :name แล้ว', ['name' => $user->name_thai ?? $user->name]));
    }

    public function ungraduate(Request $request, User $user)
    {
        if (! $this->applyUngraduate($user)) {
            return back()->with('error', __('ผู้ใช้นี้ยังไม่ได้อยู่ในสถานะจบการศึกษา'));
        }

        return back()->with('status', __('ยกเลิกสถานะจบการศึกษาของ :name แล้ว', ['name' => $user->name_thai ?? $user->name]));
    }

    /**
     * Apply one of the three "student -> something" actions to a batch of
     * users selected via checkboxes on the list. Deliberately silent about
     * rows an action doesn't apply to (e.g. an already-admin row selected
     * along with students for a "promote" bulk action) rather than failing
     * the whole batch — the summary message reports how many actually
     * changed vs. were skipped so the admin can tell what happened.
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'bulk_action' => ['required', Rule::in(['promote', 'ban', 'graduate', 'ungraduate'])],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $users = User::whereIn('id', $validated['user_ids'])->get();
        $actor = $request->user();
        $applied = 0;

        foreach ($users as $user) {
            $ok = match ($validated['bulk_action']) {
                'promote' => $this->applyPromote($user),
                'ban' => $this->applyBan($user, $actor),
                'graduate' => $this->applyGraduate($user),
                'ungraduate' => $this->applyUngraduate($user),
            };

            $applied += $ok ? 1 : 0;
        }

        $skipped = $users->count() - $applied;

        $message = match ($validated['bulk_action']) {
            'promote' => __('เลื่อนสิทธิ์เป็นแอดมินสำเร็จ :count คน', ['count' => $applied]),
            'ban' => __('ระงับการใช้งานบัญชีสำเร็จ :count คน', ['count' => $applied]),
            'graduate' => __('ทำเครื่องหมายจบการศึกษาสำเร็จ :count คน', ['count' => $applied]),
            'ungraduate' => __('ยกเลิกสถานะจบการศึกษาสำเร็จ :count คน', ['count' => $applied]),
        };

        if ($skipped > 0) {
            $message .= ' '.__('(ข้าม :count คนที่ไม่เข้าเงื่อนไข)', ['count' => $skipped]);
        }

        return back()->with('status', $message);
    }

    private function applyPromote(User $user): bool
    {
        if ($user->role !== 'student') {
            return false;
        }

        $user->update(['role' => 'admin']);
        AuditLogger::log('promoted', __('ผู้ใช้งาน'), __(':name เป็นแอดมิน', ['name' => $user->name_thai ?? $user->name]), $user);

        return true;
    }

    private function applyBan(User $user, User $actor): bool
    {
        if ($user->id === $actor->id) {
            return false;
        }

        $user->update(['account_status' => 'banned']);
        AuditLogger::log('banned', __('ผู้ใช้งาน'), __('บัญชี :name', ['name' => $user->name_thai ?? $user->name]), $user);

        return true;
    }

    private function applyGraduate(User $user): bool
    {
        if ($user->role !== 'student' || $user->isGraduated()) {
            return false;
        }

        $user->update(['graduated_at' => now()]);
        AuditLogger::log('graduated', __('ผู้ใช้งาน'), __(':name เป็นผู้จบการศึกษา', ['name' => $user->name_thai ?? $user->name]), $user);

        return true;
    }

    private function applyUngraduate(User $user): bool
    {
        if (! $user->isGraduated()) {
            return false;
        }

        $user->update(['graduated_at' => null]);
        AuditLogger::log('ungraduated', __('ผู้ใช้งาน'), __(':name กลับเป็นนักศึกษาปัจจุบัน', ['name' => $user->name_thai ?? $user->name]), $user);

        return true;
    }
}
