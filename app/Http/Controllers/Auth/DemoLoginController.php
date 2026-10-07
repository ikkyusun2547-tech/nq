<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\User;
use App\Services\AcademicYearCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets evaluators (e.g. thesis advisors) try the system without a
 * university Google account: the admin and student views each have their
 * own password, and whichever one is typed picks the account. The student
 * password signs in as the original demo student; the same password with a
 * "-1" … "-4" suffix signs in as that year level's demo student. All demo accounts are
 * created on first use, so enabling this on a deployment only takes setting
 * DEMO_ADMIN_PASSWORD and/or DEMO_STUDENT_PASSWORD.
 */
class DemoLoginController extends Controller
{
    /**
     * The demo students on offer: key => [email local part, Thai name,
     * student ID, year level]. 'default' is the original demo student (the
     * bare student password); the others are the password plus "-<key>".
     */
    public const STUDENTS = [
        'default' => ['demo.student', 'นายนักศึกษา ทดลอง', '99999999999', 2],
        '1' => ['demo.student.y1', 'นักศึกษาทดลอง ชั้นปีที่ 1', '99999999901', 1],
        '2' => ['demo.student.y2', 'นักศึกษาทดลอง ชั้นปีที่ 2', '99999999902', 2],
        '3' => ['demo.student.y3', 'นักศึกษาทดลอง ชั้นปีที่ 3', '99999999903', 3],
        '4' => ['demo.student.y4', 'นักศึกษาทดลอง ชั้นปีที่ 4', '99999999904', 4],
    ];

    public function show()
    {
        abort_unless($this->enabled(), 404);

        return view('auth.demo-login');
    }

    public function store(Request $request)
    {
        abort_unless($this->enabled(), 404);

        $password = $request->validate(['password' => ['required', 'string']])['password'];

        $studentKey = $this->matchingStudent($password);

        $user = match (true) {
            $this->matches('demo_admin_password', $password) => $this->demoAdmin(),
            $studentKey !== null => $this->demoStudent($studentKey),
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

    /** Which demo student (a STUDENTS key) this password signs in as, if any. */
    private function matchingStudent(string $password): ?string
    {
        $base = (string) config('services.srru.demo_student_password');

        if (blank($base)) {
            return null;
        }

        foreach (array_keys(self::STUDENTS) as $key) {
            $expected = $key === 'default' ? $base : "$base-$key";

            if (hash_equals($expected, $password)) {
                return (string) $key;
            }
        }

        return null;
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

    private function demoStudent(string $key): User
    {
        [$localPart, $nameThai, $studentId, $yearLevel] = self::STUDENTS[$key];

        $user = User::firstOrCreate(
            ['email' => $this->email($localPart)],
            ['name' => 'Demo Student'.($key === 'default' ? '' : " Y$key"), 'name_thai' => $nameThai],
        );

        $user->update(['role' => 'student', 'account_status' => 'active']);

        // Pre-fill a profile so the evaluator lands straight on the student
        // dashboard. Left alone once set, so edits made through the profile
        // page stick; with no faculty/major data yet it falls back to the
        // normal profile-setup form.
        if (! $user->hasCompletedProfile()) {
            $faculty = Faculty::whereHas('majors')->with('majors')->orderBy('id')->first();

            if ($faculty && ! User::where('student_id', $studentId)->exists()) {
                $user->update([
                    'student_id' => $studentId,
                    'faculty_id' => $faculty->id,
                    'major_id' => $faculty->majors->first()->id,
                    // A year-N student enrolled N-1 academic years ago.
                    'enrollment_year' => AcademicYearCalculator::forDate(now()) - ($yearLevel - 1),
                    'year_level' => $yearLevel,
                    'program_type' => 'normal',
                ]);
            }
        }

        return $user;
    }
}
