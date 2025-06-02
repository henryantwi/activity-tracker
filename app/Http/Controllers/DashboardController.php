<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Update user's last activity
        $user->updateLastActivity();
        
        // Get dashboard statistics
        $stats = $this->getDashboardStats($user);
        
        // Get today's activities
        $todayActivities = Activity::with(['creator', 'assignee', 'latestUpdate'])
            ->where(function($query) use ($user) {
                $query->where('assigned_to', $user->id)
                      ->orWhere('created_by', $user->id);
            })
            ->today()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get recent updates
        $recentUpdates = ActivityUpdate::with(['activity', 'user'])
            ->whereHas('activity', function($query) use ($user) {
                $query->where('assigned_to', $user->id)
                      ->orWhere('created_by', $user->id);
            })
            ->today()
            ->orderBy('update_time', 'desc')
            ->limit(15)
            ->get();
        
        // Get overdue activities
        $overdueActivities = Activity::with(['creator', 'assignee'])
            ->where('assigned_to', $user->id)
            ->overdue()
            ->orderBy('due_date')
            ->limit(5)
            ->get();
        
        // Get pending handovers
        $pendingHandovers = collect();
        if ($user->is_admin) {
            $pendingHandovers = \App\Models\DailyHandover::with(['fromUser', 'toUser'])
                ->unacknowledged()
                ->orderBy('handover_time', 'desc')
                ->limit(5)
                ->get();
        }
        
        return view('dashboard.index', compact(
            'stats',
            'todayActivities',
            'recentUpdates',
            'overdueActivities',
            'pendingHandovers'
        ));
    }
    
    private function getDashboardStats($user)
    {
        $baseQuery = Activity::query();
        
        if (!$user->is_admin) {
            $baseQuery->where(function($query) use ($user) {
                $query->where('assigned_to', $user->id)
                      ->orWhere('created_by', $user->id);
            });
        }
        
        return [
            'total_activities' => (clone $baseQuery)->count(),
            'pending_activities' => (clone $baseQuery)->pending()->count(),
            'in_progress_activities' => (clone $baseQuery)->inProgress()->count(),
            'completed_today' => (clone $baseQuery)->completed()
                ->whereDate('updated_at', today())->count(),
            'overdue_activities' => (clone $baseQuery)->overdue()->count(),
            'high_priority_pending' => (clone $baseQuery)->pending()
                ->byPriority('high')->count(),
            'today_updates' => ActivityUpdate::whereHas('activity', function($query) use ($user, $baseQuery) {
                if (!$user->is_admin) {
                    $query->where(function($q) use ($user) {
                        $q->where('assigned_to', $user->id)
                          ->orWhere('created_by', $user->id);
                    });
                }
            })->today()->count(),
            'active_users_today' => User::whereHas('activityUpdates', function($query) {
                $query->today();
            })->count(),
        ];
    }

    /**
     * Get dashboard stats for AJAX requests
     */
    public function getStats()
    {
        $user = auth()->user();
        $stats = $this->getDashboardStats($user);
        
        return response()->json($stats);
    }
    
    /**
     * Get today's activities for AJAX requests
     */
    public function getTodayActivities()
    {
        $user = auth()->user();
        
        $todayActivities = Activity::with(['creator', 'assignee', 'latestUpdate'])
            ->where(function($query) use ($user) {
                $query->where('assigned_to', $user->id)
                      ->orWhere('created_by', $user->id);
            })
            ->today()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        return response()->json($todayActivities);
    }
    
    /**
     * Get recent updates for AJAX requests
     */
    public function getRecentUpdates()
    {
        $user = auth()->user();
        
        $recentUpdates = ActivityUpdate::with(['activity', 'user'])
            ->whereHas('activity', function($query) use ($user) {
                $query->where('assigned_to', $user->id)
                      ->orWhere('created_by', $user->id);
            })
            ->today()
            ->orderBy('update_time', 'desc')
            ->limit(15)
            ->get();
            
        return response()->json($recentUpdates);
    }
}


// app/Http/Controllers/ActivityUpdateController.php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Http\Requests\StoreActivityUpdateRequest;
use Illuminate\Http\Request;

class ActivityUpdateController extends Controller
{
    public function store(StoreActivityUpdateRequest $request, Activity $activity)
    {
        $previousData = $activity->toArray();
        
        // Update activity status
        $activity->update(['status' => $request->status]);
        
        // Create update record
        $update = $activity->updates()->create([
            'user_id' => auth()->id(),
            'status' => $request->status,
            'remarks' => $request->remarks,
            'previous_data' => $previousData,
            'new_data' => $activity->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Activity status updated successfully!',
                'update' => $update->load('user'),
                'activity' => $activity->fresh()->load(['creator', 'assignee'])
            ]);
        }
        
        return redirect()->back()
                        ->with('success', 'Activity status updated successfully!');
    }
    
    public function index(Request $request)
    {
        $query = \App\Models\ActivityUpdate::with(['activity', 'user']);
        
        // Apply filters
        if ($request->filled('date')) {
            $query->whereDate('update_time', $request->date);
        } else {
            // Default to today
            $query->today();
        }
        
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // If not admin, show only user's updates
        if (!auth()->user()->is_admin) {
            $query->where(function($q) {
                $q->where('user_id', auth()->id())
                  ->orWhereHas('activity', function($subQ) {
                      $subQ->where('assigned_to', auth()->id())
                           ->orWhere('created_by', auth()->id());
                  });
            });
        }
        
        $updates = $query->orderBy('update_time', 'desc')
                        ->paginate(20)
                        ->withQueryString();
        
        $users = \App\Models\User::select('id', 'name')->get();
        
        return view('activity-updates.index', compact('updates', 'users'));
    }
}

