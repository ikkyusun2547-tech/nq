<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.srru.demo_admin_password' => 'admin-demo',
            'services.srru.demo_student_password' => 'student-demo',
        ]);
    }

    public function test_the_feature_is_hidden_when_no_password_is_configured(): void
    {
        config([
            'services.srru.demo_admin_password' => null,
            'services.srru.demo_student_password' => null,
        ]);

        $this->get(route('login'))->assertDontSee(route('demo-login.show'));
        $this->get(route('demo-login.show'))->assertNotFound();
        $this->post(route('demo-login.store'), ['password' => 'admin-demo'])->assertNotFound();
    }

    public function test_the_login_page_still_offers_google_alongside_demo_login(): void
    {
        $this->get(route('login'))
            ->assertSee(route('auth.google.redirect'))
            ->assertSee(route('demo-login.show'));

        $this->get(route('demo-login.show'))->assertOk();
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $this->post(route('demo-login.store'), ['password' => 'nope'])
            ->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_an_unset_password_never_matches(): void
    {
        config(['services.srru.demo_student_password' => '']);

        $this->post(route('demo-login.store'), ['password' => ''])
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_the_admin_password_logs_in_as_a_demo_super_admin(): void
    {
        $this->post(route('demo-login.store'), ['password' => 'admin-demo'])
            ->assertRedirect(route('admin.dashboard'));

        $user = User::where('email', 'demo.admin@srru.ac.th')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('super_admin', $user->role);
    }

    public function test_the_student_password_logs_in_as_a_demo_student_with_a_completed_profile(): void
    {
        $faculty = Faculty::factory()->create();
        Major::factory()->create(['faculty_id' => $faculty->id]);

        $this->post(route('demo-login.store'), ['password' => 'student-demo'])
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'demo.student@srru.ac.th')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->isStudent());
        $this->assertTrue($user->hasCompletedProfile());

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_the_demo_student_goes_to_profile_setup_when_no_faculties_exist(): void
    {
        $this->post(route('demo-login.store'), ['password' => 'student-demo'])
            ->assertRedirect(route('profile-setup.show'));
    }

    public function test_logging_in_again_restores_a_demoted_or_banned_demo_admin(): void
    {
        $this->post(route('demo-login.store'), ['password' => 'admin-demo']);
        User::where('email', 'demo.admin@srru.ac.th')->update(['role' => 'student', 'account_status' => 'banned']);
        $this->post(route('logout'));

        $this->post(route('demo-login.store'), ['password' => 'admin-demo'])
            ->assertRedirect(route('admin.dashboard'));

        $user = User::where('email', 'demo.admin@srru.ac.th')->firstOrFail();
        $this->assertSame('super_admin', $user->role);
        $this->assertSame('active', $user->account_status);
        $this->assertSame(1, User::count());
    }
}
