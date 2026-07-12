<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

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

    public function index(Request $request)
    {
        $role = $request->input('role', 'all');
        $role = in_array($role, self::ROLES, true) ? $role : 'all';

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
            ->orderByRaw("case role when 'super_admin' then 0 when 'admin' then 1 else 2 end")
            ->orderBy('name_thai')
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
        abort_if($user->role !== 'student', 422, __('ผู้ใช้นี้เป็นแอดมินอยู่แล้ว'));

        $user->update(['role' => 'admin']);

        return back()->with('status', __('เลื่อนสิทธิ์ :name เป็นแอดมินแล้ว', ['name' => $user->name_thai ?? $user->name]));
    }

    public function demote(Request $request, User $user)
    {
        abort_if($user->role === 'student', 422, __('ผู้ใช้นี้เป็นนักศึกษาอยู่แล้ว'));

        // Checked before the self-demote guard below so the last super admin
        // demoting themselves gets the accurate reason (must keep at least
        // one) rather than the generic "can't touch yourself" message —
        // with only one super_admin ever able to reach this action (the
        // route itself requires that role), self-demotion is the only way
        // this branch is actually reachable.
        if ($user->role === 'super_admin' && User::where('role', 'super_admin')->count() <= 1) {
            abort(422, __('ต้องมีผู้ดูแลระบบสูงสุดอย่างน้อย 1 คนเสมอ'));
        }

        abort_if($user->id === $request->user()->id, 422, __('ไม่สามารถลดสิทธิ์ตัวเองได้'));

        $user->update(['role' => 'student']);

        return back()->with('status', __('ลดสิทธิ์ :name เป็นนักศึกษาแล้ว', ['name' => $user->name_thai ?? $user->name]));
    }

    public function ban(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('ไม่สามารถระงับบัญชีตัวเองได้'));

        $user->update(['account_status' => 'banned']);

        return back()->with('status', __('ระงับการใช้งานบัญชี :name แล้ว', ['name' => $user->name_thai ?? $user->name]));
    }

    public function unban(Request $request, User $user)
    {
        $user->update(['account_status' => 'active']);

        return back()->with('status', __('ปลดระงับบัญชี :name แล้ว', ['name' => $user->name_thai ?? $user->name]));
    }
}
