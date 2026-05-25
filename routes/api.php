<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:90,1')->group(function () {
    Route::get('/search', SearchController::class)->name('api.search');

    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('/register', [AuthApiController::class, 'register'])->middleware('throttle:6,1')->name('register');
        Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:6,1')->name('login');
        Route::post('/refresh', [AuthApiController::class, 'refresh'])->middleware('throttle:12,1')->name('refresh');
        Route::post('/logout', [AuthApiController::class, 'logout'])->middleware('throttle:12,1')->name('logout');
        Route::get('/me', [AuthApiController::class, 'me'])->middleware('jwt')->name('me');
    });

    Route::middleware('jwt')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('api.notifications.read');
    });
});
