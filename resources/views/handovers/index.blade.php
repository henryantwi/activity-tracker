@extends('layouts.app')

@php
    $title = 'Daily Handovers';
@endphp

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Daily Handovers</h1>
                    <p class="text-muted mb-0">Manage activity handovers between team members</p>
                </div>
                <div>
                    <a href="{{ route('handovers.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create Handover
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('handovers.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="{{ request('date') }}">
                </div>
                <div class="col-md-3">
                    <label for="from_user" class="form-label">From User</label>
                    <select class="form-select" id="from_user" name="from_user">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('from_user') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="to_user" class="form-label">To User</label>
                    <select class="form-select" id="to_user" name="to_user">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('to_user') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="acknowledged" {{ request('status') === 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('handovers.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Handovers List -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Handovers</h6>
        </div>
        <div class="card-body">
            @if($handovers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Activities</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($handovers as $handover)
                                <tr>
                                    <td>{{ $handover->handover_time->format('M d, Y H:i') }}</td>
                                    <td>{{ $handover->fromUser->name }}</td>
                                    <td>{{ $handover->toUser->name }}</td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $handover->activities_count }} activities
                                        </span>
                                    </td>
                                    <td>
                                        @if($handover->is_acknowledged)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Acknowledged
                                            </span>
                                            <br>
                                            <small class="text-muted">{{ $handover->acknowledged_at->format('M d, H:i') }}</small>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('handovers.show', $handover) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('acknowledge', $handover)
                                                @if(!$handover->is_acknowledged)
                                                    <form method="POST" action="{{ route('handovers.acknowledge', $handover) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-success btn-sm" 
                                                                onclick="return confirm('Acknowledge this handover?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                            @can('delete', $handover)
                                                <form method="POST" action="{{ route('handovers.destroy', $handover) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                            onclick="return confirm('Are you sure you want to delete this handover?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $handovers->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No handovers found</p>
                    <a href="{{ route('handovers.create') }}" class="btn btn-primary">Create First Handover</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
