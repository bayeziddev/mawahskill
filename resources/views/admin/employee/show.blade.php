@extends('admin.layouts.admin')

@section('title', 'Employee Profile - Skills Hut Ltd')

@push('styles')
<style>
    .profile-header {
        background: #fff;
        border: 1px solid #E2E0DD;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .profile-photo {
        width: 110px;
        height: 110px;
        border-radius: 14px;
        object-fit: cover;
        border: 1px solid #E2E0DD;
    }

    .profile-name {
        font-family: 'Outfit', sans-serif;
        font-size: 1.6rem;
        font-weight: 700;
        color: #1A1A1A;
        margin-bottom: 4px;
    }

    .profile-meta {
        color: #7F7F7F;
        font-size: 0.9rem;
    }

    .status-badge {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 5px 10px;
        border-radius: 20px;
        letter-spacing: 0.3px;
    }

    .status-active {
        background: #ECFDF5;
        color: #059669;
    }

    .status-inactive {
        background: #F4F4F0;
        color: #7F7F7F;
    }

    .info-card {
        background: #fff;
        border: 1px solid #E2E0DD;
        border-radius: 14px;
        padding: 20px;
        height: 100%;
    }

    .info-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: #1A1A1A;
        margin-bottom: 16px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 10px 0;
        border-bottom: 1px solid #F4F4F0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 0.82rem;
        color: #7F7F7F;
        font-weight: 600;
    }

    .info-value {
        font-size: 0.88rem;
        color: #1A1A1A;
        font-weight: 500;
        text-align: right;
    }

    .btn-edit {
        background: #FF5E2B;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 10px 18px;
        text-decoration: none;
    }

    .btn-edit:hover {
        background: #E04B1A;
        color: #fff;
    }

    .btn-back {
        background: #fff;
        color: #1A1A1A;
        border: 1px solid #E2E0DD;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 10px 18px;
        text-decoration: none;
    }

    .btn-back:hover {
        background: #FAF9F6;
        color: #1A1A1A;
    }

    .leave-stat-card {
        border-radius: 12px;
        border: 1px solid #F4F4F0;
        background: #FAF9F6;
        padding: 16px;
        height: 100%;
    }
    .leave-stat-icon {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; margin-bottom: 10px;
    }
    .leave-stat-icon.green  { background: #ECFDF5; color: #059669; }
    .leave-stat-icon.red    { background: #FEF2F2; color: #DC2626; }
    .leave-stat-icon.blue   { background: #EFF6FF; color: #2563EB; }
    .leave-stat-icon.orange { background: #FFF7ED; color: #EA580C; }
    .leave-stat-label { font-size: 0.75rem; color: #7F7F7F; font-weight: 600; margin-bottom: 4px; }
    .leave-stat-number { font-size: 1.4rem; font-weight: 700; color: #1A1A1A; }
    .leave-stat-number span { font-size: 0.72rem; font-weight: 500; color: #9C9FAB; margin-left: 4px; }

    .badge-approved, .badge-pending, .badge-rejected {
        font-size: 0.7rem; font-weight: 600; padding: 3px 10px; border-radius: 20px;
        display: inline-block; white-space: nowrap;
    }
    .badge-approved { background: #ECFDF5; color: #059669; }
    .badge-pending  { background: #FFF7ED; color: #EA580C; }
    .badge-rejected { background: #FEF2F2; color: #DC2626; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title mb-1">Employee Profile</h1>
        <p class="page-subtitle mb-0">Full employee information and contact details.</p>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.employee.index') }}" class="btn-back d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back
        </a>

        <a href="{{ route('admin.employee.edit', $employee->id) }}" class="btn-edit d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square"></i> Edit
        </a>

         <form action="{{ route('admin.employee.destroy', $employee->id) }}" 
          method="POST" 
          onsubmit="return confirm('Are you sure you want to delete this employee?')">
        @csrf
        @method('DELETE')
        <button type="submit" 
                style="background:#DC2626;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:0.88rem;padding:10px 18px;cursor:pointer;">
            <i class="bi bi-trash"></i> Delete
        </button>
    </form>
    </div>
</div>

<div class="profile-header">
    <div class="d-flex align-items-center gap-4 flex-wrap">
        <div>
            @if($employee->profile_picture)
                <img src="{{ asset('storage/' . $employee->profile_picture) }}" class="profile-photo" alt="{{ $employee->name }}">
            @else
                <img src="{{ asset('images/admin_avatar.png') }}" class="profile-photo" alt="{{ $employee->name }}">
            @endif
        </div>

        <div class="flex-grow-1">
            <div class="profile-name">{{ $employee->name }}</div>

            <div class="profile-meta mb-2">
                {{ $employee->designation ?? 'N/A' }}
                @if($employee->department)
                    • {{ $employee->department->name }}
                @endif
            </div>

            <span class="status-badge {{ $employee->status === 'active' ? 'status-active' : 'status-inactive' }}">
                {{ strtoupper($employee->status) }}
            </span>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="info-card">
            <div class="info-title">Basic Information</div>

            <div class="info-row">
                <div class="info-label">Employee ID</div>
                <div class="info-value">{{ $employee->employee_id ?? 'N/A' }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">NID</div>
                <div class="info-value">{{ $employee->nid ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Blood Group</div>
                <div class="info-value">{{ $employee->blood_group ?? 'N/A' }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Name</div>
                <div class="info-value">{{ $employee->name }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $employee->email }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Phone</div>
                <div class="info-value">{{ $employee->phone ?? 'N/A' }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Date of Birth</div>
                <div class="info-value">{{ $employee->date_of_birth ?? 'N/A' }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Gender</div>
                <div class="info-value">{{ ucfirst($employee->gender ?? 'N/A') }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Address</div>
                <div class="info-value">{{ $employee->address ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="info-card">
            <div class="info-title">Job Information</div>

            <div class="info-row">
                <div class="info-label">Department</div>
                <div class="info-value">{{ $employee->department->name ?? 'No Department' }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Designation</div>
                <div class="info-value">{{ $employee->designation ?? 'N/A' }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Role</div>
                <div class="info-value">{{ $employee->role ?? 'N/A' }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Hire Date</div>
                <div class="info-value">{{ $employee->hire_date ?? 'N/A' }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Status</div>
                <div class="info-value">{{ ucfirst($employee->status) }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Created At</div>
                <div class="info-value">{{ $employee->created_at ? $employee->created_at->format('d M Y') : 'N/A' }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Updated At</div>
                <div class="info-value">{{ $employee->updated_at ? $employee->updated_at->format('d M Y') : 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="info-card">
            <div class="info-title">Emergency Contact</div>

            <div class="row">
                <div class="col-md-4">
                    <div class="info-row">
                        <div class="info-label">Name</div>
                        <div class="info-value">{{ $employee->emergency_contact_name ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-row">
                        <div class="info-label">Phone</div>
                        <div class="info-value">{{ $employee->emergency_contact_phone ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-row">
                        <div class="info-label">Relationship</div>
                        <div class="info-value">{{ $employee->emergency_contact_relationship ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>


    </div>
    {{-- Remove Profile Picture --}}

    {{-- Leave Balance --}}
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="info-card">
            <div class="info-title">Leave Balance</div>
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="leave-stat-card">
                        <div class="leave-stat-icon green"><i class="bi bi-calendar3"></i></div>
                        <div class="leave-stat-label">Annual Leave Balance</div>
                        <div class="leave-stat-number">{{ $annualBalance ?? 0 }}<span>days</span></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="leave-stat-card">
                        <div class="leave-stat-icon red"><i class="bi bi-bandaid"></i></div>
                        <div class="leave-stat-label">Sick Leave Balance</div>
                        <div class="leave-stat-number">{{ $sickBalance ?? 0 }}<span>days</span></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="leave-stat-card">
                        <div class="leave-stat-icon blue"><i class="bi bi-person-check"></i></div>
                        <div class="leave-stat-label">Casual Leave Balance</div>
                        <div class="leave-stat-number">{{ $casualBalance ?? 0 }}<span>days</span></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="leave-stat-card">
                        <div class="leave-stat-icon red"><i class="bi bi-bandaid"></i></div>
                        <div class="leave-stat-label">Sick Leave Taken</div>
                        <div class="leave-stat-number">{{ $sickUsed ?? 0 }}<span>days</span></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="leave-stat-card">
                        <div class="leave-stat-icon blue"><i class="bi bi-person-check"></i></div>
                        <div class="leave-stat-label">Casual Leave Taken</div>
                        <div class="leave-stat-number">{{ $casualUsed ?? 0 }}<span>days</span></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="leave-stat-card">
                        <div class="leave-stat-icon orange"><i class="bi bi-calendar2-check"></i></div>
                        <div class="leave-stat-label">Remaining Leaves</div>
                        <div class="leave-stat-number">{{ $remainingLeaves ?? 0 }}<span>total</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Leave History --}}
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="info-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="info-title mb-0">Leave History</div>
                <span class="text-muted" style="font-size:0.8rem;">{{ $leaves->total() }} records</span>
            </div>

            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#7F7F7F;border-bottom:1px solid #E2E0DD;padding:10px;">Leave Type</th>
                            <th style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#7F7F7F;border-bottom:1px solid #E2E0DD;padding:10px;">Duration</th>
                            <th style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#7F7F7F;border-bottom:1px solid #E2E0DD;padding:10px;">Period</th>
                            <th style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#7F7F7F;border-bottom:1px solid #E2E0DD;padding:10px;">TL Status</th>
                            <th style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#7F7F7F;border-bottom:1px solid #E2E0DD;padding:10px;">Final Status</th>
                            <th style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#7F7F7F;border-bottom:1px solid #E2E0DD;padding:10px;">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaves as $leave)
                            @php
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
                                <td style="font-size:0.82rem;padding:12px 10px;border-bottom:1px solid #F4F4F0;font-weight:600;">
                                    {{ $leave->leaveType->name ?? 'N/A' }}
                                </td>
                                <td style="font-size:0.82rem;padding:12px 10px;border-bottom:1px solid #F4F4F0;color:#7F7F7F;">
                                    {{ $leave->duration }} {{ $leave->duration == 1 ? 'Day' : 'Days' }}
                                </td>
                                <td style="font-size:0.82rem;padding:12px 10px;border-bottom:1px solid #F4F4F0;color:#7F7F7F;white-space:nowrap;">
                                    {{ \Carbon\Carbon::parse($leave->from_date)->format('M d') }} -
                                    {{ \Carbon\Carbon::parse($leave->to_date)->format('M d, Y') }}
                                </td>
                                <td style="padding:12px 10px;border-bottom:1px solid #F4F4F0;">
                                    <span class="{{ $tlStatusCls }}">{{ ucfirst(str_replace('_',' ', $leave->tl_status ?? 'pending')) }}</span>
                                </td>
                                <td style="padding:12px 10px;border-bottom:1px solid #F4F4F0;">
                                    <span class="{{ $statusCls }}">{{ ucfirst($leave->status) }}</span>
                                </td>
                                <td style="font-size:0.78rem;padding:12px 10px;border-bottom:1px solid #F4F4F0;color:#7F7F7F;">
                                    {{ optional($leave->created_at)->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:32px;color:#7F7F7F;">
                                    No leave records found for this employee.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($leaves->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $leaves->appends(request()->except('leave_page'))->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
    
   
    



</div>

@endsection