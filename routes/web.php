<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityUpdateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DailyHandoverController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Dashboard Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Activity Routes
    Route::resource('activities', ActivityController::class);
    Route::post('activities/{activity}/quick-update', [ActivityController::class, 'quickUpdate'])->name('activities.quick-update');
    Route::post('activities/{activity}/updates', [ActivityUpdateController::class, 'store'])->name('activities.updates.store');
    Route::get('activities/{activity}/updates', [ActivityUpdateController::class, 'index'])->name('activities.updates.index');
    
    // Daily Handover Routes
    Route::resource('handovers', DailyHandoverController::class);
    Route::post('handovers/{handover}/acknowledge', [DailyHandoverController::class, 'acknowledge'])->name('handovers.acknowledge');
    Route::get('/handovers/report/daily', [DailyHandoverController::class, 'dailyReport'])->name('handovers.daily-report');
    
    // Reports Routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/generate', [ReportController::class, 'generateFilteredReport'])->name('reports.generate');
    Route::get('/reports/activity/{id}/history', [ReportController::class, 'showActivityHistory'])->name('reports.activity.history');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    
    // AJAX Routes for dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
    Route::get('/dashboard/activities/today', [DashboardController::class, 'getTodayActivities'])->name('dashboard.activities.today');
    Route::get('/dashboard/updates/recent', [DashboardController::class, 'getRecentUpdates'])->name('dashboard.updates.recent');
    
    // Test route for debugging policy
    Route::get('/test-policy', function () {
        $user = \App\Models\User::find(1);
        $activity = \App\Models\Activity::find(1);
        
        \Illuminate\Support\Facades\Auth::login($user);
        
        $policy = new \App\Policies\ActivityPolicy();
        $directResult = $policy->update($user, $activity);
        
        $gateResult = \Illuminate\Support\Facades\Gate::allows('update', $activity);
        
        return "<h2>Policy Test Results</h2>" .
               "<p><strong>User:</strong> {$user->name} (ID: {$user->id})</p>" .
               "<p><strong>Is Admin:</strong> " . ($user->is_admin ? 'YES' : 'NO') . "</p>" .
               "<p><strong>Activity:</strong> {$activity->title} (ID: {$activity->id})</p>" .
               "<p><strong>Created by:</strong> {$activity->created_by}</p>" .
               "<p><strong>Assigned to:</strong> {$activity->assigned_to}</p>" .
               "<p><strong>Direct Policy Result:</strong> " . ($directResult ? 'TRUE' : 'FALSE') . "</p>" .
               "<p><strong>Gate Result:</strong> " . ($gateResult ? 'TRUE' : 'FALSE') . "</p>" .
               "<p><strong>Authenticated User:</strong> " . (\Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : 'NULL') . "</p>";
    });
});
