<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\AccountBannedException;
use App\Exceptions\UnauthorizedEmailDomainException;
use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleUserProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        // Stateless: skips storing/verifying the OAuth "state" value in the
        // session. Normally that's a CSRF guard, but session-cookie
        // continuity through a tunnel (ngrok) plus a real multi-page Google
        // consent flow proved unreliable in practice (InvalidStateException
        // even with a matching redirect URI). Access is still gated to
        // @srru.ac.th emails below, so the practical risk is low for this
        // internal tool.
        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl(url('/auth/google/callback'))
            ->redirect();
    }

    public function callback(Request $request)
    {
        // Google redirects back with ?error=... (denied consent, closed the
        // popup, picked no account, etc.) instead of ?code=... when the user
        // didn't complete authorization — without this check we'd blindly
        // try to exchange a missing code and blow up with a raw Guzzle
        // "Missing required parameter: code" error.
        if ($request->has('error') || ! $request->filled('code')) {
            return redirect()->route('login')->withErrors([
                'email' => __('การเข้าสู่ระบบถูกยกเลิกหรือไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'),
            ]);
        }

        try {
            // The callback must return to whichever host actually started
            // the login (localhost during local dev, an ngrok tunnel when
            // sharing with someone else) — a mismatch here means Google's
            // callback lands on a different origin than the one holding the
            // session where the CSRF "state" was stored, which Socialite
            // then rejects as InvalidStateException.
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->redirectUrl(url('/auth/google/callback'))
                ->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => __('เข้าสู่ระบบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'),
            ]);
        }

        try {
            $user = app(GoogleUserProvisioner::class)->provision(
                email: $googleUser->getEmail(),
                googleId: $googleUser->getId(),
                name: $googleUser->getName() ?? $googleUser->getNickname(),
                avatarUrl: $googleUser->getAvatar(),
            );
        } catch (UnauthorizedEmailDomainException|AccountBannedException $e) {
            return redirect()->route('login')->withErrors([
                'email' => $e->getMessage(),
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
