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
use Illuminate\Validation\ValidationException;

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
     * Display main reports dashboard with activity history overview
     * Implements Requirement 5: Activity history querying with custom durations
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */    public function index(Request $request)
    {
        try {
            // Check if this is a specific report type request
            if ($request->get('type') === 'daily') {
                // Set daily parameters and redirect to filtered report
                $request->merge([
                    'duration' => 'today',
                    'start_date' => now()->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d')
                ]);
                return $this->generateFilteredReport($request);
            }
            
            // Check if this is a filtered table request
            if ($request->get('type') === 'table' || $request->has('start_date') || $request->has('status')) {
                return $this->generateFilteredReport($request);
            }

            // Dashboard view with basic statistics
            $statistics = $this->calculateDashboardStatistics();
            $filterOptions = $this->getFilterOptions();
            
            // Get recent activity updates for timeline view
            $recentUpdates = ActivityUpdate::with(['activity', 'user'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            // Get activity completion trends for the last 30 days
            $completionTrends = $this->getCompletionTrends(30);

            $data = [
                'stats' => $statistics,
                'users' => $filterOptions['users'],
                'statuses' => $filterOptions['statuses'],
                'priorities' => $filterOptions['priorities'],
                'categories' => $filterOptions['categories'],
                'recentUpdates' => $recentUpdates,
                'completionTrends' => $completionTrends,
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

            Log::info('Reports dashboard accessed', [
                'user_id' => Auth::id(),
                'total_activities' => $statistics['total_activities']
            ]);

            return view('reports.index', $data);

        } catch (\Exception $e) {
            Log::error('Error loading reports dashboard', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Unable to load reports dashboard. Please try again.']);
        }
    }

    /**
     * Generate filtered activity history report
     * Core implementation of Requirement 5: Custom duration filtering
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function generateFilteredReport(Request $request)
    {
        try {
            // Validate request parameters
            $validated = $request->validate([
                'duration' => 'nullable|string|in:today,yesterday,last_7_days,last_30_days,last_90_days,this_month,last_month,this_year,custom',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
                'priority' => 'nullable|string|in:low,medium,high,urgent',
                'user_id' => 'nullable|integer|exists:users,id',
                'category' => 'nullable|string',
                'export_format' => 'nullable|string|in:csv,excel'
            ]);

            // Calculate date range based on duration
            $dateRange = $this->calculateDateRange(
                $validated['duration'] ?? 'last_30_days',
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null
            );

            // Build filtered query
            $query = Activity::with(['creator', 'assignee', 'updates.user'])
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

            // Apply additional filters
            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['priority'])) {
                $query->where('priority', $validated['priority']);
            }            if (!empty($validated['user_id'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('created_by', $validated['user_id'])
                      ->orWhere('assigned_to', $validated['user_id']);
                });
            }

            if (!empty($validated['category'])) {
                $query->where('category', $validated['category']);
            }

            // Handle export requests
            if (!empty($validated['export_format'])) {
                return $this->exportReport($query, $validated['export_format'], $validated);
            }

            // Get paginated results
            $activities = $query->orderBy('updated_at', 'desc')->paginate(20);
            $activities->withQueryString();

            // Calculate summary statistics for filtered results
            $summary = $this->calculateFilteredSummary($query);

            // Get filter options
            $filterOptions = $this->getFilterOptions();            $data = [
                'activities' => $activities,
                'summary' => $summary,
                'users' => $filterOptions['users'],
                'statuses' => $filterOptions['statuses'],
                'priorities' => $filterOptions['priorities'],
                'categories' => $filterOptions['categories'],
                'currentFilters' => $validated,
                'filters' => $validated,
                'dateRange' => $dateRange,
                'dateFrom' => $dateRange['start'],
                'dateTo' => $dateRange['end'],
                'type' => 'table'
            ];

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => view('reports.table', $data)->render(),
                    'summary' => $summary,
                    'total_results' => $activities->total()
                ]);
            }            // Return view for regular requests
            if (isset($data['type']) && $data['type'] === 'table') {
                return view('reports.table', $data);
            }
            
            return view('reports.index', $data);

        } catch (ValidationException $e) {
            Log::warning('Validation error in report generation', [
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            Log::error('Error generating filtered report', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error generating report. Please try again.'
                ], 500);
            }

            return back()->withErrors(['error' => 'Error generating report. Please try again.']);
        }
    }

    /**
     * Export report data based on request parameters
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        try {
            // Validate export parameters
            $validated = $request->validate([
                'export_format' => 'required|in:csv,excel',
                'export_data' => 'required|in:filtered,all',
                'duration' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'status' => 'nullable|string',
                'priority' => 'nullable|string',
                'user_id' => 'nullable|integer|exists:users,id',
                'category' => 'nullable|string'
            ]);

            // Build query based on export data selection
            if ($validated['export_data'] === 'all') {
                // Export all activities
                $query = Activity::with(['creator', 'assignee', 'updates']);
                $dateFrom = null;
                $dateTo = null;
            } else {
                // Export filtered data - reconstruct filters from request
                $dateRange = $this->calculateDateRange($request->input('duration'), $request->input('start_date'), $request->input('end_date'));
                $dateFrom = $dateRange['start'];
                $dateTo = $dateRange['end'];

                $query = Activity::with(['creator', 'assignee', 'updates'])
                    ->whereBetween('created_at', [$dateFrom, $dateTo]);

                // Apply additional filters
                if ($request->filled('status')) {
                    $query->where('status', $request->input('status'));
                }
                if ($request->filled('priority')) {
                    $query->where('priority', $request->input('priority'));
                }
                if ($request->filled('user_id')) {
                    $query->where(function($q) use ($request) {
                        $q->where('created_by', $request->input('user_id'))
                          ->orWhere('assigned_to', $request->input('user_id'));
                    });
                }
                if ($request->filled('category')) {
                    $query->where('category', $request->input('category'));
                }
            }

            // Use the private export method
            return $this->exportReport($query, $validated['export_format'], $validated);

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error in export method', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Error exporting data. Please try again.']);
        }
    }

    /**
     * Export filtered report data
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $format
     * @param array $filters
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    private function exportReport($query, $format, $filters)
    {
        try {
            $activities = $query->get();
            $filename = 'activity_report_' . Carbon::now()->format('Y-m-d_H-i-s') . '.' . $format;

            Log::info('Exporting report', [
                'user_id' => Auth::id(),
                'format' => $format,
                'total_records' => $activities->count(),
                'filters' => $filters
            ]);

            if ($format === 'csv') {
                return $this->exportToCsv($activities, $filename);
            } else {
                return $this->exportToExcel($activities, $filename);
            }

        } catch (\Exception $e) {
            Log::error('Error exporting report', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'format' => $format
            ]);

            return back()->withErrors(['error' => 'Failed to export report. Please try again.']);
        }
    }

    /**
     * Export activities to CSV format
     */
    private function exportToCsv($activities, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];

        return Response::stream(function () use ($activities) {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, [
                'ID', 'Title', 'Description', 'Status', 'Priority', 'Category',
                'Creator', 'Assignee', 'Due Date', 'Created At', 'Updated At',
                'Updates Count', 'Latest Update'
            ]);

            // CSV Data
            foreach ($activities as $activity) {
                $latestUpdate = $activity->updates->sortByDesc('created_at')->first();
                
                fputcsv($handle, [
                    $activity->id,
                    $activity->title,
                    $activity->description,
                    $activity->status,
                    $activity->priority,
                    $activity->category,
                    $activity->creator->name ?? 'N/A',
                    $activity->assignee->name ?? 'N/A',
                    $activity->due_date ? $activity->due_date->format('Y-m-d') : 'N/A',
                    $activity->created_at->format('Y-m-d H:i:s'),
                    $activity->updated_at->format('Y-m-d H:i:s'),
                    $activity->updates->count(),
                    $latestUpdate ? $latestUpdate->remarks : 'No updates'
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export activities to Excel format (using CSV for now)
     */
    private function exportToExcel($activities, $filename)
    {
        // For now, use CSV format with Excel MIME type
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];

        return $this->exportToCsv($activities, $filename);
    }

    /**
     * Calculate dashboard statistics
     */
    private function calculateDashboardStatistics()
    {
        try {
            $total = Activity::count();
            $pending = Activity::where('status', 'pending')->count();
            $inProgress = Activity::where('status', 'in_progress')->count();
            $completed = Activity::where('status', 'completed')->count();
            $completedToday = Activity::where('status', 'completed')
                ->whereDate('updated_at', today())
                ->count();

            // Calculate overdue activities
            $overdue = Activity::where('due_date', '<', now())
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            // Active users today (users who created updates today)
            $activeUsersToday = User::whereHas('activityUpdates', function ($query) {
                $query->whereDate('created_at', today());
            })->count();

            return [
                'total_activities' => $total,
                'pending_activities' => $pending,
                'in_progress_activities' => $inProgress,
                'completed_activities' => $completed,
                'completed_today' => $completedToday,
                'overdue_activities' => $overdue,
                'active_users_today' => $activeUsersToday,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0
            ];

        } catch (\Exception $e) {
            Log::error('Error calculating dashboard statistics', [
                'error' => $e->getMessage()
            ]);

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
     * Calculate summary statistics for filtered results
     */
    private function calculateFilteredSummary($query)
    {
        try {
            // Clone query to avoid modifying original
            $clonedQuery = clone $query;
            $activities = $clonedQuery->get();

            $total = $activities->count();            $statusCounts = $activities->countBy('status');
            $priorityCounts = $activities->countBy('priority');
            $categoryCounts = $activities->whereNotNull('category')->countBy('category');

            $completed = $statusCounts['completed'] ?? 0;
            $pending = $statusCounts['pending'] ?? 0;
            $inProgress = $statusCounts['in_progress'] ?? 0;
            $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

            // Calculate overdue activities
            $overdue = $activities->filter(function ($activity) {
                return $activity->due_date && 
                       $activity->due_date->isPast() && 
                       !in_array($activity->status, ['completed', 'cancelled']);
            })->count();

            // Calculate average updates per activity
            $totalUpdates = $activities->sum(function ($activity) {
                return $activity->updates->count();
            });
            $avgUpdates = $total > 0 ? round($totalUpdates / $total, 1) : 0;

            return [
                'total_activities' => $total,
                'pending_count' => $pending,
                'in_progress_count' => $inProgress,
                'completed_count' => $completed,
                'overdue_count' => $overdue,
                'status_distribution' => $statusCounts->toArray(),
                'priority_distribution' => $priorityCounts->toArray(),
                'category_distribution' => $categoryCounts->toArray(),
                'completion_rate' => $completionRate,
                'total_updates' => $totalUpdates,
                'avg_updates_per_activity' => $avgUpdates
            ];

        } catch (\Exception $e) {
            Log::error('Error calculating filtered summary', [
                'error' => $e->getMessage()
            ]);

            return [
                'total_activities' => 0,
                'status_distribution' => [],
                'priority_distribution' => [],
                'category_distribution' => [],
                'completion_rate' => 0,
                'total_updates' => 0,
                'avg_updates_per_activity' => 0
            ];
        }
    }

    /**
     * Get completion trends for specified number of days
     */
    private function getCompletionTrends($days = 30)
    {
        try {
            $startDate = Carbon::now()->subDays($days);
            $trends = [];

            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);
                $completed = Activity::where('status', 'completed')
                    ->whereDate('updated_at', $date)
                    ->count();

                $trends[] = [
                    'date' => $date->format('Y-m-d'),
                    'completed' => $completed
                ];
            }

            return $trends;

        } catch (\Exception $e) {
            Log::error('Error calculating completion trends', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Calculate date range based on duration selection
     * Core logic for custom duration filtering (Requirement 5)
     */
    private function calculateDateRange($duration, $startDate = null, $endDate = null)
    {
        $now = Carbon::now();

        switch ($duration) {
            case 'today':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];

            case 'yesterday':
                $yesterday = $now->copy()->subDay();
                return [
                    'start' => $yesterday->startOfDay(),
                    'end' => $yesterday->endOfDay()
                ];

            case 'last_7_days':
                return [
                    'start' => $now->copy()->subDays(7)->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];

            case 'last_30_days':
                return [
                    'start' => $now->copy()->subDays(30)->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];

            case 'last_90_days':
                return [
                    'start' => $now->copy()->subDays(90)->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];

            case 'this_month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];

            case 'last_month':
                $lastMonth = $now->copy()->subMonth();
                return [
                    'start' => $lastMonth->startOfMonth(),
                    'end' => $lastMonth->endOfMonth()
                ];

            case 'this_year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear()
                ];

            case 'custom':
                if ($startDate && $endDate) {
                    return [
                        'start' => Carbon::parse($startDate)->startOfDay(),
                        'end' => Carbon::parse($endDate)->endOfDay()
                    ];
                }
                // Fallback to last 30 days if custom dates not provided
                return [
                    'start' => $now->copy()->subDays(30)->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];

            default:
                // Default to last 30 days
                return [
                    'start' => $now->copy()->subDays(30)->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];
        }
    }

    /**
     * Get filter options for dropdowns
     */
    private function getFilterOptions()
    {
        try {
            return [
                'users' => User::select('id', 'name')
                    ->whereHas('activities')
                    ->orWhereHas('assignedActivities')
                    ->orderBy('name')
                    ->get(),
                'statuses' => ['pending', 'in_progress', 'completed', 'cancelled'],
                'priorities' => ['low', 'medium', 'high', 'urgent'],
                'categories' => Activity::whereNotNull('category')
                    ->distinct()
                    ->pluck('category')
                    ->sort()
                    ->values()
                    ->toArray()
            ];

        } catch (\Exception $e) {
            Log::error('Error getting filter options', [
                'error' => $e->getMessage()
            ]);

            return [
                'users' => collect(),
                'statuses' => ['pending', 'in_progress', 'completed', 'cancelled'],
                'priorities' => ['low', 'medium', 'high', 'urgent'],
                'categories' => []
            ];
        }
    }

    /**
     * Get activity history with updates for specific activity
     * Detailed view implementation for Requirement 5
     * 
     * @param Request $request
     * @param int $activityId
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function showActivityHistory(Request $request, $activityId)
    {
        try {
            $activity = Activity::with(['creator', 'assignee', 'updates.user'])
                ->findOrFail($activityId);

            // Get detailed update history
            $updates = ActivityUpdate::with('user')
                ->where('activity_id', $activityId)
                ->orderBy('created_at', 'desc')
                ->get();

            $data = [
                'activity' => $activity,
                'updates' => $updates,
                'timeline' => $this->buildActivityTimeline($activity, $updates)
            ];

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }

            return view('reports.activity-history', $data);

        } catch (\Exception $e) {
            Log::error('Error loading activity history', [
                'activity_id' => $activityId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activity not found or error loading history'
                ], 404);
            }

            return back()->withErrors(['error' => 'Activity not found or error loading history']);
        }
    }

    /**
     * Build activity timeline for detailed history view
     */
    private function buildActivityTimeline($activity, $updates)
    {
        $timeline = [];

        // Add activity creation event
        $timeline[] = [
            'type' => 'created',
            'timestamp' => $activity->created_at,
            'user' => $activity->creator,
            'description' => 'Activity created',
            'details' => [
                'title' => $activity->title,
                'status' => $activity->status,
                'priority' => $activity->priority
            ]
        ];

        // Add all updates
        foreach ($updates as $update) {
            $timeline[] = [
                'type' => 'update',
                'timestamp' => $update->created_at,
                'user' => $update->user,
                'description' => 'Status updated',
                'details' => [
                    'old_status' => $update->old_status,
                    'new_status' => $update->new_status,
                    'remarks' => $update->remarks
                ]
            ];
        }

        // Sort by timestamp
        usort($timeline, function ($a, $b) {
            return $b['timestamp']->timestamp - $a['timestamp']->timestamp;
        });

        return $timeline;
    }
}
// End of ReportController.php
// Test script to verify reports functionality