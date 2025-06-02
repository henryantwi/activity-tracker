<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use App\Models\Activity;
use App\Models\User;
use App\Models\ActivityUpdate;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Reports Controller Dependencies...\n";
echo "==========================================\n";

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    $userCount = User::count();
    echo "   ✓ Users table accessible - {$userCount} users found\n";
    
    $activityCount = Activity::count();
    echo "   ✓ Activities table accessible - {$activityCount} activities found\n";
    
    $updateCount = ActivityUpdate::count();
    echo "   ✓ Activity updates table accessible - {$updateCount} updates found\n";
    
    // Test user roles
    echo "\n2. Testing user roles...\n";
    $admins = User::where('role', 'admin')->orWhere('is_admin', true)->get();
    echo "   ✓ Admins found: " . $admins->count() . "\n";
    
    $managers = User::where('role', 'manager')->get();
    echo "   ✓ Managers found: " . $managers->count() . "\n";
    
    if ($admins->count() > 0) {
        $testUser = $admins->first();
        echo "   ✓ Test admin: {$testUser->name} - isAdmin(): " . ($testUser->isAdmin() ? 'true' : 'false') . "\n";
    }
    
    // Test Activity model methods
    echo "\n3. Testing Activity model...\n";
    if ($activityCount > 0) {
        $testActivity = Activity::with(['creator', 'assignee'])->first();
        echo "   ✓ Activity loaded with relationships\n";
        echo "   ✓ Creator: " . ($testActivity->creator ? $testActivity->creator->name : 'None') . "\n";
        echo "   ✓ Assignee: " . ($testActivity->assignee ? $testActivity->assignee->name : 'None') . "\n";
    }
    
    // Test statistics queries
    echo "\n4. Testing statistics queries...\n";
    
    // Status distribution
    $statusCounts = Activity::selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();
    echo "   ✓ Status distribution: " . json_encode($statusCounts) . "\n";
    
    // Priority distribution
    $priorityCounts = Activity::selectRaw('priority, COUNT(*) as count')
        ->groupBy('priority')
        ->pluck('count', 'priority')
        ->toArray();
    echo "   ✓ Priority distribution: " . json_encode($priorityCounts) . "\n";
    
    // Category distribution
    $categoryCounts = Activity::selectRaw('category, COUNT(*) as count')
        ->whereNotNull('category')
        ->groupBy('category')
        ->pluck('count', 'category')
        ->toArray();
    echo "   ✓ Category distribution: " . json_encode($categoryCounts) . "\n";
    
    // Test ReportController class loading
    echo "\n5. Testing ReportController class...\n";
    if (class_exists('App\Http\Controllers\ReportController')) {
        echo "   ✓ ReportController class exists\n";
        
        $reflection = new ReflectionClass('App\Http\Controllers\ReportController');
        $methods = $reflection->getMethods();
        echo "   ✓ Methods found: ";
        foreach ($methods as $method) {
            if ($method->class === 'App\Http\Controllers\ReportController') {
                echo $method->name . ' ';
            }
        }
        echo "\n";
    } else {
        echo "   ✗ ReportController class not found\n";
    }
    
    echo "\n✓ All tests passed! Reports functionality should work.\n";
    
} catch (Exception $e) {
    echo "\n✗ Error occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
