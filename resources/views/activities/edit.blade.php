@extends('layouts.app')

@section('title', 'Edit Activity')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Edit Activity</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('activities.index') }}">Activities</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('activities.show', $activity) }}">{{ $activity->title }}</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('activities.show', $activity) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Activity
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Activity Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('activities.update', $activity) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Activity Title *</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title', $activity->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4">{{ old('description', $activity->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Category -->
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category *</label>
                                <select class="form-select @error('category') is-invalid @enderror" 
                                        id="category" name="category" required>                                    <option value="">Select Category</option>
                                    <option value="development" {{ old('category', $activity->category) === 'development' ? 'selected' : '' }}>Development</option>
                                    <option value="testing" {{ old('category', $activity->category) === 'testing' ? 'selected' : '' }}>Testing</option>
                                    <option value="documentation" {{ old('category', $activity->category) === 'documentation' ? 'selected' : '' }}>Documentation</option>
                                    <option value="meeting" {{ old('category', $activity->category) === 'meeting' ? 'selected' : '' }}>Meeting</option>
                                    <option value="research" {{ old('category', $activity->category) === 'research' ? 'selected' : '' }}>Research</option>
                                    <option value="maintenance" {{ old('category', $activity->category) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                    <option value="other" {{ old('category', $activity->category) === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Priority -->
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Priority *</label>
                                <select class="form-select @error('priority') is-invalid @enderror" 
                                        id="priority" name="priority" required>
                                    <option value="">Select Priority</option>
                                    <option value="low" {{ old('priority', $activity->priority) === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', $activity->priority) === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority', $activity->priority) === 'high' ? 'selected' : '' }}>High</option>
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="pending" {{ old('status', $activity->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in_progress" {{ old('status', $activity->status) === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ old('status', $activity->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ old('status', $activity->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Assigned To -->
                            <div class="col-md-6 mb-3">
                                <label for="assigned_to" class="form-label">Assign To</label>
                                <select class="form-select @error('assigned_to') is-invalid @enderror" 
                                        id="assigned_to" name="assigned_to">
                                    <option value="">Unassigned</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" 
                                                {{ old('assigned_to', $activity->assigned_to) == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigned_to')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Due Date -->
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                   id="due_date" name="due_date" 
                                   value="{{ old('due_date', $activity->due_date ? \Carbon\Carbon::parse($activity->due_date)->format('Y-m-d') : '') }}">
                            @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Update Remarks -->
                        <div class="mb-3">
                            <label for="update_remarks" class="form-label">Update Remarks</label>
                            <textarea class="form-control @error('update_remarks') is-invalid @enderror" 
                                      id="update_remarks" name="update_remarks" rows="3" 
                                      placeholder="Add remarks about this update (optional)">{{ old('update_remarks') }}</textarea>
                            @error('update_remarks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">These remarks will be added to the activity timeline if the status changes.</div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('activities.show', $activity) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Activity
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
