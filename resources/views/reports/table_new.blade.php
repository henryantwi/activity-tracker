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
                <h3 class="text-primary">{{ $summary['total_activities'] ?? 0 }}</h3>
                <small class="text-muted">Total Activities</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-warning">
            <div class="card-body">
                <h3 class="text-warning">{{ $summary['pending_count'] ?? 0 }}</h3>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-info">
            <div class="card-body">
                <h3 class="text-info">{{ $summary['in_progress_count'] ?? 0 }}</h3>
                <small class="text-muted">In Progress</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-success">
            <div class="card-body">
                <h3 class="text-success">{{ $summary['completed_count'] ?? 0 }}</h3>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-danger">
            <div class="card-body">
                <h3 class="text-danger">{{ $summary['overdue_count'] ?? 0 }}</h3>
                <small class="text-muted">Overdue</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-secondary">
            <div class="card-body">
                <h3 class="text-secondary">{{ number_format(($summary['completed_count'] ?? 0) / max($summary['total_activities'] ?? 1, 1) * 100, 1) }}%</h3>
                <small class="text-muted">Completion Rate</small>
            </div>
        </div>
    </div>
</div>

<!-- Applied Filters -->
@if(array_filter($filters ?? []))
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info">
            <strong>Applied Filters:</strong>
            @if(!empty($filters['status']))
                <span class="badge bg-primary me-1">Status: {{ ucfirst($filters['status']) }}</span>
            @endif
            @if(!empty($filters['priority']))
                <span class="badge bg-secondary me-1">Priority: {{ ucfirst($filters['priority']) }}</span>
            @endif
            @if(!empty($filters['user_id']))
                <span class="badge bg-success me-1">User: {{ $users->firstWhere('id', $filters['user_id'])->name ?? 'Unknown' }}</span>
            @endif
            @if(!empty($filters['category']))
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
                    <i class="fas fa-table me-2"></i>Activities ({{ $activities->total() ?? 0 }} total)
                </h6>
            </div>
            <div class="card-body">
                @if(isset($activities) && $activities->count() > 0)
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
            <form method="GET" action="{{ route('reports.export') }}">
                <!-- Pass current filters as hidden inputs -->
                @if(isset($filters))
                    @foreach($filters as $key => $value)
                        @if($value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                @endif
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="export_format" class="form-label">Export Format</label>
                        <select class="form-select" id="export_format" name="export_format" required>
                            <option value="csv">CSV (Comma Separated Values)</option>
                            <option value="excel">Excel Spreadsheet</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="export_data" class="form-label">Data to Export</label>
                        <select class="form-select" id="export_data" name="export_data" required>
                            <option value="filtered">Filtered Activities ({{ $activities->total() ?? 0 }} records)</option>
                            <option value="all">All Activities</option>
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
            let text = cols[j].textContent.trim();
            if (text.includes(',') || text.includes('"')) {
                text = '"' + text.replace(/"/g, '""') + '"';
            }
            row.push(text);
        }
        csv.push(row.join(','));
    }
    
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
