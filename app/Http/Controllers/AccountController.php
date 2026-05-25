<?php

namespace App\Http\Controllers;

use App\Models\FacilityBookmark;
use App\Models\RecyclingCenter;
use App\Models\UserNotification;
use App\Services\ActivityLogger;
use App\Services\DashboardService;
use App\Services\EmailVerificationService;
use App\Services\FacilityFinder;
use App\Services\MediaUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function dashboard(Request $request, DashboardService $dashboard)
    {
        if ($request->user()->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($request->user()->hasRole('shop_owner')) {
            return redirect()->route('shop.dashboard');
        }

        $user = $request->user()->load([
            'settings',
            'bookmarks',
            'notifications' => fn ($query) => $query->latest()->take(8),
            'recyclingRequests.device',
            'rewardPoints',
        ]);

        return view('account.dashboard', [
            'dashboard' => $dashboard->snapshot($request->session()->getId(), $user),
            'user' => $user,
            'notifications' => $user->notifications,
            'unreadCount' => $user->notifications()->unread()->count(),
            'activities' => $user->recyclingActivities()->latest('completed_at')->take(8)->get(),
            'pickups' => $user->pickupRequests()->latest()->take(5)->get(),
            'certificates' => $user->certificates()->latest('issued_at')->take(5)->get(),
            'logs' => $user->activityLogs()->latest('created_at')->take(8)->get(),
            'centers' => RecyclingCenter::query()->where('status', 'active')->latest()->take(6)->get(),
            'requests' => $user->recyclingRequests()->with(['device', 'recyclingCenter'])->latest()->take(8)->get(),
            'rewardPoints' => $user->rewardPoints()->latest()->take(8)->get(),
        ]);
    }

    public function profile(Request $request)
    {
        return view('account.profile', [
            'user' => $request->user()->load('settings'),
        ]);
    }

    public function updateProfile(
        Request $request,
        MediaUploadService $media,
        EmailVerificationService $verification,
        ActivityLogger $logger,
    ) {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'organization' => ['nullable', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'max:3072'],
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar_url'] = $media->avatar($request->file('avatar'));
        }

        if ($validated['email'] !== $user->email) {
            $validated['email_verified_at'] = null;
        }

        unset($validated['avatar']);
        $user->fill($validated)->save();
        $logger->record('account.profile_updated', 'Updated profile information.', $request, $user);

        if ($user->email_verified_at === null) {
            $code = $verification->issue($user);

            return back()
                ->with('status', app()->isLocal()
                    ? "Profile updated. Email verification is pending. Local code: {$code}"
                    : 'Profile updated. Email verification is pending.');
        }

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request, ActivityLogger $logger)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $request->user()->forceFill(['password' => Hash::make($validated['password'])])->save();
        $logger->record('account.password_updated', 'Changed account password.', $request, $request->user());

        return back()->with('status', 'Password updated.');
    }

    public function settings(Request $request)
    {
        return view('account.settings', [
            'user' => $request->user()->load('settings'),
        ]);
    }

    public function updateSettings(Request $request, ActivityLogger $logger)
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::in(['system', 'light', 'dark'])],
            'density' => ['required', Rule::in(['comfortable', 'compact'])],
            'timezone' => ['required', 'string', 'max:64'],
            'locale' => ['required', 'string', 'max:12'],
            'email_notifications' => ['nullable', 'boolean'],
            'product_notifications' => ['nullable', 'boolean'],
            'community_updates' => ['nullable', 'boolean'],
        ]);

        $request->user()->settings()->updateOrCreate([], [
            'theme' => $validated['theme'],
            'density' => $validated['density'],
            'timezone' => $validated['timezone'],
            'locale' => $validated['locale'],
            'notification_channels' => [
                'email' => $request->boolean('email_notifications'),
                'product' => $request->boolean('product_notifications'),
                'community' => $request->boolean('community_updates'),
                'in_app' => true,
            ],
        ]);

        $logger->record('account.settings_updated', 'Updated workspace settings.', $request, $request->user());

        return back()->with('status', 'Settings saved.');
    }

    public function markNotificationRead(Request $request, UserNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->forceFill(['read_at' => now()])->save();

        return back()->with('status', 'Notification marked as read.');
    }

    public function bookmark(Request $request, FacilityFinder $finder)
    {
        $validated = $request->validate([
            'facility_id' => ['required', 'string', 'max:120'],
        ]);

        $facility = $finder->find($validated['facility_id']);
        abort_unless($facility, 404);

        FacilityBookmark::updateOrCreate([
            'user_id' => $request->user()->id,
            'facility_id' => $facility['id'],
        ], [
            'facility_name' => $facility['name'],
            'facility_city' => $facility['city'],
            'metadata' => $facility,
        ]);

        return back()->with('status', 'Facility saved to bookmarks.');
    }

    public function removeBookmark(Request $request, FacilityBookmark $bookmark)
    {
        abort_unless($bookmark->user_id === $request->user()->id, 403);

        $bookmark->delete();

        return back()->with('status', 'Bookmark removed.');
    }
}
    




