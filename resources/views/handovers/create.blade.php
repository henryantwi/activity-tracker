@extends('layouts.app')

@php
    $title = 'Create Handover';
@endphp

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Create Handover</h1>
                    <p class="text-muted mb-0">Transfer activities to another team member</p>
                </div>
                <div>
                    <a href="{{ route('handovers.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Handovers
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Create Handover Form -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Handover Details</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('handovers.store') }}">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="to_user_id" class="form-label">Transfer To <span class="text-danger">*</span></label>
                                <select class="form-select @error('to_user_id') is-invalid @enderror" id="to_user_id" name="to_user_id" required>
                                    <option value="">Select User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('to_user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('to_user_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Transfer Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="transfer_activities" name="transfer_activities" value="1">
                                    <label class="form-check-label" for="transfer_activities">
                                        Also reassign selected activities to the user
                                    </label>
                                </div>
                            </div>                        </div>

                        <div class="mb-3">
                            <label for="shift_summary" class="form-label">Shift Summary <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('shift_summary') is-invalid @enderror" id="shift_summary" name="shift_summary" rows="3" 
                                      placeholder="Provide a brief summary of what was accomplished during your shift..." required>{{ old('shift_summary') }}</textarea>
                            @error('shift_summary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="pending_tasks" class="form-label">Pending Tasks</label>
                            <textarea class="form-control @error('pending_tasks') is-invalid @enderror" id="pending_tasks" name="pending_tasks" rows="3" 
                                      placeholder="List any tasks that need to be completed or followed up on...">{{ old('pending_tasks') }}</textarea>
                            @error('pending_tasks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="important_notes" class="form-label">Important Notes</label>
                            <textarea class="form-control @error('important_notes') is-invalid @enderror" id="important_notes" name="important_notes" rows="3" 
                                      placeholder="Any critical information, reminders, or special instructions...">{{ old('important_notes') }}</textarea>
                            @error('important_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Activities Selection -->
                        <div class="mb-4">
                            <label class="form-label">Select Activities to Handover</label>
                            @if($pendingActivities->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th width="50">
                                                    <input type="checkbox" id="select_all" class="form-check-input">
                                                </th>
                                                <th>Title</th>
                                                <th>Status</th>
                                                <th>Priority</th>
                                                <th>Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pendingActivities as $activity)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="activity_ids[]" value="{{ $activity->id }}" 
                                                               class="form-check-input activity-checkbox"
                                                               {{ in_array($activity->id, old('activity_ids', [])) ? 'checked' : '' }}>
                                                    </td>
                                                    <td>
                                                        <strong>{{ $activity->title }}</strong>
                                                        @if($activity->description)
                                                            <br><small class="text-muted">{{ strlen($activity->description) > 50 ? substr($activity->description, 0, 50) . '...' : $activity->description }}</small>
                                                        @endif
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
                                                        @if($activity->due_date)
                                                            {{ $activity->due_date->format('M d, Y') }}
                                                            @if($activity->due_date->isPast())
                                                                <span class="text-danger">
                                                                    <i class="fas fa-exclamation-triangle"></i>
                                                                </span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">No due date</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    You don't have any pending or in-progress activities to handover.
                                </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('handovers.index') }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-exchange-alt me-1"></i>Create Handover
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Handover Guidelines -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Handover Guidelines</h6>
                </div>                <div class="card-body">
                    <div class="small">
                        <h6><i class="fas fa-clipboard-list text-primary me-1"></i>Shift Summary:</h6>
                        <ul>
                            <li>Overall accomplishments during your shift</li>
                            <li>Key milestones reached</li>
                            <li>System status and health checks</li>
                            <li>Customer interactions handled</li>
                        </ul>
                        
                        <h6 class="mt-3"><i class="fas fa-tasks text-warning me-1"></i>Pending Tasks:</h6>
                        <ul>
                            <li>Work that needs immediate attention</li>
                            <li>Follow-up actions required</li>
                            <li>Scheduled meetings or calls</li>
                            <li>Deadlines approaching</li>
                        </ul>
                        
                        <h6 class="mt-3"><i class="fas fa-exclamation-triangle text-danger me-1"></i>Important Notes:</h6>
                        <ul>
                            <li>Critical system alerts or issues</li>
                            <li>Emergency contacts or procedures</li>
                            <li>Special client requirements</li>
                            <li>Scheduled maintenance or updates</li>
                        </ul>
                        
                        <div class="alert alert-info mt-3">
                            <small><strong>Tip:</strong> Be specific and actionable in your handover details to ensure smooth shift transitions.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select_all');
    const activityCheckboxes = document.querySelectorAll('.activity-checkbox');
    
    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        activityCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    // Update select all checkbox when individual checkboxes change
    activityCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.activity-checkbox:checked').length;
            selectAllCheckbox.checked = checkedCount === activityCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < activityCheckboxes.length;
        });
    });
});
</script>
@endsection
