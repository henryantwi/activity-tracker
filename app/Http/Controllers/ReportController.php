<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct()
    {
        // Only admins and managers can access reports
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user || (!$user->isAdmin() && !$user->isManager())) {
                abort(403, 'You are not authorized to access reports');
            }
            return $next($request);
        });
    }

    public function index()
    {
        // Quick report stats
        $totalActivities = Activity::count();
        $completedActivities = Activity::where('status', 'completed')->count();
        $overdueActivities = Activity::where('due_date', '<', now())
                                   ->where('status', '!=', 'completed')
                                   ->count();
        $activeUsers = User::whereHas('assignedActivities')->count();
        
        return view('reports.index', compact(
            'totalActivities',
            'completedActivities', 
            'overdueActivities',
            'activeUsers'
        ));
    }
    
    public function generate(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:activities,users,status,performance',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'format' => 'required|in:table,chart'
        ]);
        
        $dateFrom = Carbon::parse($request->date_from);
        $dateTo = Carbon::parse($request->date_to);
        
        switch ($request->report_type) {
            case 'activities':
                $data = $this->getActivitiesReport($dateFrom, $dateTo);
                break;
            case 'users':
                $data = $this->getUsersReport($dateFrom, $dateTo);
                break;
            case 'status':
                $data = $this->getStatusReport($dateFrom, $dateTo);
                break;
            case 'performance':
                $data = $this->getPerformanceReport($dateFrom, $dateTo);
                break;
            default:
                $data = [];
        }
        
        if ($request->format === 'chart') {
            return response()->json($data);
        }
        
        return view('reports.table', [
            'data' => $data,
            'reportType' => $request->report_type,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
    }
    
    public function export(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:activities,users,status,performance',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'format' => 'required|in:csv,excel'
        ]);
        
        $dateFrom = Carbon::parse($request->date_from);
        $dateTo = Carbon::parse($request->date_to);
        
        switch ($request->report_type) {
            case 'activities':
                $data = Activity::with(['creator', 'assignee'])
                              ->whereBetween('created_at', [$dateFrom, $dateTo])
                              ->get();
                $filename = "activities_report_{$dateFrom->format('Y-m-d')}_to_{$dateTo->format('Y-m-d')}.csv";
                break;
            case 'users':
                $data = User::withCount([
                    'createdActivities' => function($q) use ($dateFrom, $dateTo) {
                        $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                    },
                    'assignedActivities' => function($q) use ($dateFrom, $dateTo) {
                        $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                    }
                ])->get();
                $filename = "users_report_{$dateFrom->format('Y-m-d')}_to_{$dateTo->format('Y-m-d')}.csv";
                break;
            default:
                return back()->with('error', 'Invalid report type for export.');
        }
        
        return $this->generateCSV($data, $filename, $request->report_type);
    }
    
    private function getActivitiesReport($dateFrom, $dateTo)
    {
        return Activity::with(['creator', 'assignee'])
                      ->whereBetween('created_at', [$dateFrom, $dateTo])
                      ->selectRaw('
                          category,
                          status,
                          priority,
                          COUNT(*) as count,
                          AVG(CASE WHEN status = "completed" THEN 
                              DATEDIFF(updated_at, created_at) 
                          END) as avg_completion_days
                      ')
                      ->groupBy('category', 'status', 'priority')
                      ->get();
    }
    
    private function getUsersReport($dateFrom, $dateTo)
    {
        return User::withCount([
            'createdActivities' => function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            },
            'assignedActivities' => function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            },
            'completedActivities' => function($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'completed')
                  ->whereBetween('updated_at', [$dateFrom, $dateTo]);
            }
        ])->get();
    }
    
    private function getStatusReport($dateFrom, $dateTo)
    {
        return Activity::whereBetween('created_at', [$dateFrom, $dateTo])
                      ->selectRaw('
                          status,
                          COUNT(*) as count,
                          ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM activities WHERE created_at BETWEEN ? AND ?), 2) as percentage
                      ', [$dateFrom, $dateTo])
                      ->groupBy('status')
                      ->get();
    }
    
    private function getPerformanceReport($dateFrom, $dateTo)
    {
        return DB::table('activities')
                 ->join('users as assignee', 'activities.assigned_to', '=', 'assignee.id')
                 ->whereBetween('activities.created_at', [$dateFrom, $dateTo])
                 ->selectRaw('
                     assignee.name as user_name,
                     COUNT(*) as total_assigned,
                     SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                     SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                     SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress,
                     SUM(CASE WHEN due_date < NOW() AND status != "completed" THEN 1 ELSE 0 END) as overdue,
                     ROUND(
                         SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2
                     ) as completion_rate
                 ')
                 ->groupBy('assignee.id', 'assignee.name')
                 ->get();
    }
    
    private function generateCSV($data, $filename, $reportType)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($data, $reportType) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers based on report type
            switch ($reportType) {
                case 'activities':
                    fputcsv($file, ['ID', 'Title', 'Category', 'Status', 'Priority', 'Created By', 'Assigned To', 'Created At', 'Due Date']);
                    foreach ($data as $activity) {
                        fputcsv($file, [
                            $activity->id,
                            $activity->title,
                            $activity->category,
                            $activity->status,
                            $activity->priority,
                            $activity->creator->name,
                            $activity->assignee ? $activity->assignee->name : 'Unassigned',
                            $activity->created_at->format('Y-m-d H:i:s'),
                            $activity->due_date ? Carbon::parse($activity->due_date)->format('Y-m-d') : ''
                        ]);
                    }
                    break;
                case 'users':
                    fputcsv($file, ['Name', 'Email', 'Created Activities', 'Assigned Activities', 'Completed Activities']);
                    foreach ($data as $user) {
                        fputcsv($file, [
                            $user->name,
                            $user->email,
                            $user->created_activities_count,
                            $user->assigned_activities_count,
                            $user->completed_activities_count
                        ]);
                    }
                    break;
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
