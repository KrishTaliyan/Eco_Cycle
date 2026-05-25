<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\EmailVerificationService;
use App\Services\JwtTokenService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthApiController extends Controller
{
    public function register(
        Request $request,
        JwtTokenService $tokens,
        EmailVerificationService $verification,
        NotificationService $notifications,
        ActivityLogger $logger,
    ) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => ['nullable', Rule::in(['customer', 'shop_owner'])],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);
        $deviceName = $validated['device_name'] ?? null;
        unset($validated['device_name']);
        $validated['role'] ??= 'customer';

        $user = User::create($validated);
        $user->assignRole($validated['role']);
        $user->settings()->create([
            'notification_channels' => ['email' => true, 'in_app' => true, 'product' => true],
        ]);

        $verification->issue($user);
        $notifications->onboarding($user);
        $logger->record('api.auth.register', 'Created account through the API.', $request, $user);

        return response()->json([
            'message' => 'Account created.',
            'data' => [
                'user' => $this->userPayload($user),
                'tokens' => $tokens->issueTokenPair($user, $deviceName),
            ],
        ], 201);
    }

    public function login(Request $request, JwtTokenService $tokens, ActivityLogger $logger)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $logger->record('api.auth.login', 'Issued API token pair.', $request, $user);

        return response()->json([
            'message' => 'Authenticated.',
            'data' => [
                'user' => $this->userPayload($user),
                'tokens' => $tokens->issueTokenPair($user, $validated['device_name'] ?? null),
            ],
        ]);
    }

    public function refresh(Request $request, JwtTokenService $tokens)
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $pair = $tokens->refresh($validated['refresh_token'], $validated['device_name'] ?? null);

        if (! $pair) {
            return response()->json(['message' => 'Refresh token is invalid or expired.'], 401);
        }

        return response()->json([
            'message' => 'Token refreshed.',
            'data' => ['tokens' => $pair],
        ]);
    }

    public function logout(Request $request, JwtTokenService $tokens)
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $tokens->revoke($validated['refresh_token']);

        return response()->json(['message' => 'Refresh token revoked.']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'data' => [
                'user' => $this->userPayload($request->user()),
            ],
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'email_verified' => $user->email_verified_at !== null,
            'avatar_url' => $user->avatar_url,
        ];
    }
}
