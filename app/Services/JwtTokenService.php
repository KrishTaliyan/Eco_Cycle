<?php

namespace App\Services;

use App\Models\ApiRefreshToken;
use App\Models\User;
use Illuminate\Support\Str;

class JwtTokenService
{
    public function issueTokenPair(User $user, ?string $deviceName = null): array
    {
        $refreshToken = $this->createRefreshToken($user, $deviceName);

        return [
            'access_token' => $this->issueAccessToken($user),
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 15 * 60,
        ];
    }

    public function issueAccessToken(User $user, int $ttlMinutes = 15): string
    {
        $issuedAt = now();
        $payload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'sub' => (string) $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'iat' => $issuedAt->timestamp,
            'exp' => $issuedAt->copy()->addMinutes($ttlMinutes)->timestamp,
            'jti' => (string) Str::uuid(),
        ];

        return $this->encode($payload);
    }

    public function verifyAccessToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $this->secret(), true));

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($payload), true);

        if (! is_array($payload) || ($payload['exp'] ?? 0) < now()->timestamp) {
            return null;
        }

        return $payload;
    }

    public function refresh(string $refreshToken, ?string $deviceName = null): ?array
    {
        $record = ApiRefreshToken::query()
            ->where('token_hash', hash('sha256', $refreshToken))
            ->first();

        if (! $record || ! $record->isActive()) {
            return null;
        }

        $record->forceFill(['revoked_at' => now()])->save();

        return $this->issueTokenPair($record->user, $deviceName ?? $record->device_name);
    }

    public function revoke(string $refreshToken): void
    {
        ApiRefreshToken::query()
            ->where('token_hash', hash('sha256', $refreshToken))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    private function createRefreshToken(User $user, ?string $deviceName): string
    {
        $token = Str::random(80);

        ApiRefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'device_name' => $deviceName,
            'expires_at' => now()->addDays(30),
        ]);

        return $token;
    }

    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ], JSON_THROW_ON_ERROR));

        $payload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $this->secret(), true));

        return "{$header}.{$payload}.{$signature}";
    }

    private function secret(): string
    {
        $key = (string) config('app.key');

        if (str_starts_with($key, 'base64:')) {
            return base64_decode(substr($key, 7), true) ?: $key;
        }

        return $key;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;

        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
