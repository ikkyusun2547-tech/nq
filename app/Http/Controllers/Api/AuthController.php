<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AccountBannedException;
use App\Exceptions\InvalidGoogleTokenException;
use App\Exceptions\UnauthorizedEmailDomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GoogleIdTokenLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\GoogleIdTokenVerifier;
use App\Services\Auth\GoogleUserProvisioner;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function loginWithGoogle(
        GoogleIdTokenLoginRequest $request,
        GoogleIdTokenVerifier $verifier,
        GoogleUserProvisioner $provisioner,
    ) {
        try {
            $claims = $verifier->verify($request->string('id_token')->toString());
        } catch (InvalidGoogleTokenException $e) {
            return response()->json(['message' => $e->getMessage(), 'error_code' => 'INVALID_GOOGLE_TOKEN'], 401);
        }

        try {
            $user = $provisioner->provision(
                email: $claims['email'],
                googleId: $claims['sub'],
                name: $claims['name'] ?? null,
                avatarUrl: $claims['picture'] ?? null,
            );
        } catch (UnauthorizedEmailDomainException $e) {
            return response()->json(['message' => $e->getMessage(), 'error_code' => 'EMAIL_DOMAIN_NOT_ALLOWED'], 403);
        } catch (AccountBannedException $e) {
            return response()->json(['message' => $e->getMessage(), 'error_code' => 'ACCOUNT_BANNED'], 403);
        }

        $token = $user->createToken('flutter-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load(['faculty', 'major'])),
            'profile_completed' => $user->hasCompletedProfile(),
            'is_admin' => $user->isAdmin(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => __('ออกจากระบบสำเร็จ')]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => new UserResource($request->user()->load(['faculty', 'major']))]);
    }
}
