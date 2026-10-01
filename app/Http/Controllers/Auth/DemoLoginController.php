<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets evaluators (e.g. thesis advisors) try the system without a
 * university Google account: the admin and student views each have their
 * own password, and whichever one is typed picks the account. The two demo
 * accounts are created on first use, so enabling this on a deployment only
 * takes setting DEMO_ADMIN_PASSWORD and/or DEMO_STUDENT_PASSWORD.
 */
class DemoLoginController extends Controller
{
    public function show()
    {
        abort_unless($this->enabled(), 404);

        return view('auth.demo-login');
    }

    public function store(Request $request)
    {
        abort_unless($this->enabled(), 404);

        $password = $request->validate(['password' => ['required', 'string']])['password'];

        $user = match (true) {
            $this->matches('demo_admin_password', $password) => $this->demoAdmin(),
            $this->matches('demo_student_password', $password) => $this->demoStudent(),
            default => null,
        };

        if (! $user) {
            return back()->withErrors([
                'password' => __('รหัสผ่านทดลองไม่ถูกต้อง'),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route($user->hasCompletedProfile() ? 'dashboard' : 'profile-setup.show');
    }

    public static function enabled(): bool
    {
        return filled(config('services.srru.demo_admin_password'))
            || filled(config('services.srru.demo_student_password'));
    }

    // An unset password must never match — hash_equals('', '') would.
    private function matches(string $key, string $password): bool
    {
        $expected = config("services.srru.$key");

        return filled($expected) && hash_equals((string) $expected, $password);
    }

    private function email(string $localPart): string
    {
        return $localPart.'@'.config('services.srru.email_domain');
    }

    /**
     * Role and ban status are re-asserted on every login so the demo stays
     * usable even if someone demoted or banned it while trying out the
     * user-management screens.
     */
    private function demoAdmin(): User
    {
        $user = User::firstOrCreate(
            ['email' => $this->email('demo.admin')],
            ['name' => 'Demo Admin', 'name_thai' => 'ผู้ดูแลระบบ (ทดลอง)'],
        );

        $user->update(['role' => 'super_admin', 'account_status' => 'active']);

        return $user;
    }

    private function demoStudent(): User
    {
        $user = User::firstOrCreate(
            ['email' => $this->email('demo.student')],
            ['name' => 'Demo Student', 'name_thai' => 'นายนักศึกษา ทดลอง'],
        );

        $user->update(['role' => 'student', 'account_status' => 'active']);

        // Pre-fill a profile so the evaluator lands straight on the student
        // dashboard. Left alone once set, so edits made through the profile
        // page stick; with no faculty/major data yet it falls back to the
        // normal profile-setup form.
        if (! $user->hasCompletedProfile()) {
            $faculty = Faculty::whereHas('majors')->with('majors')->orderBy('id')->first();

            if ($faculty && ! User::where('student_id', '99999999999')->exists()) {
                $currentBuddhistYear = (int) now()->year + 543;

                $user->update([
                    'student_id' => '99999999999',
                    'faculty_id' => $faculty->id,
                    'major_id' => $faculty->majors->first()->id,
                    'enrollment_year' => $currentBuddhistYear - 1,
                    'year_level' => 2,
                    'program_type' => 'normal',
                ]);
            }
        }

        return $user;
    }
}
