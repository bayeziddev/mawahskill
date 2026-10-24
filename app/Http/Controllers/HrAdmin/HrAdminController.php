<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\LeaveNotification;
class HrAdminController extends Controller
{
 public function dashboard()
{
    $hr = auth('Hr')->user();

    // ================= KPI =================
    $totalEmployees   = Employee::count();
    $totalDepartments = Department::count();

    // ================= HR Final Approval Queue =================
    // Employee → TL recommended → HR এর কাছে
    $leaveQueue = Leave::where('requester_type', 'employee')
        ->where('tl_status', 'recommended')
        ->where('status', 'pending')
        ->with(['employee.department', 'leaveType'])
        ->latest()
        ->take(5)
        ->get();

    // TL নিজে leave দিয়েছে — HR এর recommendation দরকার (Super Admin এর আগে)
    $tlLeaveQueue = Leave::where('requester_type', 'tl')
        ->where('hr_status', 'pending')
        ->with(['requester', 'leaveType'])
        ->latest()
        ->take(5)
        ->get();

    // Activity log — recent leaves (any status change)
    $recentActivity = Leave::with(['employee'])
        ->where(function ($q) {
            $q->whereIn('status', ['approved', 'rejected'])
              ->orWhere('tl_status', 'recommended');
        })
        ->latest()
        ->take(5)
        ->get();

    // ================= HR's own leave balance =================
    $leaveTypes = LeaveType::where('is_active', 1)->get();

    // Approved leave — type অনুযায়ী কতদিন নেওয়া হয়েছে
    $usedByType = Leave::where('requester_id', $hr->id)
        ->where('requester_type', 'hr')
        ->where('status', 'approved')
        ->selectRaw('leave_type_id, SUM(duration) as used_days')
        ->groupBy('leave_type_id')
        ->pluck('used_days', 'leave_type_id');

    // নির্দিষ্ট type খোঁজো
    $annual = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'annual'));
    $sick   = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'sick'));
    $casual = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'casual'));

    // Used days per type
    $annualUsed = $annual ? ($usedByType[$annual->id] ?? 0) : 0;
    $sickUsed   = $sick   ? ($usedByType[$sick->id]   ?? 0) : 0;
    $casualUsed = $casual ? ($usedByType[$casual->id]  ?? 0) : 0;

    // Balances
    $annualBalance = $annual
        ? max(0, $annual->days_allowed - $annualUsed)
        : 0;
    $casualBalance = $casual
        ? max(0, $casual->days_allowed - $casualUsed)
        : 0;
    $sickBalance = $sick
        ? max(0, $sick->days_allowed - $sickUsed)
        : 0;

    // Total remaining
    $totalAllowed    = $leaveTypes->sum('days_allowed');
    $totalUsed       = $usedByType->sum();
    $remainingLeaves = max(0, $totalAllowed - $totalUsed);

    // Recent leave history — HR এর নিজের last 5
    $recentLeaves = Leave::where('requester_id', $hr->id)
        ->where('requester_type', 'hr')
        ->with('leaveType')
        ->latest()
        ->take(5)
        ->get();

    // ================= Notifications =================
    $unreadNotifications = LeaveNotification::where('recipient_type', 'hr')
        ->where('recipient_id', $hr->id)
        ->whereNull('read_at')
        ->latest()
        ->get();

    $unreadCount = $unreadNotifications->count();

    return view('hr.hr_admin', compact(
        'totalEmployees',
        'totalDepartments',
        'leaveQueue',
        'tlLeaveQueue',
        'recentActivity',
        'leaveTypes',
        'annual', 'sick', 'casual',
        'annualBalance',
        'casualBalance', 'sickBalance',
        'annualUsed',
        'sickUsed', 'casualUsed',
        'remainingLeaves',
        'recentLeaves',
        'unreadNotifications',
        'unreadCount'
    ));
}

// TL-দের leave list যেগুলো HR-এর recommendation-এর অপেক্ষায় (Super Admin-এর আগে)
public function tl_review()
{
    $leaves = Leave::where('requester_type', 'tl')
        ->where('hr_status', 'pending')
        ->with(['requester', 'leaveType'])
        ->latest()
        ->paginate(10);

    return view('hr.leave.tl_review', compact('leaves'));
}

public function tl_recommend(Request $request, Leave $leave)
{
    $request->validate(['hr_note' => 'required|string|max:500']);

    $leave->update([
        'hr_status' => 'recommended',
        'hr_note'   => $request->hr_note,
    ]);

    // এখানে চাইলে Super Admin কে notify করতে পারেন (recipient_type = 'super_admin')

    return back()->with('success', 'Team Lead leave recommended to Super Admin.');
}

