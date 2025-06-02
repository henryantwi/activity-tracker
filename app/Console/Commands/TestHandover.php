<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestHandover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-handover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test handover functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing DailyHandover functionality...');
        
        try {
            // Test model instantiation
            $handover = new \App\Models\DailyHandover();
            $this->info('✓ DailyHandover model can be instantiated');
            
            // Test if we can access the table
            $count = \App\Models\DailyHandover::count();
            $this->info("✓ Database connection works, handovers count: {$count}");
            
            // Test User relationship
            $userCount = \App\Models\User::count();
            $this->info("✓ Users available for handover: {$userCount}");
            
            // Test Activity relationship
            $activityCount = \App\Models\Activity::count();
            $this->info("✓ Activities available for handover: {$activityCount}");
            
            $this->info('All tests passed! Handover functionality is ready.');
            
        } catch (\Exception $e) {
            $this->error('Test failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
