<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'role' => 'admin',
            'bio' => 'System Administrator',
            'location' => 'Head Office',
            'phone' => '+1234567890',
            'email_verified_at' => now(),
        ]);

        // Create manager user
        User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'role' => 'manager',
            'bio' => 'Team Manager - Can search and report on all activities',
            'location' => 'Management Office',
            'phone' => '+1234567899',
            'email_verified_at' => now(),
        ]);

        // Create regular users
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'role' => 'user',
            'bio' => 'Senior Developer',
            'location' => 'Development Team',
            'phone' => '+1234567891',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'role' => 'manager',
            'bio' => 'Project Manager - Can generate reports and search activities',
            'location' => 'Management',
            'phone' => '+1234567892',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Mike Johnson',
            'email' => 'mike@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'role' => 'user',
            'bio' => 'QA Engineer',
            'location' => 'Quality Assurance',
            'phone' => '+1234567893',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Sarah Williams',
            'email' => 'sarah@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'role' => 'user',
            'bio' => 'UI/UX Designer',
            'location' => 'Design Team',
            'phone' => '+1234567894',
            'email_verified_at' => now(),
        ]);
    }
}
