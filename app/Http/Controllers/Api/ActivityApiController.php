<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with(['creator', 'assignee', 'latestUpdate']);
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        
        // Authorization: If not admin, show only user's activities
        if (!auth()->user()->is_admin) {
            $query->where(function($q) {
                $q->where('assigned_to', auth()->id())
                  ->orWhere('created_by', auth()->id());
            });
        }
        
        $perPage = $request->get('per_page', 15);
        $activities = $query->orderBy('created_at', 'desc')
                           ->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityRequest $request): JsonResponse
    {
        try {
            $activity = Activity::create($request->validated());
            $activity->load(['creator', 'assignee']);
            
            return response()->json([
                'success' => true,
                'message' => 'Activity created successfully',
                'data' => $activity
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity): JsonResponse
    {
        $this->authorize('view', $activity);
        
        $activity->load(['creator', 'assignee', 'updates.user']);
        
        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        try {
            $oldData = $activity->toArray();
            
            $activity->update($request->validated());
            
            // Create update record if status changed
            if ($request->has('status') && $activity->wasChanged('status')) {
                $activity->updates()->create([
                    'user_id' => auth()->id(),
                    'status' => $activity->status,
                    'remarks' => $request->get('update_remarks'),
                    'previous_data' => $oldData,
                    'new_data' => $activity->fresh()->toArray(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
            
            $activity->load(['creator', 'assignee', 'latestUpdate']);
            
            return response()->json([
                'success' => true,
                'message' => 'Activity updated successfully',
                'data' => $activity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity): JsonResponse
    {
        $this->authorize('delete', $activity);
        
        try {
            $activity->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Activity deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function stats(): JsonResponse
    {
        $user = auth()->user();
        
        $baseQuery = Activity::query();
        
        // If not admin, limit to user's activities
        if (!$user->is_admin) {
            $baseQuery->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }
        
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'overdue' => (clone $baseQuery)->where('due_date', '<', now())
                                          ->whereIn('status', ['pending', 'in_progress'])
                                          ->count(),
            'today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Update activity status
     */
    public function updateStatus(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);
        
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'remarks' => 'nullable|string|max:255'
        ]);
        
        try {
            $oldStatus = $activity->status;
            $activity->update(['status' => $request->status]);
            
            // Create update record
            $activity->updates()->create([
                'user_id' => auth()->id(),
                'status' => $request->status,
                'remarks' => $request->remarks,
                'previous_data' => ['status' => $oldStatus],
                'new_data' => ['status' => $request->status],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            $activity->load(['creator', 'assignee', 'latestUpdate']);
            
            return response()->json([
                'success' => true,
                'message' => 'Activity status updated successfully',
                'data' => $activity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update activity status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
