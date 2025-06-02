@extends('layouts.app')

@php
    $title = 'Handover Details';
@endphp

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Handover Details</h1>
                    <p class="text-muted mb-0">From {{ $handover->fromUser->name }} to {{ $handover->toUser->name }}</p>
                </div>
                <div>
                    <a href="{{ route('handovers.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Handovers
                    </a>
                    @can('acknowledge', $handover)
                        @if(!$handover->is_acknowledged)
                            <form method="POST" action="{{ route('handovers.acknowledge', $handover) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success" onclick="return confirm('Acknowledge this handover?')">
                                    <i class="fas fa-check me-1"></i>Acknowledge Handover
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Handover Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Handover Information</h6>
                    @if($handover->is_acknowledged)
                        <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i>Acknowledged
                        </span>
                    @else
                        <span class="badge bg-warning">
                            <i class="fas fa-clock me-1"></i>Pending
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">From:</dt>
                                <dd class="col-sm-8">{{ $handover->fromUser->name }}</dd>
                                
                                <dt class="col-sm-4">To:</dt>
                                <dd class="col-sm-8">{{ $handover->toUser->name }}</dd>
                                
                                <dt class="col-sm-4">Handover Time:</dt>
                                <dd class="col-sm-8">{{ $handover->handover_time->format('M d, Y H:i') }}</dd>
                                
                                <dt class="col-sm-4">Activities Count:</dt>
                                <dd class="col-sm-8">{{ $handover->activities_count }} activities</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            @if($handover->is_acknowledged)
                                <dl class="row">
                                    <dt class="col-sm-4">Acknowledged At:</dt>
                                    <dd class="col-sm-8">{{ $handover->acknowledged_at->format('M d, Y H:i') }}</dd>
                                    
                                    <dt class="col-sm-4">Status:</dt>
                                    <dd class="col-sm-8">
                                        <span class="badge bg-success">Completed</span>
                                    </dd>
                                </dl>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    This handover is still pending acknowledgment.
                                </div>
                            @endif
                        </div>                    </div>
                    
                    <!-- Handover Details -->
                    @if($handover->shift_summary)
                        <hr>
                        <h6><i class="fas fa-clipboard-list me-1"></i>Shift Summary:</h6>
                        <div class="bg-light p-3 rounded mb-3">
                            {!! nl2br(e($handover->shift_summary)) !!}
                        </div>
                    @endif
                    
                    @if($handover->pending_tasks)
                        <h6><i class="fas fa-tasks me-1"></i>Pending Tasks:</h6>
                        <div class="bg-warning bg-opacity-10 p-3 rounded mb-3 border border-warning border-opacity-25">
                            {!! nl2br(e($handover->pending_tasks)) !!}
                        </div>
                    @endif
                    
                    @if($handover->important_notes)
                        <h6><i class="fas fa-exclamation-circle me-1"></i>Important Notes:</h6>
                        <div class="bg-danger bg-opacity-10 p-3 rounded mb-3 border border-danger border-opacity-25">
                            {!! nl2br(e($handover->important_notes)) !!}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Activities List -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Handed Over Activities</h6>
                </div>
                <div class="card-body">
                    @if(count($handover->activities_data) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Status at Handover</th>
                                        <th>Priority</th>
                                        <th>Due Date</th>
                                        <th>Current Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($handover->activities_data as $activityData)
                                        @php
                                            $currentActivity = $currentActivities->get($activityData['id']);
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $activityData['title'] }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    Created by: {{ $activityData['creator_name'] }} | 
                                                    Assigned to: {{ $activityData['assignee_name'] }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ ucfirst(str_replace('_', ' ', $activityData['status'])) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $activityData['priority'] === 'high' ? 'danger' : ($activityData['priority'] === 'medium' ? 'warning' : 'secondary') }}">
                                                    {{ ucfirst($activityData['priority']) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $activityData['due_date'] ? \Carbon\Carbon::parse($activityData['due_date'])->format('M d, Y') : 'No due date' }}
                                            </td>
                                            <td>
                                                @if($currentActivity)
                                                    <span class="badge bg-{{ $currentActivity->status_color }}">
                                                        {{ ucfirst(str_replace('_', ' ', $currentActivity->status)) }}
                                                    </span>
                                                    @if($currentActivity->status !== $activityData['status'])
                                                        <br><small class="text-success">
                                                            <i class="fas fa-arrow-up"></i> Status changed
                                                        </small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Activity not found</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($currentActivity)
                                                    <a href="{{ route('activities.show', $currentActivity) }}" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No activities were included in this handover</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Quick Actions</h6>
                </div>
                <div class="card-body">
                    @if(!$handover->is_acknowledged && auth()->id() === $handover->to_user_id)
                        <form method="POST" action="{{ route('handovers.acknowledge', $handover) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block w-100" onclick="return confirm('Acknowledge this handover?')">
                                <i class="fas fa-check me-1"></i>Acknowledge Handover
                            </button>
                        </form>
                    @endif
                    
                    <a href="{{ route('handovers.create') }}" class="btn btn-primary btn-block w-100 mb-2">
                        <i class="fas fa-plus me-1"></i>Create New Handover
                    </a>
                    
                    <a href="{{ route('handovers.index') }}" class="btn btn-outline-secondary btn-block w-100">
                        <i class="fas fa-list me-1"></i>All Handovers
                    </a>
                </div>
            </div>

            <!-- Activity Summary -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Activity Summary</h6>
                </div>
                <div class="card-body">
                    @php
                        $statusCounts = collect($handover->activities_data)->groupBy('status')->map->count();
                        $priorityCounts = collect($handover->activities_data)->groupBy('priority')->map->count();
                    @endphp
                    
                    <h6 class="small font-weight-bold">By Status:</h6>
                    @foreach($statusCounts as $status => $count)
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ ucfirst(str_replace('_', ' ', $status)) }}:</span>
                            <span class="badge bg-secondary">{{ $count }}</span>
                        </div>
                    @endforeach
                    
                    <hr>
                    
                    <h6 class="small font-weight-bold">By Priority:</h6>
                    @foreach($priorityCounts as $priority => $count)
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ ucfirst($priority) }}:</span>
                            <span class="badge bg-{{ $priority === 'high' ? 'danger' : ($priority === 'medium' ? 'warning' : 'secondary') }}">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
