<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrowseProfileController;
use App\Http\Controllers\Api\ConnectionRequestController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PartnerPreferenceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProfilePhotoController;
use App\Http\Controllers\Api\Admin\AdminConnectionRequestController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminPaymentController;
use App\Http\Controllers\Api\Admin\AdminPricingController;
use App\Http\Controllers\Api\Admin\AdminProfileController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/locations/countries', [LocationController::class, 'countries']);
Route::get('/locations/regions', [LocationController::class, 'regions']);
Route::get('/locations/districts', [LocationController::class, 'districts']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::middleware('member.active')->group(function (): void {
        Route::get('/pricing', [PricingController::class, 'show']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'upsert']);
        Route::post('/profile/photos', [ProfilePhotoController::class, 'store']);
        Route::delete('/profile/photos/{profilePhoto}', [ProfilePhotoController::class, 'destroy']);
        Route::get('/profile-photos/{profilePhoto}', [ProfilePhotoController::class, 'show']);

        Route::get('/partner-preferences', [PartnerPreferenceController::class, 'show']);
        Route::put('/partner-preferences', [PartnerPreferenceController::class, 'upsert']);

        Route::get('/browse', [BrowseProfileController::class, 'index']);
        Route::get('/browse/{profile}', [BrowseProfileController::class, 'show']);

        Route::get('/connection-requests/sent', [ConnectionRequestController::class, 'sent']);
        Route::get('/connection-requests/received', [ConnectionRequestController::class, 'received']);
        Route::post('/connection-requests', [ConnectionRequestController::class, 'store']);
        Route::patch('/connection-requests/{connectionRequest}/accept', [ConnectionRequestController::class, 'accept']);
        Route::patch('/connection-requests/{connectionRequest}/reject', [ConnectionRequestController::class, 'reject']);
        Route::patch('/connection-requests/{connectionRequest}/cancel', [ConnectionRequestController::class, 'cancel']);

        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/connection-requests/{connectionRequest}/payments', [PaymentController::class, 'store']);
    });
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    Route::get('/settings/pricing', [AdminPricingController::class, 'show']);
    Route::patch('/settings/pricing', [AdminPricingController::class, 'update']);

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::patch('/users/{user}/block', [AdminUserController::class, 'block']);
    Route::patch('/users/{user}/unblock', [AdminUserController::class, 'unblock']);
    Route::patch('/users/{user}/activate', [AdminUserController::class, 'activate']);
    Route::patch('/users/{user}/deactivate', [AdminUserController::class, 'deactivate']);
    Route::patch('/users/{user}/hide-profile', [AdminUserController::class, 'hideProfile']);
    Route::patch('/users/{user}/unhide-profile', [AdminUserController::class, 'unhideProfile']);
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

    Route::get('/profiles/pending', [AdminProfileController::class, 'pending']);
    Route::patch('/profiles/{profile}/approve', [AdminProfileController::class, 'approve']);
    Route::patch('/profiles/{profile}/reject', [AdminProfileController::class, 'reject']);

    Route::get('/connection-requests', [AdminConnectionRequestController::class, 'index']);

    Route::get('/payments', [AdminPaymentController::class, 'index']);
    Route::patch('/payments/{payment}/confirm', [AdminPaymentController::class, 'confirm']);
    Route::patch('/payments/{payment}/reject', [AdminPaymentController::class, 'reject']);
});
