<?php

use App\Http\Controllers\API\SSOController;
use App\Http\Controllers\API\DashboardController;
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

// SSO API Routes
Route::post('/sso/verify', [SSOController::class, 'verify'])
     ->middleware(['throttle:60,1', 'readonly.db'])
     ->name('sso.verify');

Route::get('/sso/logout', [SSOController::class, 'logout'])
     ->middleware('throttle:60,1')
     ->name('sso.logout');

Route::post('/sso/logout', [SSOController::class, 'logout'])
     ->middleware('throttle:60,1')
     ->name('sso.logout.post');

Route::get('/sso/verify/logout', [SSOController::class, 'logout'])
     ->middleware('throttle:60,1')
     ->name('sso.verify.logout');

Route::post('/sso/verify/logout', [SSOController::class, 'logout'])
     ->middleware('throttle:60,1')
     ->name('sso.verify.logout.post');

// Dashboard API Routes
Route::prefix('dashboard')->middleware(['throttle:60,1'])->group(function () {
    // Comprehensive overview
    Route::get('/overview', [DashboardController::class, 'overview']);

    // Total counts from all databases
    Route::get('/totals', [DashboardController::class, 'totalCounts']);

    // Time series data for charts
    Route::get('/pencatatan-izin/time-series', [DashboardController::class, 'pencatatanIzinTimeSeries']);
    Route::get('/pencatatan-izin/time-series-all', [DashboardController::class, 'pencatatanIzinTimeSeriesAll']);
    Route::get('/chart-data', [DashboardController::class, 'chartData']);

    // Comparison endpoints
    Route::get('/pencatatan-izin/year-comparison', [DashboardController::class, 'yearComparison']);
    Route::get('/pencatatan-izin/monthly-comparison', [DashboardController::class, 'monthlyComparison']);

    // Province statistics
    Route::get('/province-ranking', [DashboardController::class, 'provinceRanking']);
    Route::get('/province-stats', [DashboardController::class, 'getProvinceStats']);
    Route::get('/monthly-province-chart', [DashboardController::class, 'getMonthlyProvinceChart']);

    // Daily statistics
    Route::get('/daily-stats', [DashboardController::class, 'dailyStats']);

    // Legacy endpoints (backward compatibility)
    Route::get('/pencatatan-izin', [DashboardController::class, 'getPencatatanIzinData']);
    Route::get('/pencatatan-izin/summary', [DashboardController::class, 'getPencatatanIzinSummary']);

    // Database connection testing
    Route::get('/test-connections', [DashboardController::class, 'testConnections']);
});