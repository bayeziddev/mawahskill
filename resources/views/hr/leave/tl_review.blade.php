@extends('hr.layouts.hr')

@section('title', 'Team Lead Leave Review - Skills Hut Ltd')

@push('styles')
<style>
    .breadcrumb-nav { font-size: 0.82rem; color: #7F7F7F; margin-bottom: 20px; }
    .breadcrumb-nav a { color: #7F7F7F; text-decoration: none; }
    .breadcrumb-nav a:hover { color: #2C67F2; }

    .page-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .page-title { font-size: 1.35rem; font-weight: 700; color: #1E1E1E; margin: 0; }
    .page-subtitle { font-size: 0.85rem; color: #7F7F7F; margin-top: 4px; }

    .tl-review-card {
        background: #fff;
        border: 1px solid #EEF0F4;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(20, 20, 43, 0.03);
        overflow: hidden;
    }

    .table-tl-review {
        width: 100%;
        margin-bottom: 0;
        font-size: 0.88rem;
    }
    .table-tl-review thead th {
        background: #F8F9FC;
        color: #7F7F7F;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #EEF0F4;
        padding: 14px 18px;
        white-space: nowrap;
    }
    .table-tl-review tbody td {
        padding: 16px 18px;
        vertical-align: middle;
        border-bottom: 1px solid #F3F4F8;
        color: #1E1E1E;
    }
    .table-tl-review tbody tr:last-child td { border-bottom: none; }
    .table-tl-review tbody tr:hover { background: #FAFBFF; }

    .tl-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #EAF0FF;
        color: #2C67F2;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    .tl-name { font-weight: 600; font-size: 0.9rem; color: #1E1E1E; }
    .tl-sub { font-size: 0.78rem; color: #9C9FAB; }

    .badge-leave-type {
        background: #F1F4FF;
        color: #2C67F2;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 20px;
        display: inline-block;
    }

    .badge-status-pending {
        background: #FFF6E5;
        color: #B78103;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 20px;
        display: inline-block;
    }

    .reason-text {
        max-width: 220px;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #6B6E7A;
        font-size: 0.83rem;
    }

    .btn-recommend {
        background: #16A34A;
        border: none;
        color: #fff;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 7px 14px;
        border-radius: 8px;
        white-space: nowrap;
    }
    .btn-recommend:hover { background: #128A3E; color: #fff; }

    .btn-decline {
        background: #FFF0F0;
        border: none;
        color: #DC2626;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 7px 14px;
        border-radius: 8px;
        white-space: nowrap;
    }
    .btn-decline:hover { background: #FFE1E1; color: #DC2626; }

    .empty-state {
        text-align: center;
        padding: 70px 20px;
        color: #9C9FAB;
    }
    .empty-state i { font-size: 2.6rem; color: #D7DBE6; display: block; margin-bottom: 14px; }

    .modal-note-label {
        font-size: 0.82rem;
        font-weight: 600;
        color: #1E1E1E;
        margin-bottom: 6px;
        display: block;
    }
</style>
@endpush

@section('content')
<div class="breadcrumb-nav">
    <a href="{{ route('hr_admin.dashboard') }}">Dashboard</a> / Team Lead Leave Review
</div>

<div class="page-header-row">
    <div>
        <h1 class="page-title">Team Lead Leave Requests</h1>
        <div class="page-subtitle">Review leave requests submitted directly by Team Leads before forwarding to Super Admin.</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="tl-review-card">
    @if($leaves->count() > 0)
        <div class="table-responsive">
            <table class="table-tl-review">
                <thead>
                    <tr>
                        <th>Team Lead</th>
                        <th>Leave Type</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leaves as $leave)
                        @php $tlName = $leave->requester->name ?? 'Team Lead'; @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="tl-avatar">{{ strtoupper(substr($tlName, 0, 1)) }}</div>
                                    <div>
                                        <div class="tl-name">{{ $tlName }}</div>
                                        <div class="tl-sub">Team Lead</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge-leave-type">{{ $leave->leaveType->name ?? 'N/A' }}</span></td>
                            <td>
                                {{ \Carbon\Carbon::parse($leave->from_date)->format('d M') }} -
                                {{ \Carbon\Carbon::parse($leave->to_date)->format('d M, Y') }}
                                <div class="tl-sub">{{ $leave->duration }} day{{ $leave->duration != 1 ? 's' : '' }}</div>
                            </td>
                            <td><span class="reason-text" title="{{ $leave->reason }}">{{ $leave->reason }}</span></td>
                            <td class="tl-sub">{{ optional($leave->created_at)->format('d M Y, h:i A') }}</td>
                            <td><span class="badge-status-pending">Awaiting HR</span></td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn-recommend" data-bs-toggle="modal" data-bs-target="#recommendModal{{ $leave->id }}">
                                        <i class="bi bi-check2"></i> Recommend
                                    </button>
                                    <button type="button" class="btn-decline" data-bs-toggle="modal" data-bs-target="#declineModal{{ $leave->id }}">
                                        <i class="bi bi-x"></i> Decline
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- Recommend Modal --}}
                        <div class="modal fade" id="recommendModal{{ $leave->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('hrm.leave.tl_recommend', $leave->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Recommend {{ $tlName }}'s Leave</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="modal-note-label">Note for Super Admin</label>
                                            <textarea name="hr_note" rows="4" class="form-control" placeholder="Add a note before forwarding this leave to the Super Admin..." required></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn-recommend">Confirm Recommendation</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Decline Modal --}}
                        <div class="modal fade" id="declineModal{{ $leave->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('hrm.leave.tl_not_recommend', $leave->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Decline {{ $tlName }}'s Leave</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="modal-note-label">Reason for declining</label>
                                            <textarea name="hr_note" rows="4" class="form-control" placeholder="Explain why this leave request is being declined..." required></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn-decline">Confirm Decline</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-3">
            {{ $leaves->links() }}
        </div>
    @else
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            No Team Lead leave requests awaiting your recommendation right now.
        </div>
    @endif
</div>
@endsection