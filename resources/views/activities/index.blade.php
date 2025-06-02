@extends('layouts.app')

@php
    $title = 'Activities';
@endphp

@section('content')
<!-- Activities Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Activities</h1>
                <p class="text-muted mb-0">Manage and track all work activities</p>
            </div>
            <div>
                <a href="{{ route('activities.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>New Activity
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                    <form method="GET" action="{{ route('activities.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" name="priority" id="priority">
                                <option value="">All Priorities</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="assignee" class="form-label">Assignee</label>
                            <select class="form-select" name="assigned_to" id="assignee">
                                <option value="">All Assignees</option>
                                @foreach($users ?? [] as $user)
                                    <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" id="search" 
                                       placeholder="Search activities..." value="{{ request('search') }}">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>Apply Filters
                            </button>
                            <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Activities List -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        All Activities 
                        @if(isset($activities))
                            ({{ $activities->total() }} total)
                        @endif
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($activities) && $activities->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Assignee</th>
                                        <th>Created</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activities as $activity)
                                    <tr>
                                        <td>
                                            <div>
                                                <a href="{{ route('activities.show', $activity) }}" class="text-decoration-none fw-bold">
                                                    {{ $activity->title }}
                                                </a>                                                @if($activity->description)
                                                    <div class="small text-muted">
                                                        {{ strlen($activity->description) > 80 ? substr($activity->description, 0, 80) . '...' : $activity->description }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $activity->status_color }}">
                                                {{ ucfirst(str_replace('_', ' ', $activity->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $activity->priority_color }}">
                                                {{ ucfirst($activity->priority) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($activity->assignee)
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 30px; height: 30px;">
                                                        <span class="text-white small">
                                                            {{ substr($activity->assignee->name, 0, 1) }}
                                                        </span>
                                                    </div>
                                                    {{ $activity->assignee->name }}
                                                </div>
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="small">{{ $activity->created_at->format('M d, Y') }}</span>
                                            <div class="small text-muted">by {{ $activity->creator->name }}</div>
                                        </td>
                                        <td>
                                            @if($activity->due_date)
                                                <span class="small @if($activity->isOverdue()) text-danger @endif">
                                                    {{ $activity->due_date->format('M d, Y') }}
                                                </span>
                                                @if($activity->isOverdue())
                                                    <div class="small text-danger">
                                                        <i class="fas fa-exclamation-triangle"></i> Overdue
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-muted small">No due date</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="{{ route('activities.show', $activity) }}">
                                                        <i class="fas fa-eye me-2"></i>View Details
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="{{ route('activities.edit', $activity) }}">
                                                        <i class="fas fa-edit me-2"></i>Edit
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" 
                                                           onclick="confirmDelete({{ $activity->id }})">
                                                        <i class="fas fa-trash me-2"></i>Delete
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $activities->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No activities found</h5>
                            <p class="text-muted">Create your first activity to get started!</p>
                            <a href="{{ route('activities.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Create Activity
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this activity? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>        </div>
    </div>

<script>
function confirmDelete(activityId) {
    document.getElementById('deleteForm').action = '/activities/' + activityId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endsection