public function tl_not_recommend(Request $request, Leave $leave)
{
    $request->validate(['hr_note' => 'required|string|max:500']);

    $leave->update([
        'hr_status' => 'not_recommended',
        'hr_note'   => $request->hr_note,
        'status'    => 'rejected',
    ]);

    return back()->with('success', 'Team Lead leave declined.');
}

    public function employeeDirectory()
    {
        $employees = Employee::all();
        return view('hr.employee.index', compact('employees'));
    }

     public function employee_leave()
{
    $query = Leave::with(['employee.department', 'leaveType'])->latest();

    // Filters
    if (request('leave_type_id')) {
        $query->where('leave_type_id', request('leave_type_id'));
    }

    if (request('status') && request('status') !== 'all') {
        $query->where('status', request('status'));
    }

    if (request('from') && request('to')) {
        $query->whereDate('from_date', '>=', request('from'))
              ->whereDate('to_date', '<=', request('to'));
    }

    $leaves = $query->paginate(10)->withQueryString();

    // KPI
    $pendingCount = Leave::where('status', 'pending')->count();

    $approvedTodayCount = Leave::where('status', 'approved')
        ->whereDate('updated_at', today())
        ->count();

    $onLeaveTodayCount = Leave::where('status', 'approved')
        ->whereDate('from_date', '<=', today())
        ->whereDate('to_date', '>=', today())
        ->count();

    // Currently on leave (today)
    $staffOnLeave = Leave::with('employee')
        ->where('status', 'approved')
        ->whereDate('from_date', '<=', today())
        ->whereDate('to_date', '>=', today())
        ->get();

    $leaveTypes = LeaveType::where('is_active', 1)->get();

    return view('hr.leave.index', compact(
        'leaves', 'pendingCount', 'approvedTodayCount', 'onLeaveTodayCount',
        'staffOnLeave', 'leaveTypes'
    ));
}



public function show_leave(Leave $leave)
{
    $leave->load([
        'employee.department',
        'leaveType',
    ]);

    return view('hr.leave.show', compact('leave'));
}

