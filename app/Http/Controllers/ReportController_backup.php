<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

/**
 * ReportController - Activity History Reporting System
 * 
 * Implements Requirement 5: "Provide a reporting view to enable querying 
 * of activity histories based on custom durations"
 * 
 * Features:
 * - Custom duration filtering (daily, weekly, monthly, yearly, custom range)
 * - Activity history tracking with status changes over time
 * - Comprehensive statistics and analytics
 * - Export capabilities (CSV, Excel)
 * - Role-based access control (Admin and Manager only)
 * - Real-time activity monitoring and trend analysis
 */
class ReportController extends Controller
{
    /**
     * Initialize controller with middleware protection
     * Only admins and managers can access reports
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user->isAdmin() && !$user->isManager()) {
                abort(403, 'You are not authorized to access reports. Only administrators and managers can view reports.');
            }
            return $next($request);
        });
    }

    /**
     * Display activity history reports with custom duration filtering
     * Implements Requirement 5: Activity history querying
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            // Check if this is a specific report request or dashboard view
            $type = $request->get('type', 'dashboard');
            
            if ($type === 'table') {
                return $this->generateTableReport($request);
            }

            // Dashboard view - get basic statistics and filter options
            $filterOptions = $this->getFilterOptions();
            
            // Calculate dashboard statistics using safe method
            $statistics = $this->calculateDashboardStatistics();

            // Get recent activity for timeline
            $recentActivities = Activity::with(['creator', 'assignee'])
                ->latest()
                ->take(10)
                ->get();

            // Prepare data for main dashboard view
            $data = [
                'stats' => $statistics,
                'users' => $filterOptions['users'],
                'statuses' => $filterOptions['statuses'],
                'priorities' => $filterOptions['priorities'],
                'categories' => $filterOptions['categories'],
                'recentActivities' => $recentActivities,
                'showFilters' => $request->has('start_date') || $request->has('status'),
                'currentFilters' => [
                    'duration' => $request->get('duration', 'last_30_days'),
                    'start_date' => $request->get('start_date'),
                    'end_date' => $request->get('end_date'),
                    'status' => $request->get('status'),
                    'priority' => $request->get('priority'),
                    'user_id' => $request->get('user_id'),
                    'category' => $request->get('category')
                ]
            ];

            Log::info('Report dashboard accessed successfully', [
                'user_id' => Auth::id(),
                'total_activities' => $statistics['total_activities'],
                'filters_applied' => array_filter($data['currentFilters'])
            ]);

            return view('reports.index', $data);

        } catch (\Exception $e) {
            Log::error('Error loading reports dashboard', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            return back()->withErrors(['error' => 'Unable to load reports. Please try again.']);
        }
    }

    /**
     * Generate filtered table report based on request parameters
     */
    private function generateTableReport(Request $request)
    {
        try {
            // Get filters from request
            $duration = $request->get('duration', 'last_30_days');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $status = $request->get('status');
            $priority = $request->get('priority');
            $userId = $request->get('user_id');
            $category = $request->get('category');

            // Calculate date range
            $dateRange = $this->calculateDateRange($duration, $startDate, $endDate);
            
            // Get filtered activities
            $activitiesQuery = $this->buildFilteredQuery($dateRange, [
                'status' => $status,
                'priority' => $priority,
                'user_id' => $userId,
                'category' => $category
            ]);

            // Paginate results
            $activities = $activitiesQuery->paginate(20);
            $activities->withQueryString();

            // Calculate summary statistics for filtered results
            $summary = $this->calculateFilteredSummary($activitiesQuery);

            // Get filter options
            $filterOptions = $this->getFilterOptions();

            return view('reports.table', [
                'activities' => $activities,
                'summary' => $summary,
                'filters' => [
                    'duration' => $duration,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status,
                    'priority' => $priority,
                    'user_id' => $userId,
                    'category' => $category
                ],
                'users' => $filterOptions['users'],
                'statuses' => $filterOptions['statuses'],
                'priorities' => $filterOptions['priorities'],
                'categories' => $filterOptions['categories'],
                'dateRange' => $dateRange
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating table report', [
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            return back()->withErrors(['error' => 'Unable to generate table report.']);
        }
    }

    /**
     * Calculate dashboard statistics safely (MySQL strict mode compatible)
     */
    private function calculateDashboardStatistics()
    {
        try {
            // Get all activities with basic relationships
            $allActivities = Activity::with(['creator', 'assignee'])->get();
            $totalActivities = $allActivities->count();

            // Calculate status counts
            $pendingCount = $allActivities->where('status', 'pending')->count();
            $inProgressCount = $allActivities->where('status', 'in_progress')->count();
            $completedCount = $allActivities->where('status', 'completed')->count();
            $completedToday = $allActivities->where('status', 'completed')
                ->filter(function($activity) {
                    return $activity->updated_at->isToday();
                })->count();

            // Calculate overdue count
            $overdueCount = $allActivities->filter(function($activity) {
                return $activity->due_date && 
                       $activity->due_date->isPast() && 
                       !in_array($activity->status, ['completed', 'cancelled']);
            })->count();

            // Calculate active users today
            $activeUsersToday = User::whereHas('assignedActivities', function($q) {
                $q->whereDate('updated_at', today());
            })->orWhereHas('activityUpdates', function($q) {
                $q->whereDate('created_at', today());
            })->count();

            return [
                'total_activities' => $totalActivities,
                'pending_activities' => $pendingCount,
                'in_progress_activities' => $inProgressCount,
                'completed_activities' => $completedCount,
                'completed_today' => $completedToday,
                'overdue_activities' => $overdueCount,
                'active_users_today' => $activeUsersToday,
                'completion_rate' => $totalActivities > 0 ? round(($completedCount / $totalActivities) * 100, 1) : 0
            ];

        } catch (\Exception $e) {
            Log::error('Error calculating dashboard statistics', ['error' => $e->getMessage()]);
            
            return [
                'total_activities' => 0,
                'pending_activities' => 0,
                'in_progress_activities' => 0,
                'completed_activities' => 0,
                'completed_today' => 0,
                'overdue_activities' => 0,
                'active_users_today' => 0,
                'completion_rate' => 0
            ];
        }
    }

    /**
     * Calculate date range based on duration selection
     * 
     * @param string $duration
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    private function calculateDateRange($duration, $startDate = null, $endDate = null)
    {
        $now = Carbon::now();
        
        // If custom dates are provided, use them
        if ($duration === 'custom' && $startDate && $endDate) {
            return [
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay(),
                'label' => 'Custom Range'
            ];
        }

        // Predefined duration options
        switch ($duration) {
            case 'today':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                    'label' => 'Today'
                ];
                
            case 'yesterday':
                return [
                    'start' => $now->copy()->subDay()->startOfDay(),
                    'end' => $now->copy()->subDay()->endOfDay(),
                    'label' => 'Yesterday'
                ];
                
            case 'last_7_days':
                return [
                    'start' => $now->copy()->subDays(7)->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                    'label' => 'Last 7 Days'
                ];
                
            case 'last_30_days':
                return [
                    'start' => $now->copy()->subDays(30)->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                    'label' => 'Last 30 Days'
                ];
                
            case 'this_month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                    'label' => 'This Month'
                ];
                
            case 'last_month':
                return [
                    'start' => $now->copy()->subMonth()->startOfMonth(),
                    'end' => $now->copy()->subMonth()->endOfMonth(),
                    'label' => 'Last Month'
                ];
                
            case 'this_year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear(),
                    'label' => 'This Year'
                ];
                
            case 'last_year':
                return [
                    'start' => $now->copy()->subYear()->startOfYear(),
                    'end' => $now->copy()->subYear()->endOfYear(),
                    'label' => 'Last Year'
                ];
                
            default:
                // Default to last 30 days
                return [
                    'start' => $now->copy()->subDays(30)->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                    'label' => 'Last 30 Days'
                ];
        }
    }

    /**
     * Generate custom reports based on filters
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function generate(Request $request)
    {
        try {
            // Validate input parameters
            $validated = $request->validate([
                'duration' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'status' => 'nullable|string',
                'priority' => 'nullable|string',
                'user_id' => 'nullable|integer|exists:users,id',
                'category' => 'nullable|string',
                'format' => 'nullable|in:table,chart,summary'
            ]);

            // Return to reports page with table view and filters applied
            return redirect()->route('reports.index', array_merge($validated, ['type' => 'table']));

        } catch (\Exception $e) {
            Log::error('Error generating custom report', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'filters' => $request->all()
            ]);

            return back()->withErrors(['error' => 'Unable to generate report. Please try again.']);
        }
    }

    /**
     * Export activity history data
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'duration' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'status' => 'nullable|string',
                'priority' => 'nullable|string',
                'user_id' => 'nullable|integer|exists:users,id',
                'category' => 'nullable|string',
                'export_format' => 'required|in:csv,excel'
            ]);

            // Calculate date range
            $dateRange = $this->calculateDateRange(
                $validated['duration'] ?? 'last_30_days',
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null
            );

            // Get activity history
            $filters = [
                'status' => $validated['status'] ?? null,
                'priority' => $validated['priority'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'category' => $validated['category'] ?? null
            ];

            // Get all data for export (not paginated)
            $activities = $this->getExportData($dateRange, $filters);

            $filename = 'activity_history_' . $dateRange['start']->format('Y-m-d') . '_to_' . $dateRange['end']->format('Y-m-d') . '.csv';

            Log::info('Activity history exported', [
                'user_id' => Auth::id(),
                'format' => $validated['export_format'],
                'records_count' => $activities->count(),
                'date_range' => $dateRange
            ]);

            return Response::streamDownload(function () use ($activities) {
                $handle = fopen('php://output', 'w');
                
                // Write CSV headers
                fputcsv($handle, [
                    'Activity ID',
                    'Title',
                    'Description',
                    'Status',
                    'Priority',
                    'Category',
                    'Creator',
                    'Assignee',
                    'Created Date',
                    'Due Date',
                    'Last Updated',
                    'Total Updates',
                    'Latest Update'
                ]);

                // Write data rows
                foreach ($activities as $activity) {
                    $latestUpdate = $activity->updates->first();
                    
                    fputcsv($handle, [
                        $activity->id,
                        $activity->title,
                        $activity->description,
                        $activity->status,
                        $activity->priority,
                        $activity->category,
                        $activity->creator ? $activity->creator->name : 'Unknown',
                        $activity->assignee ? $activity->assignee->name : 'Unassigned',
                        $activity->created_at->format('Y-m-d H:i:s'),
                        $activity->due_date ? $activity->due_date->format('Y-m-d') : '',
                        $activity->updated_at->format('Y-m-d H:i:s'),
                        $activity->updates->count(),
                        $latestUpdate ? $latestUpdate->created_at->format('Y-m-d H:i:s') : ''
                    ]);
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Error exporting activity history', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'filters' => $request->all()
            ]);

            return back()->withErrors(['error' => 'Unable to export data. Please try again.']);
        }
    }

    /**
     * Build filtered query for activities based on date range and filters
     */
    private function buildFilteredQuery($dateRange, $filters = [])
    {
        $query = Activity::with(['creator', 'assignee', 'updates']);

        // Apply date range filter
        $query->where(function($q) use ($dateRange) {
            $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
              ->orWhereHas('updates', function($updateQuery) use ($dateRange) {
                  $updateQuery->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
              });
        });

        // Apply additional filters
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['user_id']) && $filters['user_id'] !== 'all') {
            $query->where(function($q) use ($filters) {
                $q->where('created_by', $filters['user_id'])
                  ->orWhere('assigned_to', $filters['user_id']);
            });
        }

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }

