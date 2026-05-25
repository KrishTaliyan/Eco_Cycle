<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ShopOwnerController;
use App\Http\Controllers\Sustainability\CertificateController;
use App\Http\Controllers\Sustainability\DashboardController;
use App\Http\Controllers\Sustainability\DeviceController;
use App\Http\Controllers\Sustainability\FacilityController;
use App\Http\Controllers\Sustainability\PageController;
use App\Http\Controllers\Sustainability\PickupController;
use App\Http\Controllers\Sustainability\QuizController;
use App\Http\Controllers\Sustainability\RecyclingController;
use App\Http\Controllers\Sustainability\SustainabilityController;
use Illuminate\Support\Facades\Route;

Route::get('/', SustainabilityController::class)->name('sustainability.index');
Route::get('/facilities', [PageController::class, 'facilities'])->name('facilities');
Route::get('/pickup', [PageController::class, 'pickup'])->name('pickup');
Route::get('/learn', [PageController::class, 'learn'])->name('learn');
Route::get('/rewards', [PageController::class, 'rewards'])->name('rewards');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('contact.submit');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
    Route::post('/demo-login', [AuthController::class, 'demoLogin'])->middleware('throttle:6,1')->name('login.demo');
    Route::get('/signup', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/signup', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordReset'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/verify-otp', [AuthController::class, 'showOtp'])->name('verification.otp');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:6,1')->name('verification.otp.store');
    Route::post('/verify-otp/resend', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1')->name('verification.otp.resend');

    Route::get('/dashboard', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AccountController::class, 'updatePassword'])->name('profile.password');
    Route::get('/settings', [AccountController::class, 'settings'])->name('settings');
    Route::put('/settings', [AccountController::class, 'updateSettings'])->name('settings.update');
    Route::post('/notifications/{notification}/read', [AccountController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/bookmarks', [AccountController::class, 'bookmark'])->name('bookmarks.store');
    Route::delete('/bookmarks/{bookmark}', [AccountController::class, 'removeBookmark'])->name('bookmarks.destroy');

    Route::post('/customer/recycling-requests', [CustomerController::class, 'storeRequest'])
        ->middleware('role:customer')
        ->name('customer.requests.store');

    Route::get('/shop', [ShopOwnerController::class, 'dashboard'])
        ->middleware('role:shop_owner')
        ->name('shop.dashboard');
    Route::post('/shop/centers', [ShopOwnerController::class, 'storeCenter'])
        ->middleware('role:shop_owner')
        ->name('shop.centers.store');
    Route::put('/shop/requests/{recyclingRequest}', [ShopOwnerController::class, 'updateRequest'])
        ->middleware('role:shop_owner')
        ->name('shop.requests.update');

    Route::get('/admin', AdminController::class)->middleware('role:admin')->name('admin.dashboard');
    Route::post('/admin/users', [AdminController::class, 'storeUser'])->middleware('role:admin')->name('admin.users.store');
    Route::put('/admin/users/{user}', [AdminController::class, 'updateUser'])->middleware('role:admin')->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->middleware('role:admin')->name('admin.users.destroy');
    Route::post('/admin/centers', [AdminController::class, 'storeCenter'])->middleware('role:admin')->name('admin.centers.store');
    Route::put('/admin/requests/{recyclingRequest}', [AdminController::class, 'updateRequest'])->middleware('role:admin')->name('admin.requests.update');
});

Route::prefix('api')->name('api.')->middleware('throttle:90,1')->group(function () {
    Route::get('/facilities/nearest', [FacilityController::class, 'nearest'])->name('facilities.nearest');
    Route::post('/devices/analyze', [DeviceController::class, 'analyze'])->name('devices.analyze');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/quiz/submit', [QuizController::class, 'submit'])->name('quiz.submit');
    Route::post('/recycling/complete', [RecyclingController::class, 'complete'])->name('recycling.complete');
    Route::post('/pickups/schedule', [PickupController::class, 'schedule'])->name('pickups.schedule');
});

Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
Route::get('/certificates/verify/{token}', [CertificateController::class, 'verify'])->name('certificates.verify');
