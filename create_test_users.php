<?php
// Script to create test manager and admin users
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

try {
    echo "Creating test users with different roles...\n\n";
    
    // Get existing users
    $users = User::all();
    
    if ($users->count() >= 2) {
        // Make first user an admin
        $admin = $users->first();
        $admin->role = 'admin';
        $admin->is_admin = true;
        $admin->save();
        echo "Updated {$admin->name} to admin role\n";
        
        // Make second user a manager  
        $manager = $users->skip(1)->first();
        $manager->role = 'manager';
        $manager->is_admin = false;
        $manager->save();
        echo "Updated {$manager->name} to manager role\n";
    }
    
    // Create additional test users if needed
    if ($users->count() < 3) {
        $newUser = User::create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'is_admin' => false,
        ]);
        echo "Created new manager user: {$newUser->name}\n";
    }
    
    if ($users->count() < 4) {
        $newUser = User::create([
            'name' => 'Test User', 
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'is_admin' => false,
        ]);
        echo "Created new regular user: {$newUser->name}\n";
    }
    
    echo "\nFinal role distribution:\n";
    echo "Admins: " . User::where('role', 'admin')->count() . "\n";
    echo "Managers: " . User::where('role', 'manager')->count() . "\n";
    echo "Users: " . User::where('role', 'user')->count() . "\n";
    
    echo "\nAll users:\n";
    $allUsers = User::all();
    foreach ($allUsers as $user) {
        echo "- {$user->name} ({$user->email}): {$user->role}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
