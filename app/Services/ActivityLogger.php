<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function record(
        string $event,
        string $description,
        ?Request $request = null,
        ?User $user = null,
        array $metadata = [],
    ): ActivityLog {
        $request ??= request();
        $user ??= Auth::user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'event' => $event,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 800),
            'metadata' => $metadata,
        ]);
    }
}
