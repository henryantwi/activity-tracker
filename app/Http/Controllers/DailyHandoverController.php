<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\DailyHandover;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyHandoverController extends Controller
{
    public function index(Request $request)
    {
        $query = DailyHandover::with(['fromUser', 'toUser']);
        
        // Apply filters
        if ($request->filled('date')) {
            $query->whereDate('handover_time', $request->date);
        } else {
            // Default to today
            $query->whereDate('handover_time', today());
        }
        
        if ($request->filled('from_user')) {
            $query->where('from_user_id', $request->from_user);
        }
        
        if ($request->filled('to_user')) {
            $query->where('to_user_id', $request->to_user);
        }
        
        if ($request->filled('status')) {
            if ($request->status === 'acknowledged') {
                $query->whereNotNull('acknowledged_at');
            } else {
                $query->whereNull('acknowledged_at');
            }
        }
        
        // If not admin, show only user's handovers
        if (!auth()->user()->is_admin) {
            $query->where(function($q) {
                $q->where('from_user_id', auth()->id())
                  ->orWhere('to_user_id', auth()->id());
            });
        }
        
        $handovers = $query->orderBy('handover_time', 'desc')
                          ->paginate(15)
                          ->withQueryString();
        
        $users = User::select('id', 'name')->get();
        
        return view('handovers.index', compact('handovers', 'users'));
    }
    
    public function create()
    {
        $users = User::where('id', '!=', auth()->id())
                    ->select('id', 'name')
                    ->get();
        
        // Get pending activities assigned to current user
        $pendingActivities = Activity::with(['creator', 'assignee'])
            ->where('assigned_to', auth()->id())
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('priority', 'desc')
            ->orderBy('due_date')
            ->get();
        
        return view('handovers.create', compact('users', 'pendingActivities'));
    }
      public function store(Request $request)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id|different:from_user_id',
            'activity_ids' => 'array',
            'activity_ids.*' => 'exists:activities,id',
            'shift_summary' => 'required|string|max:1000',
            'pending_tasks' => 'nullable|string|max:1000',
            'important_notes' => 'nullable|string|max:1000',
        ]);
        
        // Get selected activities with their details
        $activities = Activity::with(['creator', 'assignee'])
            ->whereIn('id', $request->activity_ids ?? [])
            ->get();
        
        // Prepare activities data for JSON storage
        $activitiesData = $activities->map(function($activity) {
            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'status' => $activity->status,
                'priority' => $activity->priority,
                'due_date' => $activity->due_date?->format('Y-m-d'),
                'creator_name' => $activity->creator->name,
                'assignee_name' => $activity->assignee->name ?? 'Unassigned',
            ];
        });
        
        $handover = DailyHandover::create([
            'from_user_id' => auth()->id(),
            'to_user_id' => $request->to_user_id,
            'handover_date' => now()->format('Y-m-d'),
            'shift_summary' => $request->shift_summary,
            'pending_tasks' => $request->pending_tasks,
            'important_notes' => $request->important_notes,
            'activities_data' => $activitiesData,
            'handover_time' => now(),
            'is_acknowledged' => false,
        ]);
        
        // Optional: Update activity assignments
        if ($request->filled('transfer_activities') && $request->transfer_activities) {
            Activity::whereIn('id', $request->activity_ids ?? [])
                   ->update(['assigned_to' => $request->to_user_id]);
        }
        
        return redirect()->route('handovers.index')
                        ->with('success', 'Handover created successfully!');
    }
      public function show(DailyHandover $handover)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401, 'You must be logged in to perform this action');
        }
        
        // Check authorization: admin can view all handovers, users can view handovers they are involved in
        if (!$user->is_admin && $handover->from_user_id !== $user->id && $handover->to_user_id !== $user->id) {
            abort(403, 'You are not authorized to view this handover');
        }
        
        $handover->load(['fromUser', 'toUser']);
        
        // Get actual activities if they still exist
        $activityIds = collect($handover->activities_data)->pluck('id');
        $currentActivities = Activity::with(['creator', 'assignee'])
            ->whereIn('id', $activityIds)
            ->get()
            ->keyBy('id');
        
        return view('handovers.show', compact('handover', 'currentActivities'));
    }    public function acknowledge(DailyHandover $handover)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401, 'You must be logged in to perform this action');
        }
        
        // Check authorization: admin can acknowledge any handover, only the recipient can acknowledge the handover
        if (!$user->is_admin && ($handover->to_user_id !== $user->id || !is_null($handover->acknowledged_at))) {
            abort(403, 'You are not authorized to acknowledge this handover');
        }
        
        $handover->update([
            'is_acknowledged' => true,
            'acknowledged_at' => now(),
        ]);
        
        return redirect()->back()
                        ->with('success', 'Handover acknowledged successfully!');
    }
      public function destroy(DailyHandover $handover)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401, 'You must be logged in to perform this action');
        }
        
        // Check authorization: admin can delete all handovers, only the creator can delete handover (before acknowledgment)
        if (!$user->is_admin && ($handover->from_user_id !== $user->id || !is_null($handover->acknowledged_at))) {
            abort(403, 'You are not authorized to delete this handover');
        }
        
        $handover->delete();
        
        return redirect()->route('handovers.index')
                        ->with('success', 'Handover deleted successfully!');
    }
    
    /**
     * Generate daily handover report for a specific date
     */
    public function dailyReport(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);
        
        $handovers = DailyHandover::with(['fromUser', 'toUser'])
            ->whereDate('handover_time', $selectedDate)
            ->orderBy('handover_time')
            ->get();
        
        $stats = [
            'total_handovers' => $handovers->count(),
            'acknowledged' => $handovers->whereNotNull('acknowledged_at')->count(),
            'pending' => $handovers->whereNull('acknowledged_at')->count(),
            'total_activities' => $handovers->sum(function($handover) {
                return count($handover->activities_data);
            }),
        ];
        
        return view('handovers.daily-report', compact('handovers', 'stats', 'selectedDate'));
    }
}
