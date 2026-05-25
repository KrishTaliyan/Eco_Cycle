<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()
                ->notifications()
                ->latest()
                ->take(20)
                ->get(),
            'meta' => [
                'unread' => $request->user()->notifications()->unread()->count(),
            ],
        ]);
    }

    public function read(Request $request, UserNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->forceFill(['read_at' => now()])->save();

        return response()->json([
            'message' => 'Notification marked as read.',
            'data' => $notification,
        ]);
    }
}
