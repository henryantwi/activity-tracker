@extends('layouts.app')

@section('title', 'Activity Details')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">{{ $activity->title }}</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('activities.index') }}">Activities</a></li>
                            <li class="breadcrumb-item active">{{ $activity->title }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    @can('update', $activity)
                        <a href="{{ route('activities.edit', $activity) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Activity
                        </a>
                    @endcan
                    @can('delete', $activity)
                        <form action="{{ route('activities.destroy', $activity) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Are you sure you want to delete this activity?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Activity Details -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Activity Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Status:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-{{ $activity->status_color }}">
                                        <i class="fas fa-{{ $activity->status_icon }}"></i>
                                        {{ ucfirst($activity->status) }}
                                    </span>
                                </dd>
                                
                                <dt class="col-sm-4">Priority:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-{{ $activity->priority === 'high' ? 'danger' : ($activity->priority === 'medium' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($activity->priority) }}
                                    </span>
                                </dd>
                                
                                <dt class="col-sm-4">Category:</dt>
                                <dd class="col-sm-8">{{ ucfirst($activity->category) }}</dd>
                                
                                <dt class="col-sm-4">Created:</dt>
                                <dd class="col-sm-8">{{ $activity->created_at->format('M d, Y H:i') }}</dd>
                            </dl>
                        </div>
                        
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Created By:</dt>
                                <dd class="col-sm-8">{{ $activity->creator->name }}</dd>
                                
                                <dt class="col-sm-4">Assigned To:</dt>
                                <dd class="col-sm-8">
                                    {{ $activity->assignee ? $activity->assignee->name : 'Unassigned' }}
                                </dd>
                                
                                <dt class="col-sm-4">Due Date:</dt>
                                <dd class="col-sm-8">
                                    @if($activity->due_date)
                                        {{ \Carbon\Carbon::parse($activity->due_date)->format('M d, Y') }}
                                        @if(\Carbon\Carbon::parse($activity->due_date)->isPast() && $activity->status !== 'completed')
                                            <span class="badge bg-danger ms-1">Overdue</span>
                                        @endif
                                    @else
                                        No due date set
                                    @endif
                                </dd>
                                
                                <dt class="col-sm-4">Updated:</dt>
                                <dd class="col-sm-8">{{ $activity->updated_at->format('M d, Y H:i') }}</dd>
                            </dl>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Description</h6>
                    <p class="text-muted">{{ $activity->description ?: 'No description provided.' }}</p>
                </div>
            </div>

            <!-- Quick Status Update -->
            @can('update', $activity)
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Status Update</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('activities.quick-update', $activity) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <select name="status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    <option value="pending" {{ $activity->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in_progress" {{ $activity->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ $activity->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ $activity->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="remarks" class="form-control" placeholder="Optional remarks">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endcan
        </div>

        <!-- Activity Timeline -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Activity Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <!-- Creation -->
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Activity Created</h6>
                                <p class="timeline-description">
                                    Created by {{ $activity->creator->name }}
                                </p>
                                <small class="text-muted">{{ $activity->created_at->format('M d, Y H:i') }}</small>
                            </div>
                        </div>

                        <!-- Updates -->
                        @foreach($activity->updates->sortByDesc('created_at') as $update)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-{{ $update->status_color }}"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Status changed to {{ ucfirst($update->status) }}</h6>
                                @if($update->remarks)
                                <p class="timeline-description">{{ $update->remarks }}</p>
                                @endif
                                <small class="text-muted">
                                    by {{ $update->user->name }} on {{ $update->created_at->format('M d, Y H:i') }}
                                </small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 20px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
    padding-left: 25px;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: -5px;
    top: 5px;
    bottom: -15px;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item:last-child:before {
    display: none;
}

.timeline-marker {
    position: absolute;
    left: -10px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.timeline-description {
    font-size: 0.85rem;
    margin-bottom: 5px;
    color: #6c757d;
}
</style>
@endsection
