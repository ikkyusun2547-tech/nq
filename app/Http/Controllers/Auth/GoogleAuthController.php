<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();
        $domain = config('services.srru.email_domain');

        if (! Str::endsWith($googleUser->getEmail(), '@'.$domain)) {
            return redirect()->route('login')->withErrors([
                'email' => __('อนุญาตเฉพาะบัญชีอีเมลของมหาวิทยาลัย (@:domain) เท่านั้น', ['domain' => $domain]),
            ]);
        }

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]
        );

        if ($user->account_status === 'banned') {
            return redirect()->route('login')->withErrors([
                'email' => __('บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อกองพัฒนานักศึกษา'),
            ]);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(
            $user->hasCompletedProfile() ? route('dashboard') : route('profile-setup.show')
        );
    }

    public function logout()
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
