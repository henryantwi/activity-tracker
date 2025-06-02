<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Http\Requests\StoreActivityUpdateRequest;
use Illuminate\Http\Request;

class ActivityUpdateController extends Controller
{
    public function store(StoreActivityUpdateRequest $request, Activity $activity)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401, 'You must be logged in to perform this action');
        }
        
        // Check authorization: admin users can add updates to any activity, 
        // other users can only add updates to activities they created or are assigned to
        if (!$user->is_admin && $activity->created_by !== $user->id && $activity->assigned_to !== $user->id) {
            abort(403, 'You are not authorized to add updates to this activity');
        }
        
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
    
    public function index(Request $request, Activity $activity)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401, 'You must be logged in to perform this action');
        }
        
        // Check authorization: admin users can view updates for any activity, 
        // other users can only view updates for activities they created or are assigned to
        if (!$user->is_admin && $activity->created_by !== $user->id && $activity->assigned_to !== $user->id) {
            abort(403, 'You are not authorized to view updates for this activity');
        }
        
        $query = $activity->updates()->with('user');
        
        // Apply filters
        if ($request->filled('date')) {
            $query->whereDate('update_time', $request->date);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $updates = $query->orderBy('update_time', 'desc')
                        ->paginate(20)
                        ->withQueryString();
        
        if ($request->ajax()) {
            return response()->json($updates);
        }
        
        return view('activities.updates', compact('activity', 'updates'));
    }
}
