<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HandoverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();
        $activities = \App\Models\Activity::all();
        
        if ($users->count() >= 2 && $activities->count() > 0) {
            // Create sample handover data
            \App\Models\DailyHandover::create([
                'from_user_id' => $users[0]->id,
                'to_user_id' => $users[1]->id,
                'handover_date' => now()->format('Y-m-d'),
                'shift_summary' => 'Morning shift completed successfully. All critical systems checked and running normally. Completed 3 priority tasks and responded to 5 customer inquiries.',
                'pending_tasks' => 'Review quarterly reports by EOD, Follow up with client meeting scheduled for tomorrow at 2 PM, Update project documentation for the new feature release',
                'important_notes' => 'Server maintenance is scheduled for tonight at 2 AM - ensure all users are notified. Client ABC is expecting an urgent update on project status by 4 PM today. New security patches need to be applied to production servers.',
                'activities_data' => json_encode([
                    ['activity_id' => $activities[0]->id, 'status' => 'completed', 'notes' => 'Finished ahead of schedule'],
                ]),
                'handover_time' => now(),
                'is_acknowledged' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Create an acknowledged handover from yesterday
            if ($users->count() >= 3) {
                \App\Models\DailyHandover::create([
                    'from_user_id' => $users[1]->id,
                    'to_user_id' => $users[2]->id,
                    'handover_date' => now()->subDay()->format('Y-m-d'),
                    'shift_summary' => 'Evening shift concluded without major incidents. Processed all pending orders and handled customer support tickets.',
                    'pending_tasks' => 'Complete monthly backup verification, Update team calendar with new project deadlines',
                    'important_notes' => 'All systems running smoothly. New employee starts Monday and will need workspace setup.',
                    'activities_data' => json_encode([]),
                    'handover_time' => now()->subDay(),
                    'is_acknowledged' => true,
                    'acknowledged_at' => now()->subHours(12),
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subHours(12),
                ]);
            }
        }
    }
}
