<?php

namespace App\Services\Auth;

use App\Exceptions\AccountBannedException;
use App\Exceptions\UnauthorizedEmailDomainException;
use App\Models\User;
use Illuminate\Support\Str;

class GoogleUserProvisioner
{
    /**
     * Create or update the local User record for a verified Google account,
     * shared by both the web OAuth callback and the mobile id_token login so
     * the domain-allowlist/banned-account rules can never drift apart.
     */
    public function provision(string $email, string $googleId, ?string $name, ?string $avatarUrl): User
    {
        $domain = config('services.srru.email_domain');

        if (! Str::endsWith($email, '@'.$domain)) {
            throw new UnauthorizedEmailDomainException(
                __('อนุญาตเฉพาะบัญชีอีเมลของมหาวิทยาลัย (@:domain) เท่านั้น', ['domain' => $domain])
            );
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name ?? $email,
                'google_id' => $googleId,
                'avatar_url' => $avatarUrl,
                'email_verified_at' => now(),
            ]
        );

        if ($user->account_status === 'banned') {
            throw new AccountBannedException(
                __('บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อกองพัฒนานักศึกษา')
            );
        }

        return $user;
    }
}
