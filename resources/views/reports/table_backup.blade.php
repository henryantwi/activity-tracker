@extends('layouts.app')

@php
    $title = 'Report Results';
@endphp

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Report Results</h1>
                <p class="text-muted mb-0">
                    From {{ $dateFrom->format('M d, Y') }} to {{ $dateTo->format('M d, Y') }}
                </p>
            </div>
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-print me-1"></i>Print
                </button>
                <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Reports
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center border-primary">
            <div class="card-body">
                <h3 class="text-primary">{{ $summary['total_activities'] }}</h3>
                <small class="text-muted">Total Activities</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-warning">
            <div class="card-body">
                <h3 class="text-warning">{{ $summary['pending_count'] }}</h3>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-info">
            <div class="card-body">
                <h3 class="text-info">{{ $summary['in_progress_count'] }}</h3>
                <small class="text-muted">In Progress</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-success">
            <div class="card-body">
                <h3 class="text-success">{{ $summary['completed_count'] }}</h3>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-danger">
            <div class="card-body">
                <h3 class="text-danger">{{ $summary['overdue_count'] }}</h3>
                <small class="text-muted">Overdue</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-secondary">
            <div class="card-body">
                <h3 class="text-secondary">{{ number_format(($summary['completed_count'] / max($summary['total_activities'], 1)) * 100, 1) }}%</h3>
                <small class="text-muted">Completion Rate</small>
            </div>
        </div>
    </div>
</div>

<!-- Applied Filters -->
@if(array_filter($filters))
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info">
            <strong>Applied Filters:</strong>
            @if($filters['status'])
                <span class="badge bg-primary me-1">Status: {{ ucfirst($filters['status']) }}</span>
            @endif
            @if($filters['priority'])
                <span class="badge bg-secondary me-1">Priority: {{ ucfirst($filters['priority']) }}</span>
            @endif
            @if($filters['user_id'])
                <span class="badge bg-success me-1">User: {{ $activities->first()?->assignee?->name ?? 'Unknown' }}</span>
            @endif
            @if($filters['category'])
                <span class="badge bg-warning me-1">Category: {{ ucfirst($filters['category']) }}</span>
            @endif
        </div>
    </div>
</div>
@endif

