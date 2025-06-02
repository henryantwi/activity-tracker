<?php

// app/Http/Controllers/ActivityController.php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function index(Request $request)
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
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        
        // If not admin or manager, show only user's activities
        if (!Auth::user()->canSearchAllActivities()) {
            $query->where(function($q) {
                $q->where('assigned_to', Auth::id())
                  ->orWhere('created_by', Auth::id());
            });
        }
        
        $activities = $query->orderBy('created_at', 'desc')
                           ->paginate(15)
                           ->withQueryString();
        
        $users = User::select('id', 'name')->get();
        
        return view('activities.index', compact('activities', 'users'));
    }
    
    public function create()
    {
        $users = User::select('id', 'name')->get();
        return view('activities.create', compact('users'));
    }
    
    public function store(StoreActivityRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        
        $activity = Activity::create($data);
        
        return redirect()->route('activities.show', $activity)
                        ->with('success', 'Activity created successfully!');
    }
    
    public function show(Activity $activity)
    {
        // Check authorization explicitly
        $user = Auth::user();
        if (!$user) {
            abort(401, 'User not authenticated');
        }
        
        if (!$user->canSearchAllActivities() && $activity->created_by !== $user->id && $activity->assigned_to !== $user->id) {
            abort(403, 'You are not authorized to view this activity');
        }
        
        $activity->load(['creator', 'assignee', 'updates.user']);
        
        return view('activities.show', compact('activity'));
    }
    
    public function edit(Activity $activity)
    {
        \Log::info('Edit method called', [
            'activity_id' => $activity->id,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'user_is_admin' => \Illuminate\Support\Facades\Auth::user()->is_admin ?? 'NULL'
        ]);
        
        // Try a more explicit check
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            abort(401, 'User not authenticated');
        }
        
        // Check authorization explicitly
        if (!$user->canSearchAllActivities() && $activity->created_by !== $user->id && $activity->assigned_to !== $user->id) {
            abort(403, 'You are not authorized to edit this activity');
        }
        
        $users = User::select('id', 'name')->get();
        
        return view('activities.edit', compact('activity', 'users'));
    }
    
    public function update(UpdateActivityRequest $request, Activity $activity)
    {
        // Check authorization explicitly
        $user = Auth::user();
        if (!$user) {
            abort(401, 'User not authenticated');
        }
        
        if (!$user->isAdmin() && !$user->isManager() && $activity->created_by !== $user->id && $activity->assigned_to !== $user->id) {
            abort(403, 'You are not authorized to update this activity');
        }
        
        $oldData = $activity->toArray();
        
        $activity->update($request->validated());
        
        // Create update record if status changed
        if ($request->has('status') && $activity->wasChanged('status')) {
            $activity->updates()->create([
                'user_id' => Auth::id(),
                'status' => $activity->status,
                'remarks' => $request->get('update_remarks'),
                'previous_data' => $oldData,
                'new_data' => $activity->fresh()->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
        
        return redirect()->route('activities.show', $activity)
                        ->with('success', 'Activity updated successfully!');
    }
    
    public function destroy(Activity $activity)
    {
        // Check authorization explicitly
        $user = Auth::user();
        if (!$user) {
            abort(401, 'User not authenticated');
        }
        
        // Only admins, managers, or creators can delete activities
        if (!$user->isAdmin() && !$user->isManager() && $activity->created_by !== $user->id) {
            abort(403, 'You are not authorized to delete this activity');
        }
        
        $activity->delete();
        
        return redirect()->route('activities.index')
                        ->with('success', 'Activity deleted successfully!');
    }

    public function quickUpdate(Request $request, Activity $activity)
    {
        // Check authorization explicitly
        $user = Auth::user();
        if (!$user) {
            abort(401, 'User not authenticated');
        }
        
        if (!$user->isAdmin() && !$user->isManager() && $activity->created_by !== $user->id && $activity->assigned_to !== $user->id) {
            abort(403, 'You are not authorized to update this activity');
        }
        
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'remarks' => 'nullable|string|max:255'
        ]);
        
        $oldStatus = $activity->status;
        $activity->update(['status' => $request->status]);
        
        // Create update record
        $activity->updates()->create([
            'user_id' => Auth::id(),
            'status' => $request->status,
            'remarks' => $request->remarks,
            'previous_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $request->status],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        return redirect()->back()->with('success', 'Activity status updated successfully!');
    }
}