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
        return Socialite::driver('google')
            ->redirectUrl(url('/auth/google/callback'))
            ->redirect();
    }

    public function callback()
    {
        // The callback must return to whichever host actually started the
        // login (localhost during local dev, an ngrok tunnel when sharing
        // with someone else) — a mismatch here means Google's callback
        // lands on a different origin than the one holding the session
        // where the CSRF "state" was stored, which Socialite then rejects
        // as InvalidStateException.
        $googleUser = Socialite::driver('google')
            ->redirectUrl(url('/auth/google/callback'))
            ->user();
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

        // Admins always land on their own control panel — honoring a
        // pre-login "intended" URL here is wrong when that URL was captured
        // while browsing the student area (e.g. before switching accounts),
        // since it would silently drop an admin onto the student dashboard.
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
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