<!-- Activities Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-table me-2"></i>Activities ({{ $activities->total() }} total)
                </h6>
            </div>
            <div class="card-body">
                @if($activities->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="reportTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Category</th>
                                    <th>Assigned To</th>
                                    <th>Created By</th>
                                    <th>Due Date</th>
                                    <th>Created</th>
                                    <th>Updates</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activities as $activity)
                                <tr>
                                    <td>
                                        <a href="{{ route('activities.show', $activity->id) }}" class="text-decoration-none">
                                            {{ $activity->title }}
                                        </a>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'in_progress' => 'info', 
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $statusColors[$activity->status] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $activity->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $priorityColors = [
                                                'low' => 'success',
                                                'medium' => 'warning',
                                                'high' => 'danger'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $priorityColors[$activity->priority] ?? 'secondary' }}">
                                            {{ ucfirst($activity->priority) }}
                                        </span>
                                    </td>
                                    <td>{{ ucfirst($activity->category) }}</td>
                                    <td>{{ $activity->assignee->name ?? 'Unassigned' }}</td>
                                    <td>{{ $activity->creator->name ?? 'Unknown' }}</td>
                                    <td>
                                        @if($activity->due_date)
                                            @php
                                                $isOverdue = $activity->due_date < now() && $activity->status !== 'completed';
                                            @endphp
                                            <span class="@if($isOverdue) text-danger fw-bold @endif">
                                                {{ $activity->due_date->format('M d, Y') }}
                                            </span>
                                            @if($isOverdue)
                                                <i class="fas fa-exclamation-triangle text-danger ms-1"></i>
                                            @endif
                                        @else
                                            <span class="text-muted">No due date</span>
                                        @endif
                                    </td>
                                    <td>{{ $activity->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $activity->updates->count() }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $activities->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No activities found</h5>
                        <p class="text-muted">Try adjusting your filters or date range.</p>
                        <a href="{{ route('reports.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Reports
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Report Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('reports.export') }}">
                @csrf
                <!-- Pass current filters as hidden inputs -->
                @foreach($filters as $key => $value)
                    @if($value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="format" class="form-label">Export Format</label>
                        <select class="form-select" id="format" name="format" required>
                            <option value="csv">CSV (Comma Separated Values)</option>
                            <option value="excel">Excel Spreadsheet</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="report_type" class="form-label">Data to Export</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="activities">Activities Only</option>
                            <option value="users">User Summary</option>
                            <option value="status">Status Summary</option>
                            <option value="performance">Performance Report</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $activity->status_color }}">
                                                    <i class="fas fa-{{ $activity->status_icon }} me-1"></i>
                                                    {{ ucfirst($activity->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $activity->priority_color }}">
                                                    <i class="fas fa-{{ $activity->priority_icon }} me-1"></i>
                                                    {{ ucfirst($activity->priority) }}
                                                </span>
                                            </td>
                                            <td>
                                                <i class="fas fa-{{ $activity->category_icon }} me-1"></i>
                                                {{ str_replace('_', ' ', ucfirst($activity->category)) }}
                                            </td>
                                            <td>{{ $activity->assignee->name ?? 'Unassigned' }}</td>
                                            <td>{{ $activity->creator->name }}</td>
                                            <td>
                                                @if($activity->due_date)
                                                    <span class="@if($activity->due_date < now()) text-danger @endif">
                                                        {{ $activity->due_date->format('Y-m-d') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">No due date</span>
                                                @endif
                                            </td>
                                            <td>{{ $activity->created_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        @elseif(isset($data['users']))
                            <!-- Users Report -->
                            <h6 class="text-muted mb-3">Users Performance Report</h6>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="reportTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>User</th>
                                            <th>Total Activities</th>
                                            <th>Completed</th>
                                            <th>In Progress</th>
                                            <th>Pending</th>
                                            <th>Completion Rate</th>
                                            <th>Last Activity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['users'] as $userData)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        <span class="text-white font-weight-bold">
                                                            {{ substr($userData['user']->name, 0, 2) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $userData['user']->name }}</div>
                                                        <small class="text-muted">{{ $userData['user']->email }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-info">{{ $userData['total'] }}</span></td>
                                            <td><span class="badge bg-success">{{ $userData['completed'] }}</span></td>
                                            <td><span class="badge bg-warning">{{ $userData['in_progress'] }}</span></td>
                                            <td><span class="badge bg-secondary">{{ $userData['pending'] }}</span></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: {{ $userData['completion_rate'] }}%">
                                                        {{ number_format($userData['completion_rate'], 1) }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($userData['user']->last_activity)
                                                    {{ $userData['user']->last_activity->diffForHumans() }}
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        @elseif(isset($data['status_summary']))
                            <!-- Status Report -->
                            <h6 class="text-muted mb-3">Status Summary Report</h6>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="reportTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Status</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                            <th>Visual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['status_summary'] as $status => $count)
                                        @php
                                            $percentage = $data['total'] > 0 ? ($count / $data['total']) * 100 : 0;
                                            $badgeColor = match($status) {
                                                'pending' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'secondary',
                                                default => 'primary'
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="badge bg-{{ $badgeColor }}">
                                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                                </span>
                                            </td>
                                            <td><strong>{{ $count }}</strong></td>
                                            <td>{{ number_format($percentage, 1) }}%</td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-{{ $badgeColor }}" role="progressbar" 
                                                         style="width: {{ $percentage }}%">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        @else
                            <!-- Generic Report -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="reportTable">
                                    <thead class="table-dark">
                                        <tr>
                                            @if(!empty($data) && is_array($data))
                                                @foreach(array_keys(reset($data)) as $header)
                                                    <th>{{ ucfirst(str_replace('_', ' ', $header)) }}</th>
                                                @endforeach
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($data) && is_array($data))
                                            @foreach($data as $row)
                                                <tr>
                                                    @foreach($row as $cell)
                                                        <td>{{ $cell }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <!-- Summary Statistics -->
                        @if(isset($data['total']) || isset($data['statistics']))
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Summary Statistics</h6>
                                        <div class="row">
                                            @if(isset($data['total']))
                                                <div class="col-md-3">
                                                    <div class="text-center">
                                                        <h4 class="text-primary">{{ $data['total'] }}</h4>
                                                        <small class="text-muted">Total Records</small>
                                                    </div>
                                                </div>
                                            @endif
                                            @if(isset($data['statistics']))
                                                @foreach($data['statistics'] as $key => $value)
                                                <div class="col-md-3">
                                                    <div class="text-center">
                                                        <h4 class="text-info">{{ $value }}</h4>
                                                        <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $key)) }}</small>
                                                    </div>
                                                </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Data Found</h5>
                            <p class="text-muted">No records match your criteria or the report is empty.</p>
                            <a href="{{ route('reports.index') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-1"></i>Generate New Report
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn-group, .card-header .btn-group {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 12px;
    }
    
    .badge {
        border: 1px solid #000 !important;
        color: #000 !important;
        background: transparent !important;
    }
}

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 12px;
}

.progress {
    border-radius: 10px;
}
</style>

<script>
function exportData(format) {
    const table = document.getElementById('reportTable');
    if (!table) {
        alert('No data to export');
        return;
    }
    
    if (format === 'csv') {
        exportToCSV(table);
    }
}

function exportToCSV(table) {
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            // Get text content and clean it
            let text = cols[j].textContent.trim();
            // Escape quotes and wrap in quotes if contains comma
            if (text.includes(',') || text.includes('"')) {
                text = '"' + text.replace(/"/g, '""') + '"';
            }
            row.push(text);
        }
        csv.push(row.join(','));
    }
    
    // Download CSV
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = 'report_' + new Date().toISOString().slice(0, 10) + '.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>
@endsection
