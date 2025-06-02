<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Activity Tracker Implementation Verification ===\n\n";

// Check if users exist
echo "1. Checking Users and Roles:\n";
$users = \App\Models\User::select('name', 'email', 'role')->get();
foreach ($users as $user) {
    echo "   - {$user->name} ({$user->email}) - Role: {$user->role}\n";
}

echo "\n2. Testing Role Methods:\n";
$testUser = $users->first();
if ($testUser) {
    echo "   Testing with user: {$testUser->name}\n";
    echo "   - isAdmin(): " . ($testUser->isAdmin() ? 'true' : 'false') . "\n";
    echo "   - isManager(): " . ($testUser->isManager() ? 'true' : 'false') . "\n";
    echo "   - canSearchAllActivities(): " . ($testUser->canSearchAllActivities() ? 'true' : 'false') . "\n";
    echo "   - canManageReports(): " . ($testUser->canManageReports() ? 'true' : 'false') . "\n";
}

// Check activities count
echo "\n3. Activity Statistics:\n";
$totalActivities = \App\Models\Activity::count();
echo "   - Total activities in database: {$totalActivities}\n";

// Check routes
echo "\n4. Routes Check:\n";
$routes = \Illuminate\Support\Facades\Route::getRoutes();
$activityRoutes = [];
$reportRoutes = [];

foreach ($routes as $route) {
    if (str_contains($route->getName() ?? '', 'activities.')) {
        $activityRoutes[] = $route->getName();
    }
    if (str_contains($route->getName() ?? '', 'reports.')) {
        $reportRoutes[] = $route->getName();
    }
}

echo "   - Activity routes: " . implode(', ', $activityRoutes) . "\n";
echo "   - Report routes: " . implode(', ', $reportRoutes) . "\n";

echo "\n=== Verification Complete ===\n";
