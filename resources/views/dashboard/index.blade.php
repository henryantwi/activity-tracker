@extends('layouts.app')

@php
    $title = 'Dashboard';
@endphp

@section('content')
<!-- Dashboard Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Dashboard</h1>
                <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}!</p>
            </div>
            <div>
                <a href="{{ route('activities.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>New Activity
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Activities
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['total_activities'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Completed Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['completed_today'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                In Progress
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['in_progress_activities'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Overdue
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['overdue_activities'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Cards -->
    @if($stats['overdue_activities'] > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                You have {{ $stats['overdue_activities'] }} overdue activities that need attention.
                <a href="{{ route('activities.index', ['filter' => 'overdue']) }}" class="alert-link">View overdue activities</a>
            </div>
        </div>
    </div>
    @endif

    @if($stats['high_priority_pending'] > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-fire me-2"></i>
                You have {{ $stats['high_priority_pending'] }} high priority pending activities.
                <a href="{{ route('activities.index', ['priority' => 'high', 'status' => 'pending']) }}" class="alert-link">View high priority activities</a>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <!-- Today's Activities -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Today's Activities</h6>
                    <a href="{{ route('activities.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    @if($todayActivities->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Assignee</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($todayActivities as $activity)
                                    <tr>
                                        <td>
                                            <a href="{{ route('activities.show', $activity) }}" class="text-decoration-none">
                                                {{ strlen($activity->title) > 50 ? substr($activity->title, 0, 50) . '...' : $activity->title }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $activity->status_color }}">
                                                {{ ucfirst($activity->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $activity->priority_color }}">
                                                {{ ucfirst($activity->priority) }}
                                            </span>
                                        </td>
                                        <td>{{ $activity->assignee->name ?? 'Unassigned' }}</td>
                                        <td>
                                            @if($activity->due_date)
                                                <span class="@if($activity->due_date < now()) text-danger @endif">
                                                    {{ $activity->due_date->format('M d, Y') }}
                                                </span>
                                            @else
                                                <span class="text-muted">No due date</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('activities.show', $activity) }}" class="btn btn-outline-info btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('activities.edit', $activity) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No activities for today</p>
                            <a href="{{ route('activities.create') }}" class="btn btn-primary">Create New Activity</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Updates & Quick Actions -->
        <div class="col-lg-4">
            <!-- Quick Status Update -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Status Update</h6>
                </div>
                <div class="card-body">
                    <form id="quickUpdateForm">
                        @csrf
                        <div class="mb-3">
                            <label for="activity_select" class="form-label">Select Activity</label>
                            <select class="form-select" id="activity_select" name="activity_id">
                                <option value="">Choose an activity...</option>
                                @foreach($todayActivities->where('status', '!=', 'completed') as $activity)
                                    <option value="{{ $activity->id }}">{{ strlen($activity->title) > 40 ? substr($activity->title, 0, 40) . '...' : $activity->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status_select" class="form-label">New Status</label>
                            <select class="form-select" id="status_select" name="status">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Optional remarks..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i>Update Status
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Updates -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Updates</h6>
                </div>
                <div class="card-body">
                    @if($recentUpdates->count() > 0)
                        <div class="timeline">
                            @foreach($recentUpdates as $update)
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-{{ $update->status_color }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-{{ $update->status_icon }} text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold">{{ $update->user->name }}</div>
                                        <div class="small text-muted">
                                            Updated "{{ strlen($activity->title) > 30 ? substr($activity->title, 0, 30) . '...' : $activity->title }}" to 
                                            <span class="badge bg-{{ $update->status_color }}">{{ ucfirst($update->status) }}</span>
                                        </div>
                                        @if($update->remarks)
                                            <div class="small text-muted fst-italic">{{ strlen($update->remarks) > 50 ? substr($update->remarks, 0, 50) . '...' : $update->remarks }}</div>
                                        @endif
                                        <div class="small text-muted">{{ $update->update_time->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-history fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No recent updates</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Activities (if any) -->
    @if($overdueActivities->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow border-left-danger">
                <div class="card-header py-3 bg-danger text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-exclamation-triangle me-2"></i>Overdue Activities
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Due Date</th>
                                    <th>Days Overdue</th>
                                    <th>Priority</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($overdueActivities as $activity)
                                <tr>
                                    <td>                                        <a href="{{ route('activities.show', $activity) }}" class="text-decoration-none">
                                            {{ strlen($activity->title) > 50 ? substr($activity->title, 0, 50) . '...' : $activity->title }}
                                        </a>
                                    </td>
                                    <td>{{ $activity->due_date->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-danger">
                                            {{ $activity->due_date->diffInDays(now()) }} days
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $activity->priority_color }}">
                                            {{ ucfirst($activity->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('activities.show', $activity) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif    <!-- Admin/Manager Section -->
    @if((auth()->user()->isAdmin() || auth()->user()->isManager()) && $pendingHandovers->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow border-left-info">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-exchange-alt me-2"></i>Pending Handovers
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Date</th>
                                    <th>Activities</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingHandovers as $handover)
                                <tr>
                                    <td>{{ $handover->fromUser->name }}</td>
                                    <td>{{ $handover->toUser->name }}</td>
                                    <td>{{ $handover->handover_time->format('M d, Y H:i') }}</td>
                                    <td>{{ $handover->activities_count ?? 0 }} activities</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i> Review
                                        </button>
                                    </td>
                                </tr>
                                @endforeach                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.text-xs {
    font-size: 0.7rem;
}

.timeline-item {
    position: relative;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 19px;
    top: 50px;
    width: 2px;
    height: calc(100% - 30px);
    background-color: #e3e6f0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick Update Form
    const quickUpdateForm = document.getElementById('quickUpdateForm');
    if (quickUpdateForm) {
        quickUpdateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const activityId = formData.get('activity_id');
            
            if (!activityId) {
                alert('Please select an activity');
                return;
            }
            
            fetch(`/activities/${activityId}/updates`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Activity status updated successfully!');
                    location.reload();
                } else {
                    alert('Error updating activity status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating activity status');
            });
        });
    }
    
    // Auto refresh stats every 5 minutes
    setInterval(function() {
        fetch('/dashboard/stats')
            .then(response => response.json())
            .then(data => {
                // Update stats cards if needed
                console.log('Stats refreshed');
            });
    }, 300000); // 5 minutes
});
</script>
@endsection
