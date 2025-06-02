@extends('layouts.app')

@php
    $title = 'Reports';
@endphp

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Reports</h1>
                <p class="text-muted mb-0">Generate and view activity reports</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Report Cards -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow">
            <div class="card-body text-center">
                <i class="fas fa-calendar-day fa-3x text-primary mb-3"></i>
                <h5 class="card-title">Daily Report</h5>
                <p class="card-text">View today's activity summary and updates</p>
                <a href="{{ route('reports.index', ['type' => 'daily']) }}" class="btn btn-primary">
                    View Daily Report
                </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Custom Report</h5>
                    <p class="card-text">Generate custom reports with filters</p>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#customReportModal">
                        Generate Custom Report
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow">
                <div class="card-body text-center">
                    <i class="fas fa-download fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Export Data</h5>
                    <p class="card-text">Export activities and updates to CSV/Excel</p>
                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#exportModal">
                        Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Stats</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-primary">{{ $stats['total_activities'] ?? 0 }}</h4>
                                <small class="text-muted">Total Activities</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-warning">{{ $stats['pending_activities'] ?? 0 }}</h4>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-info">{{ $stats['in_progress_activities'] ?? 0 }}</h4>
                                <small class="text-muted">In Progress</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-success">{{ $stats['completed_today'] ?? 0 }}</h4>
                                <small class="text-muted">Completed Today</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-danger">{{ $stats['overdue_activities'] ?? 0 }}</h4>
                                <small class="text-muted">Overdue</small>
                            </div>
                        </div>                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-secondary">{{ $stats['active_users_today'] ?? 0 }}</h4>
                                <small class="text-muted">Active Users</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Custom Report Modal -->
<div class="modal fade" id="customReportModal" tabindex="-1" aria-labelledby="customReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customReportModalLabel">Generate Custom Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('reports.generate') }}">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="{{ now()->subDays(30)->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="{{ now()->format('Y-m-d') }}">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="report_status" class="form-label">Status</label>
                            <select class="form-select" id="report_status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="report_priority" class="form-label">Priority</label>
                            <select class="form-select" id="report_priority" name="priority">
                                <option value="">All Priorities</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="report_user" class="form-label">User</label>
                            <select class="form-select" id="report_user" name="user_id">
                                <option value="">All Users</option>
                                @foreach($users ?? [] as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="report_category" class="form-label">Category</label>
                            <select class="form-select" id="report_category" name="category">
                                <option value="">All Categories</option>
                                <option value="development">Development</option>
                                <option value="testing">Testing</option>
                                <option value="documentation">Documentation</option>
                                <option value="meeting">Meeting</option>
                                <option value="research">Research</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="GET" action="{{ route('reports.export') }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="export_format" class="form-label">Export Format</label>
                        <select class="form-select" id="export_format" name="export_format">
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                      <div class="mb-3">
                        <label for="export_data" class="form-label">Data to Export</label>
                        <select class="form-select" id="export_data" name="export_data">
                            <option value="all">All Activities</option>
                            <option value="filtered">Recent Activities (Last 30 Days)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="duration" class="form-label">Date Range (for filtered export)</label>
                        <select class="form-select" id="duration" name="duration">
                            <option value="today">Today</option>
                            <option value="last_7_days">Last 7 Days</option>
                            <option value="last_30_days" selected>Last 30 Days</option>
                            <option value="last_90_days">Last 90 Days</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_year">This Year</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Export</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
