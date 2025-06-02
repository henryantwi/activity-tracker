<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\User;
use App\Models\ActivityUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendDailyActivitySummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($date = null, $userId = null)
    {
        $this->date = $date ? Carbon::parse($date) : today();
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->userId) {
                $this->sendSummaryToUser($this->userId);
            } else {
                $this->sendSummaryToAllUsers();
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send daily activity summary: ' . $e->getMessage());
            // Don't rethrow in production - let the job complete
            // throw $e;
        }
    }

    private function sendSummaryToAllUsers()
    {
        $users = User::where('is_admin', true)->get();
        
        foreach ($users as $user) {
            $this->sendSummaryToUser($user->id);
        }
    }

    private function sendSummaryToUser($userId)
    {
        $user = User::find($userId);
        if (!$user) return;

        $summary = $this->generateDailySummary($user);
        
        // Here you would actually send the email
        // For now, we'll just log it
        \Log::info("Daily Activity Summary for {$user->email}", $summary);
        
        // Placeholder for actual email sending:
        // Mail::to($user->email)->send(new DailyActivitySummaryMail($summary));
    }

    private function generateDailySummary($user)
    {
        $date = $this->date;
        
        // Get activities for the day
        $activitiesQuery = Activity::whereDate('created_at', $date);
        
        // If not admin, limit to user's activities
        if (!$user->is_admin) {
            $activitiesQuery->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }
        
        $activities = $activitiesQuery->with(['creator', 'assignee'])->get();
        
        // Get updates for the day
        $updatesQuery = ActivityUpdate::whereDate('update_time', $date)
                                     ->with(['activity', 'user']);
        
        if (!$user->is_admin) {
            $updatesQuery->whereHas('activity', function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }
        
        $updates = $updatesQuery->get();
        
        // Get overdue activities
        $overdueQuery = Activity::where('due_date', '<', $date)
                               ->whereIn('status', ['pending', 'in_progress']);
        
        if (!$user->is_admin) {
            $overdueQuery->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }
        
        $overdueActivities = $overdueQuery->with(['creator', 'assignee'])->get();
        
        // Generate statistics
        $stats = [
            'date' => $date->format('Y-m-d'),
            'total_activities' => $activities->count(),
            'new_activities' => $activities->count(),
            'completed_today' => $activities->where('status', 'completed')->count(),
            'in_progress' => $activities->where('status', 'in_progress')->count(),
            'pending' => $activities->where('status', 'pending')->count(),
            'total_updates' => $updates->count(),
            'overdue_count' => $overdueActivities->count(),
        ];
        
        // Priority breakdown
        $priorityStats = [
            'high' => $activities->where('priority', 'high')->count(),
            'medium' => $activities->where('priority', 'medium')->count(),
            'low' => $activities->where('priority', 'low')->count(),
        ];
        
        // Category breakdown
        $categoryStats = [];
        foreach ($activities->groupBy('category') as $category => $categoryActivities) {
            $categoryStats[$category] = $categoryActivities->count();
        }
        
        return [
            'user' => $user,
            'date' => $date,
            'stats' => $stats,
            'priority_stats' => $priorityStats,
            'category_stats' => $categoryStats,
            'activities' => $activities,
            'updates' => $updates,
            'overdue_activities' => $overdueActivities,
            'top_activities' => $activities->sortByDesc('priority')->take(5),
            'recent_updates' => $updates->sortByDesc('update_time')->take(10),
        ];
    }

    /**
     * Generate HTML content for the summary
     */
    private function generateSummaryHtml($summary)
    {
        $html = "<h2>Daily Activity Summary - {$summary['date']->format('F j, Y')}</h2>";
        
        $html .= "<h3>Statistics</h3>";
        $html .= "<ul>";
        foreach ($summary['stats'] as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            $html .= "<li><strong>{$label}:</strong> {$value}</li>";
        }
        $html .= "</ul>";
        
        if ($summary['overdue_activities']->count() > 0) {
            $html .= "<h3>⚠️ Overdue Activities ({$summary['overdue_activities']->count()})</h3>";
            $html .= "<ul>";
            foreach ($summary['overdue_activities'] as $activity) {
                $html .= "<li><strong>{$activity->title}</strong> - Due: {$activity->due_date->format('Y-m-d')} - Assigned to: {$activity->assignee->name ?? 'Unassigned'}</li>";
            }
            $html .= "</ul>";
        }
        
        if ($summary['activities']->count() > 0) {
            $html .= "<h3>Today's Activities ({$summary['activities']->count()})</h3>";
            foreach ($summary['activities'] as $activity) {
                $html .= "<p><strong>{$activity->title}</strong> - Status: {$activity->status} - Priority: {$activity->priority}</p>";
            }
        }
        
        return $html;
    }
}
     */
    public function handle(): void
    {
        //
    }
}
