@extends('hr.layouts.hr')

@section('title', 'Admin Dashboard - Skills Hut Ltd')

@push('styles')
<style>
    /* ===== Base / Mobile-First ===== */
    .kpi-card {
        border-radius: 12px;
        transition: transform 0.2s;
    }
    .kpi-card:hover { transform: translateY(-2px); }

    .kpi-label {
        font-size: 0.7rem;
        letter-spacing: 0.5px;
    }
    .kpi-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #1A1A1A;
    }

    .page-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.35rem;
        font-weight: 700;
    }
    .page-subtitle { font-size: 0.85rem; }

    .btn-brand {
        background-color: #FF5E2B;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        width: 100%;
        justify-content: center;
    }
    .btn-outline-custom {
        border: 1px solid #E2E0DD;
        color: #4A4A4A;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .btn-outline-custom:hover { background-color: #FAF9F6; }

    .section-card {
        border-radius: 14px;
        border: 1px solid #E2E0DD;
        background: #fff;
        padding: 16px;
    }
    .section-title {
        font-family: 'Outfit', sans-serif;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1A1A1A;
    }

    /* Table */
    .table th {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #7F7F7F;
        border-bottom: 1px solid #E2E0DD;
        padding: 10px 10px;
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

    .badge-approved, .badge-pending {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 20px;
        display: inline-block;
        white-space: nowrap;
    }
    .badge-approved { background: #ECFDF5; color: #059669; }
    .badge-pending  { background: #FFF7ED; color: #EA580C; }

    /* Action buttons */
    .action-group {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }

    .btn-approve, .btn-reject, .btn-view {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
        flex: 1 1 auto;
        text-align: center;
        min-width: 70px;
    }
    .btn-approve {
        background: #FF5E2B; color: #fff; border: none;
    }
    .btn-approve:hover { background: #E04B1A; }
    .btn-reject {
        background: none; border: 1px solid #E2E0DD; color: #DC2626;
    }
    .btn-reject:hover { background: #FEE2E2; }
    .btn-view {
        background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE;
        text-decoration: none;
        display: inline-flex; align-items: center; justify-content: center; gap: 4px;
    }
    .btn-view:hover { background: #2563EB; color: #fff; border-color: #2563EB; }

    .link-brand {
        color: #FF5E2B;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
    }
    .link-brand:hover { text-decoration: underline; }

    .activity-item {
        padding: 12px 0;
        border-bottom: 1px solid #F4F4F0;
    }
    .activity-item:last-child { border-bottom: none; }
    .activity-item strong { font-size: 0.85rem; color: #1A1A1A; }
    .activity-item p { font-size: 0.8rem; color: #4A4A4A; margin: 2px 0 0; }
    .activity-item .time { font-size: 0.72rem; color: #B2ADA7; }

    /* ===== CTA Card ===== */
    .cta-card {
        background: linear-gradient(135deg, #FF5E2B 0%, #E04B1A 100%);
        border-radius: 14px;
        padding: 24px;
        color: #fff;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .cta-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: rgba(255,255,255,0.18);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        margin-bottom: 14px;
    }
    .cta-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 6px;
    }
    .cta-sub {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.9);
        margin-bottom: 18px;
        max-width: 520px;
    }
    .btn-submit-request {
        background: #fff;
        color: #FF5E2B;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        padding: 10px 20px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: background 0.2s;
    }
    .btn-submit-request:hover { background: #FFF0EB; color: #E04B1A; }

    /* ===== TL Leave Queue section (redesigned) ===== */
    .tl-queue-card {
        border-radius: 14px;
        border: 1px solid #E2E0DD;
        background: #fff;
        padding: 16px;
        margin-bottom: 24px;
        overflow: hidden;
    }
    .tl-queue-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 14px;
        padding-bottom: 14px;
        border-bottom: 1px solid #F4F4F0;
    }
    .tl-queue-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #FFF6E5;
        color: #F5A623;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .tl-queue-title {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 0.95rem;
        color: #1A1A1A;
    }
    .tl-queue-sub { font-size: 0.8rem; color: #7F7F7F; margin-top: 2px; }

    .btn-review-tl {
        background: #fff;
        color: #F5A623;
        border: 1px solid #FDE9C4;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 8px;
        text-decoration: none;
        white-space: nowrap;
    }
    .btn-review-tl:hover { background: #FFF6E5; color: #DB9110; }

    .table-tl-queue { width: 100%; font-size: 0.85rem; }
    .table-tl-queue thead th {
        color: #9C9FAB;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-weight: 700;
        padding: 8px;
        border-bottom: 1px solid #F4F4F0;
        text-align: left;
        white-space: nowrap;
    }
    .table-tl-queue tbody td {
        padding: 10px 8px;
        border-bottom: 1px solid #F8F9FC;
        color: #1A1A1A;
        vertical-align: middle;
    }
    .table-tl-queue tbody tr:last-child td { border-bottom: none; }

    .tl-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #FFF6E5;
        color: #F5A623;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.78rem;
        flex-shrink: 0;
    }
    .tl-name { font-weight: 600; font-size: 0.85rem; }
    .tl-sub { font-size: 0.78rem; color: #9C9FAB; }

    .badge-leave-type {
        background: #F1F4FF;
        color: #2C67F2;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-block;
        white-space: nowrap;
    }

    .btn-tl-recommend {
        background: #F5A623;
        color: #fff;
        border: none;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 6px;
        white-space: nowrap;
    }
    .btn-tl-recommend:hover { background: #DB9110; color: #fff; }

    .btn-tl-decline {
        background: none;
        border: 1px solid #E2E0DD;
        color: #DC2626;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 6px;
        white-space: nowrap;
    }
    .btn-tl-decline:hover { background: #FEE2E2; }

    /* ===== HR Own Leave Balance Cards ===== */
    .leave-stat-card {
        border-radius: 12px;
        border: 1px solid #E2E0DD;
        background: #fff;
        padding: 16px;
        position: relative;
        transition: transform 0.2s;
        height: 100%;
    }
    .leave-stat-card:hover { transform: translateY(-2px); }

    .leave-stat-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        margin-bottom: 10px;
    }
    .leave-stat-icon.green  { background: #ECFDF5; color: #059669; }
    .leave-stat-icon.red    { background: #FEF2F2; color: #DC2626; }
    .leave-stat-icon.blue   { background: #EFF6FF; color: #2563EB; }
    .leave-stat-icon.orange { background: #FFF7ED; color: #EA580C; }

    .leave-stat-label {
        font-size: 0.75rem;
        color: #7F7F7F;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .leave-stat-number {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1A1A1A;
    }
    .leave-stat-number span {
        font-size: 0.72rem;
        font-weight: 500;
        color: #9C9FAB;
        margin-left: 4px;
    }
    .leave-stat-badge-active {
        position: absolute;
        top: 14px;
        right: 14px;
        background: #ECFDF5;
        color: #059669;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
    }

    @media (min-width: 768px) {
        .leave-stat-number { font-size: 1.6rem; }
    }

    /* ===== Mobile Overrides ===== */
    @media (max-width: 575.98px) {
        .cta-card { padding: 18px; }
        .cta-title { font-size: 1.05rem; }
        .cta-sub { font-size: 0.8rem; }

        .tl-queue-card { padding: 14px; }
        .tl-queue-top { flex-direction: column; align-items: flex-start; }
        .btn-review-tl { width: 100%; text-align: center; }
        .table-tl-queue .action-group { flex-direction: column; }
        .table-tl-queue .btn-tl-recommend,
        .table-tl-queue .btn-tl-decline { width: 100%; }

        .action-group { flex-direction: column; }
        .action-group form, .action-group .btn-reject, .action-group .btn-view {
            width: 100%;
        }
    }

    /* ===== Tablet (≥576px) ===== */
    @media (min-width: 576px) {
        .kpi-value { font-size: 1.8rem; }
        .page-title { font-size: 1.5rem; }
        .btn-brand { width: auto; }
        .section-card { padding: 20px; }
    }

    /* ===== Desktop (≥768px) ===== */
    @media (min-width: 768px) {
        .kpi-value { font-size: 2rem; }
        .kpi-label { font-size: 0.75rem; }
        .page-title { font-size: 1.6rem; }
        .section-card { padding: 24px; }
        .section-title { font-size: 1rem; }
        .table th { font-size: 0.72rem; padding: 10px 12px; }
        .table td { font-size: 0.85rem; padding: 14px 12px; }
        .avatar-circle { width: 34px; height: 34px; font-size: 0.78rem; }
        .badge-approved, .badge-pending { font-size: 0.75rem; padding: 4px 10px; }
        .btn-approve, .btn-reject, .btn-view {
            font-size: 0.78rem;
            padding: 5px 12px;
            flex: 0 0 auto;
            min-width: auto;
        }
    }

    /* ===== Large Desktop (≥992px) ===== */
    @media (min-width: 992px) {
        .action-group { flex-wrap: nowrap; flex-direction: row; }
        .action-group form, .action-group .btn-reject, .action-group .btn-view {
            width: auto;
        }
    }

    /* ===== Prevent horizontal page scroll ===== */
    body { overflow-x: hidden; }
</style>
@endpush

@section('content')

{{-- Page Header --}}
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">HR Dashboard</h1>
        <p class="page-subtitle text-muted mb-0">Skills Hut Ltd</p>
    </div>
    <a href="{{ url()->current() }}" class="btn btn-brand d-flex align-items-center gap-2 text-decoration-none">
        <i class="bi bi-arrow-clockwise"></i> Refresh Data
    </a>
</div>

@if(session('success'))
    <div class="alert border-0 rounded-3 mb-4"
         style="background:#ECFDF5;color:#059669;font-size:0.88rem;">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert border-0 rounded-3 mb-4"
         style="background:#FEF2F2;color:#DC2626;font-size:0.88rem;">
        <i class="bi bi-exclamation-circle-fill me-2"></i> {{ session('error') }}
    </div>
@endif

{{-- HR's Own Leave Balance --}}
<div class="section-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title">My Leave Balance</div>
        <a href="{{ route('hr_admin.leave.create') }}" class="link-brand">Request Leave</a>
    </div>

    <div class="row g-3">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="leave-stat-card">
                <div class="leave-stat-icon green"><i class="bi bi-calendar3"></i></div>
                <div class="leave-stat-label">Annual Leave</div>
                <div class="leave-stat-number">{{ $annualBalance ?? 0 }}<span>days</span></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="leave-stat-card">
                <div class="leave-stat-icon blue"><i class="bi bi-calendar-check"></i></div>
                <div class="leave-stat-label">Casual Leave</div>
                <div class="leave-stat-number">{{ $casualBalance ?? 0 }}<span>days</span></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="leave-stat-card">
                <div class="leave-stat-icon orange"><i class="bi bi-heart-pulse"></i></div>
                <div class="leave-stat-label">Sick Leave</div>
                <div class="leave-stat-number">{{ $sickBalance ?? 0 }}<span>days</span></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="leave-stat-card">
                <div class="leave-stat-icon red"><i class="bi bi-bandaid"></i></div>
                <div class="leave-stat-label">Sick Taken</div>
                <div class="leave-stat-number">{{ $sickUsed ?? 0 }}<span>days</span></div>
            </div>
        </div>

        
        <div class="col-6 col-md-4 col-xl-2">
            <div class="leave-stat-card">
                <div class="leave-stat-icon blue"><i class="bi bi-person-check"></i></div>
                <div class="leave-stat-label">Casual Taken</div>
                <div class="leave-stat-number">{{ $casualUsed ?? 0 }}<span>days</span></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="leave-stat-card">
                <div class="leave-stat-icon blue"><i class="bi bi-person-check"></i></div>
                <div class="leave-stat-label">Annual Taken</div>
                <div class="leave-stat-number">{{ $annualUsed ?? 0 }}<span>days</span></div>
            </div>
        </div>


        <div class="col-6 col-md-4 col-xl-2">
            <div class="leave-stat-card">
                <span class="leave-stat-badge-active">Active</span>
                <div class="leave-stat-icon green"><i class="bi bi-calendar2-check"></i></div>
                <div class="leave-stat-label">Remaining</div>
                <div class="leave-stat-number">{{ $remainingLeaves ?? 0 }}<span>total</span></div>
            </div>
        </div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 g-md-4 mb-4">
    <div class="col-6 col-lg-6 col-xl-3">
        <div class="card kpi-card border-0 shadow-sm h-100">
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <span class="kpi-label text-uppercase text-muted fw-semibold d-block mb-2">Total Workforce</span>
                <h3 class="kpi-value mb-2">{{ $totalEmployees ?? 0 }}</h3>
                <div class="text-success d-flex align-items-center gap-1">
                    <i class="bi bi-people"></i>
                    <span style="font-size:0.75rem;">Active Employees</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-6 col-xl-3">
        <div class="card kpi-card border-0 shadow-sm h-100">
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <span class="kpi-label text-uppercase text-muted fw-semibold d-block mb-2">Departments</span>
                <h3 class="kpi-value mb-2">{{ $totalDepartments ?? 0 }}</h3>
                <div class="text-muted" style="font-size:0.75rem;">Total Departments</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-6 col-xl-3">
        <div class="card kpi-card border-0 shadow-sm h-100">
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <span class="kpi-label text-uppercase text-muted fw-semibold d-block mb-2">Pending Approvals</span>
                <h3 class="kpi-value mb-2">{{ ($leaveQueue ?? collect())->count() }}</h3>
                <div class="text-warning d-flex align-items-center gap-1">
                    <i class="bi bi-clock"></i>
                    <span style="font-size:0.75rem;">Awaiting HR Action</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-6 col-xl-3">
        <div class="card kpi-card border-0 shadow-sm h-100">
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <span class="kpi-label text-uppercase text-muted fw-semibold d-block mb-2">Recent Activity</span>
                <h3 class="kpi-value mb-2">{{ ($recentActivity ?? collect())->count() }}</h3>
                <div class="text-muted" style="font-size:0.75rem;">Last actions taken</div>
            </div>
        </div>
    </div>
</div>

{{-- CTA card --}}
<div class="cta-card">
    <span class="cta-icon"><i class="bi bi-send-fill"></i></span>
    <div class="cta-title">Planning a getaway?</div>
    <div class="cta-sub">Request your leave now to ensure timely approvals and smooth project handovers.</div>
    <a href="{{ route('hr_admin.leave.create') }}" class="btn-submit-request">
        <i class="bi bi-plus-circle"></i> Submit New Request
    </a>
</div>

{{-- TL Leave Queue (redesigned) --}}
@if(($tlLeaveQueue ?? collect())->count() > 0)
<div class="tl-queue-card">
    <div class="tl-queue-top">
        <div class="d-flex align-items-center gap-2">
            <span class="tl-queue-icon"><i class="bi bi-person-badge"></i></span>
            <div>
                <div class="tl-queue-title">
                    {{ $tlLeaveQueue->count() }} Team Lead leave request{{ $tlLeaveQueue->count() != 1 ? 's' : '' }} awaiting your recommendation
                </div>
                <div class="tl-queue-sub">Recommend or decline directly here — approved ones move to Super Admin.</div>
            </div>
        </div>
        <a href="{{ route('hrm.leave.tl_review') }}" class="btn-review-tl">
            <i class="bi bi-arrow-right-circle"></i> Review All
        </a>
    </div>

    <div class="table-responsive">
        <table class="table-tl-queue">
            <thead>
                <tr>
                    <th>Team Lead</th>
                    <th>Leave Type</th>
                    <th>Duration</th>
                    <th>Submitted</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tlLeaveQueue as $leave)
                    @php $tlName = $leave->requester->name ?? 'Team Lead'; @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="tl-avatar">{{ strtoupper(substr($tlName, 0, 1)) }}</div>
                                <span class="tl-name">{{ $tlName }}</span>
                            </div>
                        </td>
                        <td><span class="badge-leave-type">{{ $leave->leaveType->name ?? 'N/A' }}</span></td>
                        <td style="white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($leave->from_date)->format('d M') }} -
                            {{ \Carbon\Carbon::parse($leave->to_date)->format('d M, Y') }}
                        </td>
                        <td class="tl-sub" style="white-space:nowrap;">{{ optional($leave->created_at)->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="action-group justify-content-end">
                                <button type="submit" form="tlRecommendForm{{ $leave->id }}" class="btn-tl-recommend">
                                    <i class="bi bi-check2"></i> Recommend
                                </button>
                                <button type="button" class="btn-tl-decline" data-decline-url="{{ route('hrm.leave.tl_not_recommend', $leave->id) }}" onclick="openTlDeclineModal(this)">
                                    <i class="bi bi-x"></i> Decline
                                </button>
                            </div>
                            <form id="tlRecommendForm{{ $leave->id }}" method="POST" action="{{ route('hrm.leave.tl_recommend', $leave->id) }}" class="d-none">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="hr_note" value="Recommended by HR — forwarded to Super Admin.">
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Approval Queue + Activity Log --}}
<div class="row g-3 g-md-4 mb-4">

    {{-- HR Final Approval Queue --}}
    <div class="col-12 col-lg-7">
        <div class="section-card h-100">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
                <div class="section-title">HR Final Approval Queue</div>
                <a href="{{route('hr_admin.employee_leave.index')}}" class="link-brand">View All Requests</a>
            </div>

            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Employee / Dept</th>
                            <th>TL Status</th>
                            <th>Period</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveQueue as $leave)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle">
                                        {{ strtoupper(substr($leave->employee->name ?? 'NA', 0, 2)) }}
                                    </div>
                                    <div style="min-width:0;">
                                        <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;">
                                            {{ $leave->employee->name ?? 'N/A' }}
                                        </div>
                                        <div style="font-size:0.72rem;color:#7F7F7F;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;">
                                            {{ $leave->employee->department->name ?? 'N/A' }}
                                            • {{ $leave->employee->designation ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge-approved">
                                    <i class="bi bi-check-circle me-1"></i>Recommended by TL
                                </span>
                            </td>
                            <td style="font-size:0.8rem;white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($leave->from_date)->format('M d') }} -
                                {{ \Carbon\Carbon::parse($leave->to_date)->format('M d') }}
                            </td>
                            <td>
                                <div class="action-group">
                                    <form method="POST" action="{{ route('hr.leave.approve', $leave->id) }}" style="display: contents;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-approve">Approve</button>
                                    </form>

                                    <button class="btn-reject" data-reject-url="/hr_admin/leave/{{ $leave->id }}/reject" onclick="openRejectModal(this)">
                                        Reject
                                    </button>

                                    <a href="{{ route('hr.leave.show', $leave->id) }}" class="btn-view">
                                        <i class="bi bi-eye"></i> View
                                    </a>

                                    <form method="POST" action="{{ route('hr.leave.destroy', $leave->id) }}" style="display: contents;" onsubmit="return confirm('Are you sure you want to delete this leave request?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-reject" style="color: #DC2626; border-color: #FECACA;">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4"
                                style="text-align:center;padding:32px;color:#7F7F7F;font-size:0.85rem;">
                                <i class="bi bi-check2-all"
                                   style="font-size:1.5rem;display:block;margin-bottom:8px;color:#E2E0DD;"></i>
                                No pending approvals.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Activity Log --}}
    <div class="col-12 col-lg-5">
        <div class="section-card h-100">
            <div class="section-title mb-3">Recent Activity</div>
            @forelse($recentActivity as $activity)
            <div class="activity-item">
                <strong>{{ $activity->employee->name ?? 'N/A' }}</strong>
                <p>
                    Leave request ({{ $activity->leaveType->name ?? 'N/A' }})
                    @if($activity->status === 'approved')
                        <span style="color:#059669;font-weight:600;">approved by HR</span>
                    @elseif($activity->status === 'rejected')
                        <span style="color:#DC2626;font-weight:600;">rejected by HR</span>
                    @elseif($activity->tl_status === 'recommended')
                        <span style="color:#EA580C;font-weight:600;">recommended by TL</span>
                    @elseif($activity->tl_status === 'not_recommended')
                        <span style="color:#DC2626;font-weight:600;">declined by TL</span>
                    @else
                        <span style="color:#7F7F7F;font-weight:600;">submitted</span>
                    @endif
                </p>
                <span class="time">{{ optional($activity->updated_at)->diffForHumans() }}</span>
            </div>
            @empty
            <div style="text-align:center;padding:32px;color:#7F7F7F;font-size:0.85rem;">
                <i class="bi bi-clock-history"
                   style="font-size:1.5rem;display:block;margin-bottom:8px;color:#E2E0DD;"></i>
                No recent activity.
            </div>
            @endforelse
            <div class="mt-3">
                <a href="{{ route('hr_admin.employee_activity.index') }}"
                   class="btn btn-outline-custom w-100 text-decoration-none d-block text-center"
                   style="font-size:0.82rem;">
                    View Full Audit Log
                </a>
            </div>
        </div>
    </div>

</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius:14px;border:1px solid #E2E0DD;">
            <div class="modal-header" style="border-bottom:1px solid #F4F4F0;">
                <h6 class="modal-title"
                    style="font-family:'Outfit',sans-serif;font-weight:700;">
                    Reject Leave Request
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <label style="font-size:0.82rem;font-weight:600;color:#4A4A4A;margin-bottom:7px;display:block;">
                        Rejection Note <span class="text-danger">*</span>
                    </label>
                    <textarea name="hr_note" rows="4" required
                              class="form-control"
                              style="border:1px solid #E2E0DD;border-radius:8px;padding:10px 14px;font-size:0.88rem;resize:vertical;"
                              placeholder="Reason for rejection..."></textarea>
                </div>
                <div class="modal-footer" style="border-top:1px solid #F4F4F0;">
                    <button type="button" class="btn btn-sm"
                            style="background:#fff;border:1px solid #E2E0DD;border-radius:8px;font-weight:600;"
                            data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm"
                            style="background:#DC2626;color:#fff;border:none;border-radius:8px;font-weight:600;padding:8px 20px;">
                        Confirm Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- TL Decline Modal --}}
<div class="modal fade" id="tlDeclineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius:14px;border:1px solid #E2E0DD;">
            <div class="modal-header" style="border-bottom:1px solid #F4F4F0;">
                <h6 class="modal-title" style="font-family:'Outfit',sans-serif;font-weight:700;">
                    Decline Team Lead Leave Request
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="tlDeclineForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <label style="font-size:0.82rem;font-weight:600;color:#4A4A4A;margin-bottom:7px;display:block;">
                        Reason for declining <span class="text-danger">*</span>
                    </label>
                    <textarea name="hr_note" rows="4" required
                              class="form-control"
                              style="border:1px solid #E2E0DD;border-radius:8px;padding:10px 14px;font-size:0.88rem;resize:vertical;"
                              placeholder="Explain why this leave request is being declined..."></textarea>
                </div>
                <div class="modal-footer" style="border-top:1px solid #F4F4F0;">
                    <button type="button" class="btn btn-sm"
                            style="background:#fff;border:1px solid #E2E0DD;border-radius:8px;font-weight:600;"
                            data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm"
                            style="background:#DC2626;color:#fff;border:none;border-radius:8px;font-weight:600;padding:8px 20px;">
                        Confirm Decline
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openRejectModal(btn) {
    const url = btn.getAttribute('data-reject-url');
    document.getElementById('rejectForm').action = url;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function openTlDeclineModal(btn) {
    const url = btn.getAttribute('data-decline-url');
    document.getElementById('tlDeclineForm').action = url;
    new bootstrap.Modal(document.getElementById('tlDeclineModal')).show();
}
</script>
@endpush