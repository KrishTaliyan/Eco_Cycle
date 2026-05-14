<?php

use App\Http\Controllers\AuthController;
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

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/signup', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/signup', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/facilities/nearest', [FacilityController::class, 'nearest'])->name('facilities.nearest');
    Route::post('/devices/analyze', [DeviceController::class, 'analyze'])->name('devices.analyze');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/quiz/submit', [QuizController::class, 'submit'])->name('quiz.submit');
    Route::post('/recycling/complete', [RecyclingController::class, 'complete'])->name('recycling.complete');
    Route::post('/pickups/schedule', [PickupController::class, 'schedule'])->name('pickups.schedule');
});

Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
Route::get('/certificates/verify/{token}', [CertificateController::class, 'verify'])->name('certificates.verify');
