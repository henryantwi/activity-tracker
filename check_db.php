<?php
// Simple script to check database structure
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "Checking database connection...\n";
    
    // Check connection
    DB::connection()->getPdo();
    echo "Database connected successfully!\n\n";
    
    // Check if users table exists
    if (Schema::hasTable('users')) {
        echo "Users table exists.\n";
        
        // Get column info
        $columns = Schema::getColumnListing('users');
        echo "Current columns in users table:\n";
        foreach ($columns as $column) {
            echo "- $column\n";
        }
        
        // Check if role column exists
        if (Schema::hasColumn('users', 'role')) {
            echo "\nRole column already exists!\n";
        } else {
            echo "\nRole column does not exist - migration needed.\n";
        }
    } else {
        echo "Users table does not exist!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
