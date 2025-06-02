<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        if ($users->count() === 0) {
            return;
        }

        $admin = $users->where('is_admin', true)->first();
        $regularUsers = $users->where('is_admin', false);

        // Sample activities with different statuses and priorities
        $activities = [
            [
                'title' => 'Implement User Authentication System',
                'description' => 'Create a comprehensive user authentication system with login, registration, and password reset functionality.',
                'priority' => 'high',
                'category' => 'development',
                'status' => 'in_progress',
                'due_date' => now()->addDays(7),
                'created_by' => $admin->id,
                'assigned_to' => $regularUsers->first()->id ?? $admin->id,
            ],
            [
                'title' => 'Design Dashboard UI',
                'description' => 'Create wireframes and mockups for the main dashboard interface.',
                'priority' => 'medium',
                'category' => 'design',
                'status' => 'completed',
                'due_date' => now()->subDays(2),
                'created_by' => $admin->id,
                'assigned_to' => $regularUsers->get(4)->id ?? $admin->id,
            ],
            [
                'title' => 'Write API Documentation',
                'description' => 'Document all API endpoints with request/response examples.',
                'priority' => 'medium',
                'category' => 'documentation',
                'status' => 'pending',
                'due_date' => now()->addDays(14),
                'created_by' => $regularUsers->get(1)->id ?? $admin->id,
                'assigned_to' => $regularUsers->first()->id ?? $admin->id,
            ],
            [
                'title' => 'Fix Critical Bug in Payment Module',
                'description' => 'Investigate and fix the payment processing error reported by users.',
                'priority' => 'high',
                'category' => 'maintenance',
                'status' => 'pending',
                'due_date' => now()->addDays(1),
                'created_by' => $admin->id,
                'assigned_to' => $regularUsers->first()->id ?? $admin->id,
            ],
            [
                'title' => 'Conduct Code Review',
                'description' => 'Review the authentication module code for security and best practices.',
                'priority' => 'medium',
                'category' => 'testing',
                'status' => 'in_progress',
                'due_date' => now()->addDays(3),
                'created_by' => $regularUsers->get(1)->id ?? $admin->id,
                'assigned_to' => $regularUsers->get(2)->id ?? $admin->id,
            ],
            [
                'title' => 'Database Optimization',
                'description' => 'Optimize database queries and add proper indexing.',
                'priority' => 'low',
                'category' => 'maintenance',
                'status' => 'pending',
                'due_date' => now()->addDays(21),
                'created_by' => $admin->id,
                'assigned_to' => $regularUsers->first()->id ?? $admin->id,
            ],
            [
                'title' => 'Team Meeting - Weekly Standup',
                'description' => 'Weekly team standup meeting to discuss progress and blockers.',
                'priority' => 'low',
                'category' => 'meeting',
                'status' => 'completed',
                'due_date' => now()->subDays(1),
                'created_by' => $regularUsers->get(1)->id ?? $admin->id,
                'assigned_to' => null,
            ],
            [
                'title' => 'Research New Framework Options',
                'description' => 'Research and evaluate new frontend framework options for the project.',
                'priority' => 'low',
                'category' => 'research',
                'status' => 'in_progress',
                'due_date' => now()->addDays(10),
                'created_by' => $admin->id,
                'assigned_to' => $regularUsers->first()->id ?? $admin->id,
            ],
            [
                'title' => 'Setup CI/CD Pipeline',
                'description' => 'Configure continuous integration and deployment pipeline.',
                'priority' => 'high',
                'category' => 'development',
                'status' => 'pending',
                'due_date' => now()->addDays(5),
                'created_by' => $admin->id,
                'assigned_to' => $regularUsers->first()->id ?? $admin->id,
            ],
            [
                'title' => 'User Testing Session',
                'description' => 'Conduct user testing session for the new dashboard features.',
                'priority' => 'medium',
                'category' => 'testing',
                'status' => 'pending',
                'due_date' => now()->addDays(12),
                'created_by' => $regularUsers->get(1)->id ?? $admin->id,
                'assigned_to' => $regularUsers->get(2)->id ?? $admin->id,
            ],
        ];

        foreach ($activities as $activityData) {
            $activity = Activity::create($activityData);
            
            // Add some activity updates for completed and in-progress activities
            if ($activity->status !== 'pending') {
                $activity->updates()->create([
                    'user_id' => $activity->created_by,
                    'status' => $activity->status === 'completed' ? 'in_progress' : $activity->status,
                    'remarks' => 'Activity status updated',
                    'update_time' => now()->subDays(rand(1, 5)),
                    'previous_data' => ['status' => 'pending'],
                    'new_data' => ['status' => $activity->status === 'completed' ? 'in_progress' : $activity->status],
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ]);
                
                if ($activity->status === 'completed') {
                    $activity->updates()->create([
                        'user_id' => $activity->assigned_to ?? $activity->created_by,
                        'status' => 'completed',
                        'remarks' => 'Activity completed successfully',
                        'update_time' => now()->subDays(rand(0, 2)),
                        'previous_data' => ['status' => 'in_progress'],
                        'new_data' => ['status' => 'completed'],
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    ]);
                }
            }
        }

        // Add some overdue activities
        $overdueActivities = [
            [
                'title' => 'Update Security Protocols',
                'description' => 'Review and update security protocols and documentation.',
                'priority' => 'high',
                'category' => 'maintenance',
                'status' => 'pending',
                'due_date' => now()->subDays(3),
                'created_by' => $admin->id,
                'assigned_to' => $regularUsers->get(2)->id ?? $admin->id,
            ],
            [
                'title' => 'Client Presentation Preparation',
                'description' => 'Prepare presentation materials for client meeting.',
                'priority' => 'medium',
                'category' => 'other',
                'status' => 'in_progress',
                'due_date' => now()->subDays(1),
                'created_by' => $regularUsers->get(1)->id ?? $admin->id,
                'assigned_to' => $regularUsers->get(1)->id ?? $admin->id,
            ],
        ];

        foreach ($overdueActivities as $activityData) {
            Activity::create($activityData);
        }
    }
}