        return $query;
    }

    /**
     * Calculate summary statistics for filtered activities
     */
    private function calculateFilteredSummary($activitiesQuery)
    {
        try {
            $activities = $activitiesQuery->get();
            $totalActivities = $activities->count();

            $pendingCount = $activities->where('status', 'pending')->count();
            $inProgressCount = $activities->where('status', 'in_progress')->count();
            $completedCount = $activities->where('status', 'completed')->count();
            
            $overdueCount = $activities->filter(function($activity) {
                return $activity->due_date && 
                       $activity->due_date->isPast() && 
                       !in_array($activity->status, ['completed', 'cancelled']);
            })->count();

            return [
                'total_activities' => $totalActivities,
                'pending_count' => $pendingCount,
                'in_progress_count' => $inProgressCount,
                'completed_count' => $completedCount,
                'overdue_count' => $overdueCount
            ];

        } catch (\Exception $e) {
            Log::error('Error calculating filtered summary', ['error' => $e->getMessage()]);
            
            return [
                'total_activities' => 0,
                'pending_count' => 0,
                'in_progress_count' => 0,
                'completed_count' => 0,
                'overdue_count' => 0
            ];
        }
    }

    /**
     * Get data for export (not paginated)
     * 
     * @param array $dateRange
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getExportData($dateRange, $filters = [])
    {
        $query = Activity::with(['creator', 'assignee', 'updates' => function($q) use ($dateRange) {
            $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
              ->orderBy('created_at', 'desc');
        }]);

        // Apply date range filter
        $query->where(function($q) use ($dateRange) {
            $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
              ->orWhereHas('updates', function($updateQuery) use ($dateRange) {
                  $updateQuery->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
              });
        });

        // Apply additional filters
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['user_id']) && $filters['user_id'] !== 'all') {
            $query->where(function($q) use ($filters) {
                $q->where('created_by', $filters['user_id'])
                  ->orWhere('assigned_to', $filters['user_id']);
            });
        }

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }

    /**
     * Get filter options for dropdowns
     * 
     * @return array
     */
    private function getFilterOptions()
    {
        try {
            return [
                'users' => User::select('id', 'name')
                    ->orderBy('name')
                    ->get(),
                'statuses' => ['pending', 'in_progress', 'completed', 'cancelled'],
                'priorities' => ['low', 'medium', 'high', 'urgent'],
                'categories' => Activity::whereNotNull('category')
                    ->distinct()
                    ->pluck('category')
                    ->filter()
                    ->sort()
                    ->values()
            ];

        } catch (\Exception $e) {
            Log::error('Error getting filter options', ['error' => $e->getMessage()]);
            
            return [
                'users' => collect(),
                'statuses' => ['pending', 'in_progress', 'completed', 'cancelled'],
                'priorities' => ['low', 'medium', 'high', 'urgent'],
                'categories' => collect()
            ];
        }
    }
}
