<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtTokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Bearer token required.'], 401);
        }

        $payload = app(JwtTokenService::class)->verifyAccessToken($token);

        if (! $payload) {
            return response()->json(['message' => 'Token is invalid or expired.'], 401);
        }

        $user = User::find($payload['sub'] ?? null);

        if (! $user) {
            return response()->json(['message' => 'Token user no longer exists.'], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
