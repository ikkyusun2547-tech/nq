<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidGoogleTokenException;
use Illuminate\Support\Facades\Http;

class GoogleIdTokenVerifier
{
    /**
     * Verify a native Google Sign-In id_token via Google's tokeninfo endpoint
     * and return its claims. Google documents tokeninfo as unsuitable for
     * high-volume production traffic; swap to google/apiclient's JWKS-based
     * verification (isolated here) if mobile login volume grows.
     */
    public function verify(string $idToken): array
    {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if ($response->failed()) {
            throw new InvalidGoogleTokenException(__('โทเค็นสำหรับเข้าสู่ระบบไม่ถูกต้องหรือหมดอายุ'));
        }

        $claims = $response->json();

        if (($claims['aud'] ?? null) !== config('services.google.client_id')) {
            throw new InvalidGoogleTokenException(__('โทเค็นสำหรับเข้าสู่ระบบไม่ถูกต้องหรือหมดอายุ'));
        }

        if (($claims['email_verified'] ?? 'false') !== 'true') {
            throw new InvalidGoogleTokenException(__('อีเมล Google ของคุณยังไม่ได้ยืนยันตัวตน'));
        }

        return $claims;
    }
}
