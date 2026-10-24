@extends('hr.layouts.hr')

@section('title', 'Leave History - Skills Hut Ltd')

@push('styles')
<style>
    .page-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.35rem;
        font-weight: 700;
    }
    .page-subtitle { font-size: 0.85rem; }

    .section-card {
        border-radius: 14px;
        border: 1px solid #E2E0DD;
        background: #fff;
        padding: 16px;
    }

    .filter-bar {
        border-radius: 14px;
        border: 1px solid #E2E0DD;
        background: #fff;
        padding: 16px;
        margin-bottom: 20px;
    }
    .filter-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #4A4A4A;
        margin-bottom: 6px;
        display: block;
    }
    .form-select-custom, .form-control-custom {
        border: 1px solid #E2E0DD;
        border-radius: 8px;
        font-size: 0.85rem;
        padding: 8px 12px;
    }
    .btn-filter {
        background-color: #FF5E2B;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 8px 20px;
    }
    .btn-filter:hover { background-color: #E04B1A; color: #fff; }
    .btn-clear-filter {
        border: 1px solid #E2E0DD;
        color: #4A4A4A;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 8px 20px;
        text-decoration: none;
        display: inline-block;
    }
    .btn-clear-filter:hover { background-color: #FAF9F6; }

    .table th {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #7F7F7F;
        border-bottom: 1px solid #E2E0DD;
        padding: 10px;
        white-space: nowrap;
    }
    .table td {
        font-size: 0.82rem;
        color: #1A1A1A;
        padding: 12px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #F4F4F0;
    }

    .avatar-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #FFF0EB;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 700;
        color: #FF5E2B;
        flex-shrink: 0;
    }

    .badge-approved, .badge-pending, .badge-rejected {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        display: inline-block;
        white-space: nowrap;
    }
    .badge-approved { background: #ECFDF5; color: #059669; }
    .badge-pending  { background: #FFF7ED; color: #EA580C; }
    .badge-rejected { background: #FEF2F2; color: #DC2626; }

    .badge-requester {
        background: #F1F4FF;
        color: #2C67F2;
        font-size: 0.68rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 20px;
        display: inline-block;
        text-transform: uppercase;
    }

    @media (max-width: 575.98px) {
        .filter-bar .col-md-3, .filter-bar .col-md-2 { margin-bottom: 10px; }
    }
</style>
@endpush

@section('content')

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Leave History</h1>
        <p class="page-subtitle text-muted mb-0">Complete leave records for all employees and team leads</p>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('hr_admin.leave.history') }}">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <label class="filter-label">Search Employee</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-custom" placeholder="Employee name...">
            </div>

            <div class="col-6 col-md-2">
                <label class="filter-label">Status</label>
                <select name="status" class="form-select form-select-custom">
                    <option value="">All</option>
                    <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="filter-label">Leave Type</label>
                <select name="leave_type_id" class="form-select form-select-custom">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $type)
                        <option value="{{ $type->id }}" {{ request('leave_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="filter-label">Requester</label>
                <select name="requester_type" class="form-select form-select-custom">
                    <option value="">All</option>
                    <option value="employee" {{ request('requester_type') === 'employee' ? 'selected' : '' }}>Employee</option>
                    <option value="tl"       {{ request('requester_type') === 'tl'       ? 'selected' : '' }}>Team Lead</option>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label class="filter-label">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}"
                       class="form-control form-control-custom">
            </div>

            <div class="col-6 col-md-3">
                <label class="filter-label">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}"
                       class="form-control form-control-custom">
            </div>

            <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn-filter">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('hr_admin.leave.history') }}" class="btn-clear-filter">Clear</a>
                <a href="{{ route('hr_admin.leave.history.export.csv', request()->query()) }}" class="btn-filter">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                </a>
            </div>
            
        </div>
    </form>
</div>

{{-- Results Table --}}
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title" style="font-family:'Outfit',sans-serif;font-weight:700;font-size:0.95rem;">
            All Leave Records
        </div>
        <span class="text-muted" style="font-size:0.8rem;">{{ $leaves->total() }} total records</span>
    </div>

    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Requester</th>
                    <th>Type</th>
                    <th>Leave Type</th>
                    <th>Duration</th>
                    <th>Period</th>
                    <th>TL Status</th>
                    <th>Final Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaves as $leave)
                    @php
                        $name = $leave->requester_type === 'tl'
                            ? ($leave->requester->name ?? 'N/A')
                            : ($leave->employee->name ?? 'N/A');

                        $dept = $leave->requester_type === 'employee'
                            ? ($leave->employee->department->name ?? 'N/A')
                            : 'Team Lead';

                        $statusCls = match($leave->status) {
                            'approved' => 'badge-approved',
                            'rejected' => 'badge-rejected',
                            default    => 'badge-pending',
                        };

                        $tlStatusCls = match($leave->tl_status) {
                            'recommended'     => 'badge-approved',
                            'not_recommended' => 'badge-rejected',
                            default           => 'badge-pending',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle">{{ strtoupper(substr($name, 0, 2)) }}</div>
                                <div style="min-width:0;">
                                    <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;">
                                        {{ $name }}
                                    </div>
                                    <div style="font-size:0.72rem;color:#7F7F7F;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;">
                                        {{ $dept }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge-requester">{{ $leave->requester_type }}</span>
                        </td>
                        <td>{{ $leave->leaveType->name ?? 'N/A' }}</td>
                        <td style="color:#7F7F7F;">
                            {{ $leave->duration }} {{ $leave->duration == 1 ? 'Day' : 'Days' }}
                        </td>
                        <td style="white-space:nowrap;color:#7F7F7F;">
                            {{ \Carbon\Carbon::parse($leave->from_date)->format('M d') }} -
                            {{ \Carbon\Carbon::parse($leave->to_date)->format('M d, Y') }}
                        </td>
                        <td>
                            <span class="{{ $tlStatusCls }}">{{ ucfirst(str_replace('_', ' ', $leave->tl_status ?? 'pending')) }}</span>
                        </td>
                        <td>
                            <span class="{{ $statusCls }}">{{ ucfirst($leave->status) }}</span>
                        </td>
                        <td style="white-space:nowrap;color:#7F7F7F;font-size:0.78rem;">
                            {{ optional($leave->created_at)->format('M d, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:32px;color:#7F7F7F;">
                            <i class="bi bi-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px;color:#E2E0DD;"></i>
                            No leave records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($leaves->hasPages())
        <div class="mt-3 d-flex justify-content-center">
            {{ $leaves->links() }}
        </div>
    @endif
</div>

@endsection