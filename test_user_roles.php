<?php
// Test script to verify User role functionality
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

try {
    echo "Testing User role functionality...\n\n";
    
    // Get some users to test
    $users = User::take(5)->get();
    
    foreach ($users as $user) {
        echo "User: {$user->name} (ID: {$user->id})\n";
        echo "  Role: " . ($user->role ?? 'NULL') . "\n";
        echo "  is_admin: " . ($user->is_admin ? 'true' : 'false') . "\n";
        echo "  isAdmin(): " . ($user->isAdmin() ? 'true' : 'false') . "\n";
        echo "  isManager(): " . ($user->isManager() ? 'true' : 'false') . "\n";
        echo "  isUser(): " . ($user->isUser() ? 'true' : 'false') . "\n";
        echo "  canSearchAllActivities(): " . ($user->canSearchAllActivities() ? 'true' : 'false') . "\n";
        echo "  canManageReports(): " . ($user->canManageReports() ? 'true' : 'false') . "\n";
        echo "  ---\n";
    }
    
    // Check how many users have each role
    echo "\nRole distribution:\n";
    echo "Total users: " . User::count() . "\n";
    echo "Admins: " . User::where('role', 'admin')->count() . "\n";
    echo "Managers: " . User::where('role', 'manager')->count() . "\n";
    echo "Users: " . User::where('role', 'user')->count() . "\n";
    echo "NULL roles: " . User::whereNull('role')->count() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
