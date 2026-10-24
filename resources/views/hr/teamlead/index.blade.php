@extends('hr.layouts.hr')

@section('title', 'TL Assignment - Skills Hut Ltd')

@push('styles')
<style>
    .page-title { font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: #1A1A1A; }
    .page-subtitle { font-size: 0.85rem; color: #7F7F7F; }

    .filter-select { border: 1px solid #E2E0DD; border-radius: 8px; font-size: 0.85rem; padding: 9px 36px 9px 14px; background: #fff; color: #1A1A1A; appearance: none; cursor: pointer; min-width: 180px; }
    .filter-select:focus { outline: none; border-color: #FF5E2B; box-shadow: 0 0 0 3px rgba(255,94,43,0.1); }
    .filter-wrap { position: relative; }
    .filter-wrap .bi-chevron-down { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 0.7rem; color: #7F7F7F; pointer-events: none; }

    .kpi-card { background: #fff; border: 1px solid #E2E0DD; border-radius: 12px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; }
    .kpi-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #7F7F7F; margin-bottom: 6px; }
    .kpi-value { font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; color: #1A1A1A; }
    .kpi-value.orange { color: #FF5E2B; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 50%; background: #FAF9F6; border: 1px solid #E2E0DD; display: flex; align-items: center; justify-content: center; color: #7F7F7F; font-size: 1.1rem; }
    .kpi-icon.orange { background: #FFF3EE; border-color: rgba(255,94,43,0.2); color: #FF5E2B; }

    .tl-table-wrap { background: #fff; border: 1px solid #E2E0DD; border-radius: 12px; overflow: hidden; }
    .tl-table { width: 100%; margin: 0; }
    .tl-table thead th { font-size: 0.78rem; font-weight: 700; color: #1A1A1A; padding: 16px 20px; border-bottom: 1px solid #E2E0DD; background: #fff; }
    .tl-table tbody tr { border-bottom: 1px solid #F4F4F0; transition: background 0.15s; }
    .tl-table tbody tr:last-child { border-bottom: none; }
    .tl-table tbody tr:hover { background: #FAF9F6; }
    .tl-table td { padding: 18px 20px; vertical-align: middle; }

    .emp-avatar { width: 38px; height: 38px; border-radius: 50%; background: #F4F4F0; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; font-weight: 700; color: #4A4A4A; flex-shrink: 0; }
    .emp-avatar.tl-avatar { background: #FFF3EE; color: #FF5E2B; }
    .emp-name { font-size: 0.9rem; font-weight: 700; color: #1A1A1A; }
    .emp-id { font-size: 0.75rem; color: #B2ADA7; font-family: monospace; }
    .emp-designation { font-size: 0.85rem; color: #4A4A4A; }

    .badge-member { background: #F4F4F0; color: #4A4A4A; border-radius: 20px; font-size: 0.75rem; font-weight: 600; padding: 5px 12px; display: inline-flex; align-items: center; gap: 5px; }
    .badge-member::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #B2ADA7; display: inline-block; }
    .badge-tl { background: #FFF3EE; color: #FF5E2B; border-radius: 20px; font-size: 0.75rem; font-weight: 700; padding: 5px 12px; display: inline-flex; align-items: center; gap: 5px; }
    .badge-tl::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #FF5E2B; display: inline-block; }
    .badge-pending { background: #FEF3C7; color: #D97706; border-radius: 20px; font-size: 0.75rem; font-weight: 700; padding: 5px 12px; display: inline-flex; align-items: center; gap: 5px; }
    .badge-pending::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #D97706; display: inline-block; }

    .btn-assign { background: #FF5E2B; color: #fff; border: none; border-radius: 6px; font-size: 0.75rem; font-weight: 800; letter-spacing: 0.5px; padding: 8px 18px; transition: all 0.2s; cursor:pointer; }
    .btn-assign:hover { background: #E04B1A; color: #fff; }
    .btn-modify { background: #fff; color: #1A1A1A; border: 1px solid #E2E0DD; border-radius: 6px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px; padding: 8px 18px; transition: all 0.2s; cursor:pointer; }
    .btn-modify:hover { background: #FAF9F6; border-color: #FF5E2B; color: #FF5E2B; }
    .btn-cancel { background: #fff; color: #dc3545; border: 1px solid #dc3545; border-radius: 6px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px; padding: 8px 18px; transition: all 0.2s; cursor:pointer; }
    .btn-cancel:hover { background: #FEF2F2; }

    .pagination-wrap { padding: 16px 20px; border-top: 1px solid #E2E0DD; display: flex; justify-content: space-between; align-items: center; }
    .pagination-info { font-size: 0.78rem; color: #7F7F7F; font-family: monospace; }
    .pagination { margin-bottom: 0; }
    .page-link { color: #FF5E2B; border-color: #E2E0DD; }
    .page-item.active .page-link { background-color: #FF5E2B; border-color: #FF5E2B; }

    /* ── TL Assign Popup (same as admin panel) ─────────────────────────── */
    .tl-assign-wrap { position: relative; display: inline-block; }
    .dept-tl-trigger {
        display: flex; align-items: center; gap: 6px; background: #fff; border: 1px dashed #E2E0DD;
        border-radius: 8px; padding: 6px 10px; cursor: pointer; width: 100%; min-width: 160px; text-align: left;
    }
    .dept-tl-trigger.has-depts { border-style: solid; }
    .dept-tl-trigger.has-pending { border-color: #D97706; border-style: dashed; background: #FFFBEB; }
    .dept-tl-trigger.locked { cursor: not-allowed; opacity: 0.7; }
    .dept-tl-trigger:hover { border-color: #FF5E2B; }
    .tl-assign-panel {
        position: absolute; top: calc(100% + 6px); right: 0; width: 250px;
        background: #fff; border: 1px solid #E2E0DD; border-radius: 12px;
        box-shadow: 0 8px 28px rgba(0,0,0,0.14); z-index: 50; display: none; overflow: hidden;
    }
    .tl-assign-panel.open { display: block; }
    .tl-assign-panel-header {
        padding: 10px 14px; border-bottom: 1px solid #F4F4F0;
        font-size: 0.75rem; font-weight: 700; color: #1A1A1A; background: #FAFAFA;
    }
    .tl-assign-list { max-height: 220px; overflow-y: auto; padding: 6px 0; }
    .tl-assign-item { display: flex; align-items: flex-start; gap: 8px; padding: 8px 14px; cursor: pointer; transition: background 0.15s; }
    .tl-assign-item:hover { background: #FAF9F6; }
    .tl-assign-item input[type="checkbox"] { width: 15px; height: 15px; accent-color: #FF5E2B; cursor: pointer; margin-top: 2px; }
    .tl-assign-item label { font-size: 0.82rem; color: #1A1A1A; cursor: pointer; flex: 1; margin: 0; }
    .tl-assign-item.taken label { color: #B2ADA7; }
    .taken-note { display: block; font-size: 0.68rem; color: #D97706; font-weight: 600; margin-top: 2px; }
    .tl-assign-footer { padding: 10px 14px; border-top: 1px solid #F4F4F0; }
    .tl-assign-save {
        width: 100%; background: #FF5E2B; color: #fff; border: none; border-radius: 7px;
        font-size: 0.78rem; font-weight: 700; padding: 9px; cursor: pointer; transition: background 0.2s;
    }
    .tl-assign-save:hover { background: #E04B1A; }

    .dept-tl-chips { display: flex; flex-wrap: wrap; gap: 4px; max-width: 200px; }
    .dept-tl-mini-chip {
        background: #FFF3EE; color: #FF5E2B; border: 1px solid rgba(255,94,43,0.2);
        font-size: 0.7rem; font-weight: 700; padding: 3px 9px; border-radius: 10px; white-space: nowrap;
    }
    .dept-tl-mini-chip.pending {
        background: #FEF3C7; color: #D97706; border-color: rgba(217,119,6,0.25);
    }
    .dept-tl-empty { font-size: 0.78rem; color: #B2ADA7; font-style: italic; }
    .pending-hint { font-size: 0.68rem; color: #D97706; font-weight: 600; margin-top: 3px; }

    /* ============================================
       PAGE-SPECIFIC RESPONSIVE (Unique Classes)
       ============================================ */

    @media (max-width: 991.98px) {
        .kpi-card { padding: 16px 20px; }
        .kpi-value { font-size: 1.7rem; }
        .kpi-icon { width: 36px; height: 36px; font-size: 1rem; }
        .tl-table thead th { padding: 12px 16px; font-size: 0.75rem; }
        .tl-table td { padding: 14px 16px; }
        .emp-avatar { width: 34px; height: 34px; font-size: 0.75rem; }
        .emp-name { font-size: 0.85rem; }
        .emp-designation { font-size: 0.8rem; }
        .btn-assign, .btn-modify, .btn-cancel { font-size: 0.72rem; padding: 6px 14px; }
    }

    @media (max-width: 768px) {
        .page-title { font-size: 1.1rem; }
        .page-subtitle { font-size: 0.78rem; }

        .tl-assignment-header { flex-direction: column !important; gap: 16px !important; align-items: stretch !important; }
        .tl-assignment-header form { width: 100%; }
        .tl-assignment-header .filter-select { width: 100%; min-width: auto; }

        .row.g-3.mb-4 .col-12.col-md-4 { flex: 0 0 100% !important; max-width: 100% !important; }

        .kpi-card { padding: 14px 16px; }
        .kpi-label { font-size: 0.68rem; }
        .kpi-value { font-size: 1.5rem; }
        .kpi-icon { width: 32px; height: 32px; font-size: 0.95rem; }

        .tl-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .tl-table { min-width: 860px; }
        .tl-table thead th { padding: 10px 12px; font-size: 0.72rem; white-space: nowrap; }
        .tl-table td { padding: 12px 12px; }
        .emp-avatar { width: 32px; height: 32px; font-size: 0.72rem; }
        .emp-name { font-size: 0.82rem; }
        .emp-id { font-size: 0.7rem; }
        .emp-designation { font-size: 0.78rem; }
        .badge-member, .badge-tl, .badge-pending { font-size: 0.7rem; padding: 4px 10px; }
        .btn-assign, .btn-modify, .btn-cancel { font-size: 0.7rem; padding: 6px 12px; white-space: nowrap; }

        .tl-pagination-wrap { padding: 12px 16px !important; flex-direction: column !important; gap: 10px !important; align-items: stretch !important; }
        .tl-pagination-wrap .pagination-info { font-size: 0.72rem; text-align: center; }
        .tl-pagination-wrap .pagination { width: 100%; justify-content: center; }
    }

    @media (max-width: 576px) {
        .page-title { font-size: 1rem; }
        .page-subtitle { font-size: 0.75rem; }
        .kpi-card { padding: 12px 14px; }
        .kpi-value { font-size: 1.35rem; }
        .kpi-label { font-size: 0.65rem; }
        .tl-table { min-width: 780px; }
        .tl-table thead th { padding: 8px 10px; font-size: 0.7rem; }
        .tl-table td { padding: 10px 10px; }
        .emp-avatar { width: 30px; height: 30px; font-size: 0.7rem; }
        .emp-name { font-size: 0.78rem; }
        .btn-assign, .btn-modify, .btn-cancel { font-size: 0.68rem; padding: 5px 10px; }
        .filter-select { font-size: 0.8rem; padding: 8px 32px 8px 12px; }
    }

    @media (max-width: 400px) {
        .page-title { font-size: 0.95rem; }
        .kpi-value { font-size: 1.2rem; }
        .kpi-icon { width: 28px; height: 28px; font-size: 0.85rem; }
        .tl-table { min-width: 720px; }
        .emp-avatar { width: 28px; height: 28px; font-size: 0.68rem; }
        .emp-name { font-size: 0.75rem; }
        .btn-assign, .btn-modify, .btn-cancel { font-size: 0.65rem; padding: 5px 8px; }
    }
</style>
@endpush

@section('content')

@php
    $departments = \App\Models\Department::orderBy('name')->get();

    $employeesQuery = \App\Models\Employee::with('department')
        ->when(request('department_id'), function ($query) {
            $query->where('department_id', request('department_id'));
        });

    $totalMembers  = (clone $employeesQuery)->count();
    $assignedLeads = (clone $employeesQuery)->where('role', 'team_lead')->count();

    $employees = $employeesQuery->latest()->paginate(8)->withQueryString();

    // Pending TL requests raised by HR, awaiting super-admin approval
    $pendingRequests = \App\Models\Notification::where('type', 'tl_assignment_request')
        ->where('status', 'pending')
        ->pluck('employee_id')
        ->toArray();

    $pendingNotifications = \App\Models\Notification::where('type', 'tl_assignment_request')
        ->where('status', 'pending')
        ->get()
        ->keyBy('employee_id');
@endphp

    {{-- Success / Info Alert --}}
    @if(session('success'))
        <div class="alert alert-success py-2 px-3 mb-3 rounded-3" style="font-size:0.88rem;">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-warning py-2 px-3 mb-3 rounded-3" style="font-size:0.88rem;">
            <i class="bi bi-info-circle me-1"></i> {{ session('info') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger py-2 px-3 mb-3 rounded-3" style="font-size:0.88rem;">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="tl-assignment-header d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <h1 class="page-title mb-1">Team Lead Assignment</h1>
            <p class="page-subtitle mb-0">Request leadership changes — subject to admin approval.</p>
        </div>

        <form method="GET" action="{{ url()->current() }}">
            <div style="font-size:0.75rem; font-weight:700; color:#7F7F7F; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">
                Filter by Department
            </div>
            <div class="filter-wrap">
                <select name="department_id" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down"></i>
            </div>
        </form>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="kpi-card">
                <div><div class="kpi-label">Total Members</div><div class="kpi-value">{{ $totalMembers }}</div></div>
                <div class="kpi-icon"><i class="bi bi-people"></i></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card">
                <div><div class="kpi-label">Pending Requests</div><div class="kpi-value" style="color:#D97706;">{{ count($pendingRequests) }}</div></div>
                <div class="kpi-icon" style="background:#FFFBEB; border-color:rgba(217,119,6,0.2); color:#D97706;"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card">
                <div><div class="kpi-label">Assigned Leads</div><div class="kpi-value orange">{{ $assignedLeads }}</div></div>
                <div class="kpi-icon orange"><i class="bi bi-shield-check"></i></div>
            </div>
        </div>
    </div>

    {{-- Employee Table --}}
    <div class="tl-table-wrap">
        <div class="table-responsive">
        <table class="tl-table">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Designation</th>
                    <th>Department</th>
                    <th>Current Role</th>
                    <th>Status</th>
                    <th>Dept(s) as TL</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                @php
                    $isPending = in_array($employee->id, $pendingRequests);
                    $pendingNotif = $pendingNotifications[$employee->id] ?? null;
                    $pendingData  = $pendingNotif ? json_decode($pendingNotif->data ?? '{}', true) : null;
                 $pendingDeptIds = $pendingNotif
    ? (json_decode($pendingNotif->department_ids ?? '[]', true) ?: [])
    : [];

$pendingAction = $pendingNotif->action ?? 'assign';

                    // Departments actually assigned right now (approved state)
                    $assignedDeptIds = \App\Models\Department::where('hod_id', $employee->id)
                        ->pluck('id')->toArray();
                    $assignedDeptNames = \App\Models\Department::where('hod_id', $employee->id)
                        ->pluck('name')->toArray();

                    // Departments owned by someone ELSE right now — block picking these
                    $takenByOthers = \App\Models\Department::whereNotNull('hod_id')
                        ->where('hod_id', '!=', $employee->id)
                        ->with('hod:id,name')
                        ->get()
                        ->pluck('hod.name', 'id');
                @endphp
                    <tr data-employee-id="{{ $employee->id }}"
                        data-employee-role="{{ $employee->role }}"
                        data-is-pending="{{ $isPending ? '1' : '0' }}">
                        {{-- Employee --}}
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="emp-avatar {{ $employee->role === 'team_lead' ? 'tl-avatar' : '' }}">
                                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="emp-name">{{ $employee->name }}</div>
                                    <div class="emp-id">{{ $employee->employee_id ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>

                        <td><span class="emp-designation">{{ $employee->designation ?? 'N/A' }}</span></td>
                        <td><span class="emp-designation">{{ $employee->department->name ?? 'No Department' }}</span></td>

                        {{-- Current Role --}}
                        <td>
                            @if($employee->role === 'team_lead')
                                <span class="badge-tl">Team Lead</span>
                            @else
                                <span class="badge-member">Member</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td>
                            @if($isPending)
                                <span class="badge-pending">Pending</span>
                            @elseif($employee->role === 'team_lead')
                                <span style="font-size:0.75rem; color:#059669; font-weight:600;">Approved</span>
                            @else
                                <span style="font-size:0.75rem; color:#B2ADA7;">—</span>
                            @endif
                        </td>

                        {{-- Dept(s) as TL --}}
                        <td>
                            <div class="tl-assign-wrap">
                                @if($isPending)
                                    {{-- Locked while a request is awaiting approval --}}
                                    <div class="dept-tl-trigger locked has-depts" style="cursor:not-allowed;">
                                        <div class="dept-tl-chips">
                                            @if($pendingAction === 'remove')
                                                <span class="dept-tl-mini-chip pending">Removal requested</span>
                                            @elseif(!empty($pendingDeptIds))
                                                @foreach($pendingDeptIds as $pid)
                                                    <span class="dept-tl-mini-chip pending">{{ $departments->firstWhere('id', $pid)->name ?? 'Unknown' }}</span>
                                                @endforeach
                                            @else
                                                <span class="dept-tl-empty">Awaiting approval</span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <button type="button"
                                            class="dept-tl-trigger {{ count($assignedDeptIds) ? 'has-depts' : '' }}"
                                            id="trigger-{{ $employee->id }}"
                                            onclick="toggleTlAssignPanel({{ $employee->id }})">
                                        <div class="dept-tl-chips" id="deptChips-{{ $employee->id }}">
                                            @if(count($assignedDeptIds) > 0)
                                                @foreach($assignedDeptNames as $dn)
                                                    <span class="dept-tl-mini-chip">{{ $dn }}</span>
                                                @endforeach
                                            @else
                                                <span class="dept-tl-empty">Click to select</span>
                                            @endif
                                        </div>
                                        <i class="bi bi-chevron-down" style="font-size:0.6rem; margin-left:auto;"></i>
                                    </button>
                                    <div class="pending-hint" id="pendingHint-{{ $employee->id }}" style="display:none;">
                                        <i class="bi bi-info-circle"></i> Selected — click Actions to request
                                    </div>

                                    <div class="tl-assign-panel" id="tlAssignPanel-{{ $employee->id }}">
                                        <div class="tl-assign-panel-header">Select department(s)</div>
                                        <div class="tl-assign-list">
                                            @foreach($departments as $department)
                                            @php $isTaken = isset($takenByOthers[$department->id]); @endphp
                                            <div class="tl-assign-item {{ $isTaken ? 'taken' : '' }}">
                                                <input type="checkbox"
                                                       data-emp-id="{{ $employee->id }}"
                                                       id="tlDept_{{ $employee->id }}_{{ $department->id }}"
                                                       value="{{ $department->id }}"
                                                       data-dept-name="{{ $department->name }}"
                                                       data-taken="{{ $isTaken ? '1' : '0' }}"
                                                       {{ in_array($department->id, $assignedDeptIds) ? 'checked' : '' }}
                                                       {{ $isTaken ? 'disabled' : '' }}
                                                       onchange="handleDeptCheckboxChange(this, {{ $employee->id }})">
                                                <label for="tlDept_{{ $employee->id }}_{{ $department->id }}">
                                                    {{ $department->name }}
                                                    @if($isTaken)
                                                        <span class="taken-note">Already TL: {{ $takenByOthers[$department->id] }}</span>
                                                    @endif
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                        <div class="tl-assign-footer">
                                            <button type="button" class="tl-assign-save" onclick="saveDeptSelection({{ $employee->id }})">
                                                Save
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td>
                            @if($isPending)
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge-pending">AWAITING APPROVAL</span>
                                    <form action="{{ route('hr_admin.tl-request.cancel', $pendingNotif->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-cancel">CANCEL</button>
                                    </form>
                                </div>
                            @elseif($employee->role === 'team_lead')
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn-modify" onclick="requestTlChange({{ $employee->id }}, 'modify')">
                                        MODIFY
                                    </button>
                                    <button type="button" class="btn-cancel" onclick="requestRemoveTl({{ $employee->id }})">
                                        REQUEST REMOVAL
                                    </button>
                                </div>
                            @else
                                <button type="button" class="btn-assign" onclick="requestTlChange({{ $employee->id }}, 'assign')">
                                    REQUEST TL ASSIGNMENT
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="tl-pagination-wrap pagination-wrap">
            <span class="pagination-info">
                Showing {{ $employees->firstItem() ?? 0 }}-{{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }} employees
            </span>
            <div>{{ $employees->links() }}</div>
        </div>
    </div>

@push('scripts')
<script>
const CSRF_TOKEN = '{{ csrf_token() }}';

const REQUEST_URL_TEMPLATE = @json(
    route('hr_admin.teamlead_assignment.assign', ['employee' => '__EMPLOYEE_ID__'])
);

const DEPARTMENTS = @json($departments->pluck('name', 'id'));

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('tr[data-employee-id]').forEach(row => {
        const empId = row.dataset.employeeId;
        const isPending = row.dataset.isPending === '1';

        if (isPending) {
            localStorage.removeItem(`tlSelection_${empId}`);
            return;
        }

        const saved = localStorage.getItem(`tlSelection_${empId}`);

        if (saved) {
            const ids = JSON.parse(saved);

            document.querySelectorAll(`input[data-emp-id="${empId}"]`).forEach(cb => {
                if (cb.dataset.taken === '1') return;
                cb.checked = ids.includes(cb.value);
            });

            updateDeptChipsPreview(empId, ids, true);

            const trigger = document.getElementById(`trigger-${empId}`);
            if (trigger) trigger.classList.add('has-pending');

            const hint = document.getElementById(`pendingHint-${empId}`);
            if (hint) hint.style.display = 'block';
        }
    });
});

function toggleTlAssignPanel(employeeId) {
    document.querySelectorAll('.tl-assign-panel.open').forEach(panel => {
        if (panel.id !== `tlAssignPanel-${employeeId}`) {
            panel.classList.remove('open');
        }
    });

    const panel = document.getElementById(`tlAssignPanel-${employeeId}`);
    if (panel) panel.classList.toggle('open');
}

function handleDeptCheckboxChange(checkbox, employeeId) {
    if (checkbox.checked && checkbox.dataset.taken === '1') {
        alert('এই ডিপার্টমেন্ট ইতিমধ্যে অন্য একজন টিম লিডের কাছে assign করা আছে।');
        checkbox.checked = false;
    }
}

function saveDeptSelection(employeeId) {
    const panel = document.getElementById(`tlAssignPanel-${employeeId}`);
    if (!panel) return;

    const checkboxes = panel.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)');
    const selectedIds = Array.from(checkboxes).map(cb => cb.value);

    localStorage.setItem(`tlSelection_${employeeId}`, JSON.stringify(selectedIds));

    panel.classList.remove('open');
    updateDeptChipsPreview(employeeId, selectedIds, true);

    const trigger = document.getElementById(`trigger-${employeeId}`);
    const hint = document.getElementById(`pendingHint-${employeeId}`);

    if (selectedIds.length > 0) {
        if (trigger) trigger.classList.add('has-pending');
        if (hint) hint.style.display = 'block';
    } else {
        if (trigger) trigger.classList.remove('has-pending');
        if (hint) hint.style.display = 'none';
    }
}

function updateDeptChipsPreview(employeeId, selectedIds, isPendingPreview) {
    const chipsContainer = document.getElementById(`deptChips-${employeeId}`);
    const trigger = document.getElementById(`trigger-${employeeId}`);

    if (!chipsContainer || !trigger) return;

    if (selectedIds.length === 0) {
        chipsContainer.innerHTML = '<span class="dept-tl-empty">Click to select</span>';
        trigger.classList.remove('has-depts', 'has-pending');
        return;
    }

    trigger.classList.add('has-depts');

    const chipClass = isPendingPreview ? 'dept-tl-mini-chip pending' : 'dept-tl-mini-chip';

    chipsContainer.innerHTML = selectedIds.map(id => {
        const name = DEPARTMENTS[id] || 'Unknown';
        return `<span class="${chipClass}">${name}</span>`;
    }).join('');
}

function requestTlChange(employeeId, action) {
    const selectedIds = JSON.parse(localStorage.getItem(`tlSelection_${employeeId}`) || '[]');

    if (selectedIds.length === 0) {
        alert('আগে ড্রপডাউন থেকে অন্তত একটা ডিপার্টমেন্ট সিলেক্ট করে Save করুন।');
        toggleTlAssignPanel(employeeId);
        return;
    }

    const verb = action === 'modify' ? 'modify' : 'assign';

    if (!confirm(`${selectedIds.length}টি ডিপার্টমেন্টের জন্য TL ${verb} request পাঠাতে চান?`)) {
        return;
    }

    localStorage.removeItem(`tlSelection_${employeeId}`);
    submitRequestForm(employeeId, selectedIds, action);
}

function requestRemoveTl(employeeId) {
    if (!confirm('এই employee-কে Team Lead থেকে remove করার request পাঠাতে চান?')) {
        return;
    }

    localStorage.removeItem(`tlSelection_${employeeId}`);
    submitRequestForm(employeeId, [], 'remove');
}

function submitRequestForm(employeeId, departmentIds, action) {
    const form = document.createElement('form');

    form.method = 'POST';
    form.action = REQUEST_URL_TEMPLATE.replace('__EMPLOYEE_ID__', employeeId);

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = CSRF_TOKEN;
    form.appendChild(csrf);

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    form.appendChild(actionInput);

    departmentIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'department_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('click', function (event) {
    document.querySelectorAll('.tl-assign-wrap').forEach(wrap => {
        if (!wrap.contains(event.target)) {
            const panel = wrap.querySelector('.tl-assign-panel');
            if (panel) panel.classList.remove('open');
        }
    });
});
</script>
@endpush

@endsection