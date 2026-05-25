<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\EmailVerificationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request, ActivityLogger $logger, NotificationService $notifications)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The email or password is incorrect.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        $request->user()->settings()->firstOrCreate([]);
        $notifications->onboarding($request->user());
        $logger->record('auth.login', 'Signed in to the workspace.', $request, $request->user());

        return redirect()->intended(route('dashboard'));
    }

    public function demoLogin(Request $request, ActivityLogger $logger, NotificationService $notifications)
    {
        $user = User::firstOrCreate([
            'email' => 'demo@ecocycle.test',
        ], [
            'name' => 'EcoCycle Demo',
            'password' => 'Demo@12345',
            'role' => 'admin',
            'organization' => 'EcoCycle Smart',
        ]);

        $user->forceFill([
            'role' => 'admin',
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_login_at' => now(),
        ])->save();
        $user->settings()->firstOrCreate([]);
        $user->assignRole('admin');

        Auth::login($user);
        $request->session()->regenerate();
        $notifications->onboarding($user);
        $logger->record('auth.demo_login', 'Opened the demo workspace.', $request, $user);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Demo workspace ready. All controls are live.');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(
        Request $request,
        ActivityLogger $logger,
        EmailVerificationService $verification,
        NotificationService $notifications,
    ) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'organization' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::in(['customer', 'shop_owner'])],
        ]);

        $validated['role'] ??= 'customer';
        $user = User::create($validated);
        $user->assignRole($validated['role']);
        $user->settings()->create([
            'notification_channels' => [
                'email' => true,
                'in_app' => true,
                'product' => true,
            ],
            'dashboard_preferences' => [
                'default_range' => '6m',
                'show_community' => true,
            ],
        ]);
        $code = $verification->issue($user);

        Auth::login($user);
        $request->session()->regenerate();
        $notifications->onboarding($user);
        $logger->record('auth.register', 'Created a new EcoCycle account.', $request, $user);

        return redirect()
            ->route('dashboard')
            ->with('status', app()->isLocal()
                ? "Account created. Email verification is pending. Local code: {$code}"
                : 'Account created. Email verification is pending.');
    }

    public function logout(Request $request, ActivityLogger $logger)
    {
        if ($request->user()) {
            $logger->record('auth.logout', 'Signed out of the workspace.', $request, $request->user());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('sustainability.index');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordReset(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = PasswordBroker::sendResetLink($validated);

        return $status === PasswordBroker::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)])->onlyInput('email');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request, ActivityLogger $logger)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $status = PasswordBroker::reset(
            $validated,
            function (User $user, string $password) use ($logger, $request) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $logger->record('auth.password_reset', 'Reset account password.', $request, $user);
            },
        );

        return $status === PasswordBroker::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)])->onlyInput('email');
    }

    public function showOtp(Request $request)
    {
        if ($request->user()->email_verified_at) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request, EmailVerificationService $verification, ActivityLogger $logger)
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (! $verification->verify($request->user(), $validated['code'])) {
            return back()->withErrors(['code' => 'That code is invalid or expired.'])->onlyInput('code');
        }

        $logger->record('auth.email_verified', 'Verified email with OTP.', $request, $request->user());

        return redirect()->route('dashboard')->with('status', 'Email verified. Your workspace is fully active.');
    }

    public function resendOtp(Request $request, EmailVerificationService $verification)
    {
        if ($request->user()->email_verified_at) {
            return redirect()->route('dashboard');
        }

        $code = $verification->issue($request->user());

        return back()->with('status', app()->isLocal() ? "New code sent. Local code: {$code}" : 'A new verification code has been sent.');
    }
}
