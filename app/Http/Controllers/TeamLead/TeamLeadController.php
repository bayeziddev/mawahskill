<?php

namespace App\Http\Controllers\TeamLead;

use App\Http\Controllers\Controller;
use App\Models\Tl;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;

use App\Models\LeaveNotification;
use Illuminate\Http\Request;

class TeamLeadController extends Controller
{
   public function dashboard()
{
    $tl = auth('tl')->user();

    // FIX: আগে এখানে $tl->employee->department_id (শুধু নিজের department)
    // ব্যবহার হচ্ছিল। কিন্তু একজন TL একাধিক department এর HOD হতে পারে
    // (departments টেবিলের hod_id অনুযায়ী)। তাই সব department বের করতে হবে।
    $departmentIds = Department::where('hod_id', $tl->employee_id)->pluck('id');

    $members = Employee::whereIn('department_id', $departmentIds)->get();

    $leaveRequests = Leave::whereHas('employee', function ($q) use ($departmentIds) {
            $q->whereIn('department_id', $departmentIds);
        })
        ->where('tl_status', 'pending')
         ->where('requester_type', 'employee')   
        ->with(['employee', 'leaveType'])
        ->latest()
        ->paginate(5);

    $totalMembers  = $members->count();
    $activeOnLeave = $members->where('status', 'on_leave')->count();
    $pendingCount  = $leaveRequests->total();

    // Unread notifications for this TL
    $tlUnreadNotifications = LeaveNotification::where('recipient_type', 'tl')
        ->where('recipient_id', $tl->id)
        ->whereNull('read_at')
        ->latest()
        ->get();

    $tlUnreadCount = $tlUnreadNotifications->count();

    // ================= TL's own leave balance (like employee/HR dashboard) =================
    $leaveTypes = LeaveType::where('is_active', 1)->get();

    // Approved leave — type অনুযায়ী কতদিন নেওয়া হয়েছে
    $usedByType = Leave::where('employee_id', $tl->employee_id)
        ->where('requester_type', 'tl')
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

    // Total remaining — সব type এর allowed - used
    $totalAllowed    = $leaveTypes->sum('days_allowed');
    $totalUsed       = $usedByType->sum();
    $remainingLeaves = max(0, $totalAllowed - $totalUsed);

    // Recent leave history — TL এর নিজের last 5
    $recentLeaves = Leave::where('employee_id', $tl->employee_id)
        ->where('requester_type', 'tl')
        ->with('leaveType')
        ->latest()
        ->take(5)
        ->get();

    return view('team_lead.dashboard', compact(
        'leaveRequests',
        'members',
        'totalMembers',
        'activeOnLeave',
        'pendingCount',
        'tlUnreadNotifications',
        'tlUnreadCount',
        'leaveTypes',
        'annual', 'sick', 'casual',
        'annualBalance', 'casualBalance', 'sickBalance',
        'annualUsed', 'sickUsed', 'casualUsed',
        'remainingLeaves',
        'recentLeaves'
    ));
}

    public function memberIndex()
    {
        $tl = auth('tl')->user();

        // tl.employee_id যেটা employees.id কে point করে
        $employeeId = $tl->employee_id;

        // এই employee যে যে department-এর hod, সেগুলোর id বের করো
        $departmentIds = Department::where('hod_id', $employeeId)->pluck('id');

        // ঐ সব department-এর সব member (team_lead বাদে)
        $members = Employee::whereIn('department_id', $departmentIds)
            ->where('role', '!=', 'team_lead')
            ->latest()
            ->get();

        $departments = Department::whereIn('id', $departmentIds)->get();

        return view('team_lead.members.index', compact('members', 'departments'));
    }

    public function recommend_index()
    {
        $tl = auth('tl')->user();

        // TL এর single department_id এর বদলে HOD হিসেবে assign সব departments
        $departmentIds = Department::where('hod_id', $tl->employee_id)
            ->pluck('id');

        $query = Leave::with(['employee.department', 'leaveType'])
            ->whereHas('employee', function ($q) use ($departmentIds) {
                $q->whereIn('department_id', $departmentIds);
            });

        // Filter
        $filter = request('filter', 'pending');

        if ($filter === 'pending') {
            $query->where('tl_status', 'pending');
        } elseif ($filter === 'recommended') {
            $query->where('tl_status', 'recommended');
        } elseif ($filter === 'declined') {
            $query->where('tl_status', 'not_recommended');
        }

        $leaves = $query->latest()->paginate(15);

        // KPI counts
        $baseQuery = Leave::whereHas('employee', function ($q) use ($departmentIds) {
            $q->whereIn('department_id', $departmentIds);
        });

        $pendingCount     = (clone $baseQuery)->where('tl_status', 'pending')->count();
        $recommendedCount = (clone $baseQuery)->where('tl_status', 'recommended')->count();
        $declinedCount    = (clone $baseQuery)->where('tl_status', 'not_recommended')->count();

        return view('team_lead.recommend.index', compact(
            'leaves', 'filter', 'pendingCount', 'recommendedCount', 'declinedCount'
        ));
    }

    public function recommend(Request $request, Leave $leave)
    {
        $request->validate([
            'tl_note' => 'required|string|max:500',
        ]);

        $leave->update([
            'tl_status' => 'recommended',
            'tl_note'   => $request->tl_note,
        ]);

        // HR কে notification পাঠাও — এখন HR এর final approval এর জন্য রেডি
        $hrAdmin = \App\Models\HrAdmin::first();

        if ($hrAdmin) {
            LeaveNotification::create([
                'leave_id'       => $leave->id,
                'recipient_type' => 'hr',
                'recipient_id'   => $hrAdmin->id,
                'type'           => 'leave_recommended',
                'message'        => ($leave->employee->name ?? 'An employee') . '\'s leave has been recommended by TL and awaits your approval.',
            ]);
        }

        return back()->with('success', 'Leave recommended successfully.');
    }

    public function notRecommend(Request $request, Leave $leave)
    {
        $request->validate([
            'tl_note' => 'required|string|max:500',
        ]);

        $leave->update([
            'tl_status' => 'not_recommended',
            'tl_note'   => $request->tl_note,
            'status'    => 'rejected', // TL declined → auto reject, HR এর কাছে যাবে না
        ]);

        return back()->with('success', 'Leave declined.');
    }

    public function show(Leave $leave)
    {
        $leave->load(['employee', 'leaveType']);
        return view('team_lead.leave.show', compact('leave'));
    }

    public function markNotificationRead($id)
    {
        $notification = \App\Models\LeaveNotification::find($id);

        if ($notification) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead()
    {
        $tl = auth('tl')->user();

        \App\Models\LeaveNotification::where('recipient_type', 'tl')
            ->where('recipient_id', $tl->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function notificationsIndex()
    {
        $tl = auth('tl')->user();

        $notifications = \App\Models\LeaveNotification::where('recipient_type', 'tl')
            ->where('recipient_id', $tl->id)
            ->with('leave.employee')
            ->latest()
            ->paginate(15);

        return view('team_lead.notifications.index', compact('notifications'));
    }
}