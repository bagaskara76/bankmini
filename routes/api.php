<?php

use App\Http\Controllers\Api\MobileCustomerController;
use App\Http\Controllers\DeploymentController;
use App\Http\Middleware\EnsureCustomerApiAccess;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/deploy/migrate', [DeploymentController::class, 'migrate'])
    ->middleware('throttle:3,1');

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileCustomerController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('/password/otp', [MobileCustomerController::class, 'requestPasswordOtp'])
        ->middleware('throttle:3,1');
    Route::post('/password/otp/verify', [MobileCustomerController::class, 'verifyPasswordOtp'])
        ->middleware('throttle:10,1');
    Route::post('/password/reset', [MobileCustomerController::class, 'resetPassword'])
        ->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', EnsureCustomerApiAccess::class])->group(function () {
        Route::get('/dashboard', [MobileCustomerController::class, 'dashboard']);
        Route::get('/profile', [MobileCustomerController::class, 'profile']);
        Route::get('/transactions', [MobileCustomerController::class, 'transactions']);
        Route::get('/transactions/monthly-reports', [MobileCustomerController::class, 'monthlyTransactionReports']);
        Route::get('/transactions/monthly-reports/{year}/{month}/pdf', [MobileCustomerController::class, 'monthlyTransactionReportPdf'])
            ->whereNumber(['year', 'month'])
            ->name('mobile.transactions.monthly_reports.pdf');
        Route::post('/logout', [MobileCustomerController::class, 'logout']);
        Route::post('/password/change', [MobileCustomerController::class, 'changePassword'])
            ->middleware('throttle:5,1');
        Route::post('/pin/change', [MobileCustomerController::class, 'changePin'])
            ->middleware('throttle:5,1');
    });
});
