<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProcessActivityReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $reportType;
    protected $dateFrom;
    protected $dateTo;
    protected $userId;
    protected $requestedBy;

    /**
     * Create a new job instance.
     */
    public function __construct($reportType, $dateFrom, $dateTo, $userId = null, $requestedBy = null)
    {
        $this->reportType = $reportType;
        $this->dateFrom = Carbon::parse($dateFrom);
        $this->dateTo = Carbon::parse($dateTo);
        $this->userId = $userId;
        $this->requestedBy = $requestedBy;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $data = $this->generateReportData();
            $filename = $this->generateReportFile($data);
            
            if ($this->requestedBy) {
                $this->sendReportEmail($filename);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to process activity report: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateReportData()
    {
        $query = Activity::with(['creator', 'assignee']);
        
        // Apply date range
        $query->whereBetween('created_at', [$this->dateFrom, $this->dateTo]);
        
        // Apply user filter if specified
        if ($this->userId) {
            $query->where(function($q) {
                $q->where('assigned_to', $this->userId)
                  ->orWhere('created_by', $this->userId);
            });
        }
        
        switch ($this->reportType) {
            case 'activities':
                return $query->orderBy('created_at', 'desc')->get();
                
            case 'performance':
                return $this->generatePerformanceData($query);
                
            case 'status':
                return $this->generateStatusData($query);
                
            default:
                return $query->get();
        }
    }

    private function generatePerformanceData($query)
    {
        $activities = $query->get();
        $users = User::all();
        
        $data = [];
        foreach ($users as $user) {
            $userActivities = $activities->where('assigned_to', $user->id);
            
            $data[] = [
                'user' => $user->name,
                'total' => $userActivities->count(),
                'completed' => $userActivities->where('status', 'completed')->count(),
                'in_progress' => $userActivities->where('status', 'in_progress')->count(),
                'pending' => $userActivities->where('status', 'pending')->count(),
                'completion_rate' => $userActivities->count() > 0 
                    ? ($userActivities->where('status', 'completed')->count() / $userActivities->count()) * 100 
                    : 0
            ];
        }
        
        return collect($data)->sortByDesc('total');
    }

    private function generateStatusData($query)
    {
        $activities = $query->get();
        
        return [
            'status_summary' => [
                'pending' => $activities->where('status', 'pending')->count(),
                'in_progress' => $activities->where('status', 'in_progress')->count(),
                'completed' => $activities->where('status', 'completed')->count(),
                'cancelled' => $activities->where('status', 'cancelled')->count(),
            ],
            'total' => $activities->count(),
            'priority_summary' => [
                'high' => $activities->where('priority', 'high')->count(),
                'medium' => $activities->where('priority', 'medium')->count(),
                'low' => $activities->where('priority', 'low')->count(),
            ]
        ];
    }

    private function generateReportFile($data)
    {
        $filename = 'reports/' . $this->reportType . '_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        $csv = [];
        
        if ($this->reportType === 'activities') {
            // CSV headers
            $csv[] = ['Title', 'Status', 'Priority', 'Category', 'Assigned To', 'Created By', 'Due Date', 'Created At'];
            
            foreach ($data as $activity) {
                $csv[] = [
                    $activity->title,
                    $activity->status,
                    $activity->priority,
                    $activity->category,
                    $activity->assignee->name ?? 'Unassigned',
                    $activity->creator->name,
                    $activity->due_date ? $activity->due_date->format('Y-m-d') : '',
                    $activity->created_at->format('Y-m-d H:i:s')
                ];
            }
        } elseif ($this->reportType === 'performance') {
            $csv[] = ['User', 'Total Activities', 'Completed', 'In Progress', 'Pending', 'Completion Rate'];
            
            foreach ($data as $row) {
                $csv[] = [
                    $row['user'],
                    $row['total'],
                    $row['completed'],
                    $row['in_progress'],
                    $row['pending'],
                    number_format($row['completion_rate'], 2) . '%'
                ];
            }
        }
        
        // Convert to CSV string
        $csvContent = '';
        foreach ($csv as $row) {
            $csvContent .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }
        
        Storage::put($filename, $csvContent);
        
        return $filename;
    }

    private function sendReportEmail($filename)
    {
        if (!$this->requestedBy) return;
        
        $user = User::find($this->requestedBy);
        if (!$user) return;
        
        // Here you would send an email with the report attached
        // This is a placeholder for actual email implementation
        \Log::info("Report generated and ready for user {$user->email}: {$filename}");
    }
}
     */
    public function handle(): void
    {
        //
    }
}