// app/Http/Controllers/ReportController.php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use App\Http\Requests\ReportFilterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }
    
    public function daily(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));
        $selectedDate = \Carbon\Carbon::parse($date);
        
        // Get all activities created on this date
        $activitiesCreated = Activity::with(['creator', 'assignee'])
            ->whereDate('created_at', $selectedDate)
            ->orderBy('created_at')
            ->get();
        
        // Get all updates made on this date
        $updatesQuery = ActivityUpdate::with(['activity.creator', 'activity.assignee', 'user'])
            ->whereDate('update_time', $selectedDate);
        
        if (!auth()->user()->is_admin) {
            $updatesQuery->where(function($query) {
                $query->where('user_id', auth()->id())
                      ->orWhereHas('activity', function($subQuery) {
                          $subQuery->where('assigned_to', auth()->id())
                                   ->orWhere('created_by', auth()->id());
                      });
            });
        }
        
        $dailyUpdates = $updatesQuery->orderBy('update_time')->get();
        
        // Group updates by user for handover view
        $updatesByUser = $dailyUpdates->groupBy('user_id');
        
        // Get statistics for the day
        $stats = $this->getDailyStats($selectedDate);
        
        return view('reports.daily', compact(
            'activitiesCreated',
            'dailyUpdates',
            'updatesByUser',
            'stats',
            'selectedDate'
        ));
    }
    
    public function custom(ReportFilterRequest $request)
    {
        $filters = $request->validated();
        
        // Build query for activities
        $activitiesQuery = Activity::with(['creator', 'assignee', 'updates']);
        
        // Apply date range
        if (!empty($filters['start_date'])) {
            $activitiesQuery->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $activitiesQuery->whereDate('created_at', '<=', $filters['end_date']);
        }
        
        // Apply other filters
        if (!empty($filters['user_id'])) {
            $activitiesQuery->where(function($query) use ($filters) {
                $query->where('assigned_to', $filters['user_id'])
                      ->orWhere('created_by', $filters['user_id']);
            });
        }
        
        if (!empty($filters['status'])) {
            $activitiesQuery->where('status', $filters['status']);
        }
        
        if (!empty($filters['priority'])) {
            $activitiesQuery->where('priority', $filters['priority']);
        }
        
        if (!empty($filters['category'])) {
            $activitiesQuery->where('category', $filters['category']);
        }
        
        // If not admin, limit to user's activities
        if (!auth()->user()->is_admin) {
            $activitiesQuery->where(function($query) {
                $query->where('assigned_to', auth()->id())
                      ->orWhere('created_by', auth()->id());
            });
        }
        
        $activities = $activitiesQuery->orderBy('created_at', 'desc')->get();
        
        // Build query for updates in the same period
        $updatesQuery = ActivityUpdate::with(['activity', 'user']);
        
        if (!empty($filters['start_date'])) {
            $updatesQuery->whereDate('update_time', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $updatesQuery->whereDate('update_time', '<=', $filters['end_date']);
        }
        
        if (!empty($filters['user_id'])) {
            $updatesQuery->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['status'])) {
            $updatesQuery->where('status', $filters['status']);
        }
        
        // If not admin, limit to user's updates
        if (!auth()->user()->is_admin) {
            $updatesQuery->where(function($query) {
                $query->where('user_id', auth()->id())
                      ->orWhereHas('activity', function($subQuery) {
                          $subQuery->where('assigned_to', auth()->id())
                                   ->orWhere('created_by', auth()->id());
                      });
            });
        }
        
        $updates = $updatesQuery->orderBy('update_time', 'desc')->get();
        
        // Generate statistics
        $stats = $this->getCustomStats($activities, $updates, $filters);
        
        $users = User::select('id', 'name')->get();
        
        return view('reports.custom', compact(
            'activities',
            'updates',
            'stats',
            'filters',
            'users'
        ));
    }
    
    private function getDailyStats($date)
    {
        return [
            'activities_created' => Activity::whereDate('created_at', $date)->count(),
            'updates_made' => ActivityUpdate::whereDate('update_time', $date)->count(),
            'completed_activities' => ActivityUpdate::where('status', 'completed')
                ->whereDate('update_time', $date)->count(),
            'pending_activities' => Activity::whereDate('created_at', '<=', $date)
                ->where('status', 'pending')->count(),
            'active_users' => ActivityUpdate::whereDate('update_time', $date)
                ->distinct('user_id')->count(),
            'categories_worked' => Activity::whereDate('created_at', $date)
                ->distinct('category')->count(),
        ];
    }
    
    private function getCustomStats($activities, $updates, $filters)
    {
        return [
            'total_activities' => $activities->count(),
            'total_updates' => $updates->count(),
            'status_breakdown' => $activities->countBy('status'),
            'priority_breakdown' => $activities->countBy('priority'),
            'category_breakdown' => $activities->countBy('category'),
            'user_activity' => $updates->countBy('user.name'),
            'completion_rate' => $activities->count() > 0 ? 
                round(($activities->where('status', 'completed')->count() / $activities->count()) * 100, 2) : 0,
            'avg_updates_per_activity' => $activities->count() > 0 ? 
                round($updates->count() / $activities->count(), 2) : 0,
        ];
    }
    
    public function export(ReportFilterRequest $request)
    {
        $format = $request->get('export_format', 'csv');
        
        // This would implement export functionality
        // For now, return a simple response
        return response()->json([
            'message' => 'Export functionality will be implemented',
            'format' => $format
        ]);
    }
}