public function leaveHistory(Request $request)
{
    $query = Leave::with(['employee.department', 'leaveType', 'requester'])
        ->latest();

    // Filter by status
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // Filter by leave type
    if ($request->filled('leave_type_id')) {
        $query->where('leave_type_id', $request->leave_type_id);
    }

    // Filter by requester type (employee / tl)
    if ($request->filled('requester_type')) {
        $query->where('requester_type', $request->requester_type);
    }

    // Filter by date range
    if ($request->filled('from_date')) {
        $query->whereDate('from_date', '>=', $request->from_date);
    }
    if ($request->filled('to_date')) {
        $query->whereDate('to_date', '<=', $request->to_date);
    }

    // Search by employee name
    if ($request->filled('search')) {
        $search = $request->search;
        $query->whereHas('employee', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    }

    $leaves = $query->paginate(15)->withQueryString();

    $leaveTypes = LeaveType::where('is_active', 1)->get();

    return view('hr.leave.history', compact('leaves', 'leaveTypes'));
}

public function leaveHistoryExportCsv(Request $request)
{
    $query = Leave::with(['employee.department', 'leaveType', 'requester'])
        ->latest();

    // একই ফিল্টারগুলো leaveHistory() থেকে অবিকল কপি করা
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('leave_type_id')) {
        $query->where('leave_type_id', $request->leave_type_id);
    }

    if ($request->filled('requester_type')) {
        $query->where('requester_type', $request->requester_type);
    }

    if ($request->filled('from_date')) {
        $query->whereDate('from_date', '>=', $request->from_date);
    }
    if ($request->filled('to_date')) {
        $query->whereDate('to_date', '<=', $request->to_date);
    }

    if ($request->filled('search')) {
        $search = $request->search;
        $query->whereHas('employee', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    }

    // এক্সপোর্টে সব ম্যাচিং রেকর্ড লাগবে, pagination ছাড়া
    $leaves = $query->get();

    $fileName = 'leave_history_' . now()->format('Y-m-d_His') . '.csv';

    $headers = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$fileName\"",
    ];

    $callback = function () use ($leaves) {
        $file = fopen('php://output', 'w');

        // UTF-8 BOM যাতে Excel-এ বাংলা/স্পেশাল ক্যারেক্টার ঠিক দেখায়
        fputs($file, "\xEF\xBB\xBF");

        fputcsv($file, [
            'Employee Name',
            'Employee ID',
            'Department',
            'Leave Type',
            'Duration',
            'From',
            'To',
            'Requester Type',
            'TL Status',
            'Final Status',
            'Submitted',
        ]);

        foreach ($leaves as $leave) {
            fputcsv($file, [
                $leave->employee->name ?? 'N/A',
                $leave->employee->employee_id ?? 'N/A',
                $leave->employee->department->name ?? 'N/A',
                $leave->leaveType->name ?? 'N/A',
                $leave->duration . ' ' . ($leave->duration == 1 ? 'Day' : 'Days'),
                \Carbon\Carbon::parse($leave->from_date)->format('M d, Y'),
                \Carbon\Carbon::parse($leave->to_date)->format('M d, Y'),
                ucfirst($leave->requester_type ?? 'N/A'),
                ucfirst(str_replace('_', ' ', $leave->tl_status ?? 'pending')),
                ucfirst($leave->status),
                optional($leave->created_at)->format('M d, Y'),
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function destroy_leave(Leave $leave)
{
    $leave->delete();

    return redirect()->route('hr_admin.dashboard')
        ->with('success', 'Leave request deleted successfully.');
}

public function exportLeaveCsv()
{
    $query = Leave::with(['employee.department', 'leaveType'])->latest();

    if (request('leave_type_id')) {
        $query->where('leave_type_id', request('leave_type_id'));
    }

    if (request('status') && request('status') !== 'all') {
        $query->where('status', request('status'));
    }

    if (request('from') && request('to')) {
        $query->whereDate('from_date', '>=', request('from'))
              ->whereDate('to_date', '<=', request('to'));
    }

    $leaves = $query->get();

    $fileName = 'leave_requests_' . now()->format('Y-m-d_His') . '.csv';

    $headers = [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        'Pragma'              => 'no-cache',
        'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
        'Expires'             => '0',
    ];

    $callback = function () use ($leaves) {
        $file = fopen('php://output', 'w');
        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM, Excel friendly

        fputcsv($file, [
            'Employee Name',
            'Designation',
            'Department',
            'Leave Type',
            'From Date',
            'To Date',
            'Duration (Days)',
            'Reason',
            'TL Status',
            'TL Note',
            'Final Status',
            'HR Note',
            'Submitted At',
        ]);

        foreach ($leaves as $leave) {
            fputcsv($file, [
                $leave->employee->name ?? '',
                $leave->employee->designation ?? '',
                $leave->employee->department->name ?? '',
                $leave->leaveType->name ?? '',
                \Carbon\Carbon::parse($leave->from_date)->format('Y-m-d'),
                \Carbon\Carbon::parse($leave->to_date)->format('Y-m-d'),
                $leave->duration,
                $leave->reason,
                $leave->tl_status,
                $leave->tl_note,
                $leave->status,
                $leave->hr_note,
                optional($leave->created_at)->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($file);
    };

    return response()->streamDownload($callback, $fileName, $headers);
}




    public function teamLeadAssignment()
    {
        $employees = Employee::all();
        return view('hr.teamlead.index', compact('employees'));
    }

  public function requestChange(Request $request, $employeeId)
{
    $employee = Employee::findOrFail($employeeId);
    $action   = $request->input('action'); // 'assign' | 'modify' | 'remove'
    $deptIds  = $request->input('department_ids', []);
 
    // Don't allow duplicate pending requests for the same employee
    $existing = Notification::where('type', 'tl_assignment_request')
        ->where('employee_id', $employee->id)
        ->where('status', 'pending')
        ->first();
 
    if ($existing) {
        return back()->with('info', 'A request for this employee is already pending approval.');
    }
 
    // For assign/modify: block if any selected department is already led by someone else
    if ($action !== 'remove') {
        $conflicts = Department::whereIn('id', $deptIds)
            ->whereNotNull('hod_id')
            ->where('hod_id', '!=', $employee->id)
            ->with('hod:id,name')
            ->get();
 
        if ($conflicts->isNotEmpty()) {
            $names = $conflicts->map(fn($d) => "{$d->name} (already led by {$d->hod->name})")->implode(', ');
            return back()->with('error', "Cannot request: {$names}.");
        }
    }
 
    Notification::create([
        'type'        => 'tl_assignment_request',
        'employee_id' => $employee->id,
        'status'      => 'pending',
        'data'        => json_encode([
            'action'         => $action,
            'department_ids' => $action === 'remove' ? [] : $deptIds,
        ]),
    ]);
 
    return back()->with('success', "Team Lead {$action} request submitted for approval.");
}
 
/**
 * HR cancels their own pending request before admin acts on it.
 */
public function cancelRequest($notificationId)
{
    $notification = Notification::where('type', 'tl_assignment_request')
        ->where('status', 'pending')
        ->findOrFail($notificationId);
 
    $notification->delete();
 
    return back()->with('info', 'The pending request has been cancelled.');
}

public function notificationsIndex()
{
    $hr = auth('Hr')->user();

    $notifications = LeaveNotification::where('recipient_type', 'hr')
        ->where('recipient_id', $hr->id)
        ->with('leave.employee')
        ->latest()
        ->paginate(15);

    return view('hr.notifications.index', compact('notifications'));
}

public function markNotificationRead($id)
{
    $notification = LeaveNotification::find($id);

    if ($notification) {
        $notification->update(['read_at' => now()]);
    }

    return response()->json(['success' => true]);
}

public function markAllNotificationsRead()
{
    $hr = auth('Hr')->user();

    LeaveNotification::where('recipient_type', 'hr')
        ->where('recipient_id', $hr->id)
        ->whereNull('read_at')
        ->update(['read_at' => now()]);

    return back()->with('success', 'All notifications marked as read.');
}

public function employee_activity()
{
    $filter = request('filter', 'all');

    $leaveActivities = Leave::with('employee')
        ->whereIn('status', ['approved', 'rejected'])
        ->orWhere('tl_status', 'recommended')
        ->orWhere('tl_status', 'not_recommended')
        ->get()
        ->map(function ($leave) {
            $title = $leave->employee->name ?? 'Employee';

            if ($leave->status === 'approved') {
                $desc = 'Leave request approved';
                $icon = 'bi-check-circle-fill';
                $type = 'approved';
            } elseif ($leave->status === 'rejected') {
                $desc = 'Leave request rejected';
                $icon = 'bi-x-circle-fill';
                $type = 'rejected';
            } elseif ($leave->tl_status === 'recommended') {
                $desc = 'Leave recommended by Team Lead, awaiting final approval';
                $icon = 'bi-hourglass-split';
                $type = 'pending';
            } elseif ($leave->tl_status === 'not_recommended') {
                $desc = 'Leave declined by Team Lead';
                $icon = 'bi-x-circle';
                $type = 'rejected';
            } else {
                $desc = 'Leave status updated';
                $icon = 'bi-info-circle';
                $type = 'info';
            }

            return (object) [
                'category'   => 'leave',
                'type'       => $type,
                'icon'       => $icon,
                'title'      => $title,
                'desc'       => $desc,
                'approver'   => $leave->approver_type ?? null,
                'created_at' => $leave->updated_at,
            ];
        });

    $generalActivities = \App\Models\Notification::get()
        ->map(function ($notif) {
            $titleMap = [
                'tl_assignment_request' => 'Team Lead Assignment',
                'employee_creation'     => 'New Employee',
            ];
            $iconMap = [
                'pending'  => 'bi-hourglass-split',
                'approved' => 'bi-check-circle-fill',
                'rejected' => 'bi-x-circle-fill',
            ];

            return (object) [
                'category'   => $notif->type,
                'type'       => $notif->status,
                'icon'       => $iconMap[$notif->status] ?? 'bi-bell',
                'title'      => $titleMap[$notif->type] ?? 'System Update',
                'desc'       => $notif->message,
                'approver'   => null,
                'created_at' => $notif->updated_at,
            ];
        });

    $allActivities = $leaveActivities->concat($generalActivities)->sortByDesc('created_at')->values();

    // Filter apply করো
    $filteredActivities = match($filter) {
        'leave'      => $allActivities->where('category', 'leave')->values(),
        'assignment' => $allActivities->where('category', 'tl_assignment_request')->values(),
        'employee'   => $allActivities->where('category', 'employee_creation')->values(),
        default      => $allActivities,
    };

    $perPage     = 15;
    $currentPage = request()->get('page', 1);
    $activities  = new \Illuminate\Pagination\LengthAwarePaginator(
        $filteredActivities->forPage($currentPage, $perPage),
        $filteredActivities->count(),
        $perPage,
        $currentPage,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    $totalActivities  = $allActivities->count();
    $leaveCount       = $leaveActivities->count();
    $assignmentCount  = $generalActivities->where('category', 'tl_assignment_request')->count();
    $employeeAddCount = $generalActivities->where('category', 'employee_creation')->count();

    return view('hr.employee_activity.index', compact(
        'activities',
        'totalActivities',
        'leaveCount',
        'assignmentCount',
        'employeeAddCount',
        'filter'
    ));
}

public function approve(Leave $leave)
{
    $leave->update(['status' => 'approved']);

    return redirect()->back()->with('success', 'Leave request approved successfully.');
}

public function reject(Request $request, Leave $leave)
{
    $leave->update([
        'status'  => 'rejected',
        'hr_note' => $request->hr_note,
    ]);

    return redirect()->back()->with('error', 'Leave request rejected.');
}
}