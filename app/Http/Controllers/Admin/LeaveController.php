<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\LeaveNotification;
use App\Models\User; // Super Admin model — নাম ভিন্ন হলে এখানে বদলাবেন
use Illuminate\Http\Request;
use Carbon\Carbon;

class LeaveController extends Controller
{
    private function getView(string $view): string
    {
        if (auth('Hr')->check()) {
            return 'hr.' . $view;
        }
        return 'admin.' . $view;
    }

    private function getRoute(string $route): string
    {
        if (auth('Hr')->check()) {
            return 'hrm.' . $route;
        }
        return 'admin.' . $route;
    }

    // ======================================================
    //  HR নিজের leave আবেদন করবে এই অংশ দিয়ে (index/create/store)
    //  Super Admin এখানে নিজের leave নেয় না — শুধু TL/HR page দেখবে
    // ======================================================

    public function index()
    {
        abort_unless(auth('Hr')->check(), 403, 'Only HR can view their own leave requests here.');

        $hr = auth('Hr')->user();

        $leaves = Leave::where('requester_type', 'hr')
            ->where('requester_id', $hr->id)
            ->with('leaveType')
            ->latest()
            ->paginate(10);

        return view($this->getView('leave.index'), compact('leaves'));
    }

   public function create()
{
    abort_unless(auth('Hr')->check(), 403, 'Only HR can submit a leave request here.');

    $hrId = auth('Hr')->id();

    $leaveTypes = LeaveType::where('is_active', 1)->get();

    $usedByType = Leave::where('employee_id', $hrId)
        ->where('status', 'approved')
        ->selectRaw('leave_type_id, SUM(duration) as used_days')
        ->groupBy('leave_type_id')
        ->pluck('used_days', 'leave_type_id');

    $pendingByType = Leave::where('employee_id', $hrId)
        ->where('status', 'pending')
        ->selectRaw('leave_type_id, SUM(duration) as pending_days')
        ->groupBy('leave_type_id')
        ->pluck('pending_days', 'leave_type_id');

    $totalAllowed    = $leaveTypes->sum('days_allowed');
    $totalUsed       = $usedByType->sum();
    $remainingLeaves = max(0, $totalAllowed - $totalUsed);

    $annual = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'annual'));
    $sick   = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'sick'));
    $casual = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'casual'));

    $annualUsed    = $annual ? ($usedByType[$annual->id] ?? 0) : 0;
    $annualBalance = $annual ? max(0, $annual->days_allowed - $annualUsed) : 0;

    $sickUsed   = $sick   ? ($usedByType[$sick->id] ?? 0)   : 0;
    $casualUsed = $casual ? ($usedByType[$casual->id] ?? 0) : 0;

    return view($this->getView('leave.create'), compact(
        'leaveTypes', 'remainingLeaves',
        'annual', 'sick', 'casual',
        'annualBalance', 'annualUsed', 'casualUsed',
        'usedByType', 'pendingByType'
    ));
}

    public function store(Request $request)
    {
        abort_unless(auth('Hr')->check(), 403, 'Only HR can submit a leave request here.');

        $hr = auth('Hr')->user();

        $existingPending = Leave::where('requester_type', 'hr')
            ->where('requester_id', $hr->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingPending) {
            return back()->withInput()->with(
                'error',
                'You already have a pending leave request. Please wait until it is approved or rejected.'
            );
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'from_date'     => 'required|date',
            'to_date'       => 'required|date|after_or_equal:from_date',
            'reason' => [
                'required', 'string', 'max:1000',
                function ($attribute, $value, $fail) {
                    if (str_word_count(trim($value)) > 5) {
                        $fail('The reason must contain at least 5 words.');
                    }
                },
            ],
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('leave_attachments', 'public')
            : null;

        $duration = $this->calculateDuration($validated['from_date'], $validated['to_date']);

        $leave = Leave::create([
            'employee_id'    => null, // HR কোনো employee রেকর্ডের সাথে যুক্ত নয়
            'requester_type' => 'hr',
            'requester_id'   => $hr->id,
            'leave_type_id'  => $validated['leave_type_id'],
            'from_date'      => $validated['from_date'],
            'to_date'        => $validated['to_date'],
            'duration'       => $duration,
            'reason'         => $validated['reason'],
            'attachment'     => $attachmentPath,
            'status'         => 'pending',
            'hr_status'      => null, // এই ধাপ প্রযোজ্য না, সরাসরি Super Admin এ যাবে
        ]);

        $this->notifySuperAdmin($leave, $hr->name . ' (HR)');

        return redirect()
            ->route($this->getRoute('dashboard'))
            ->with('success', 'Leave request submitted successfully.');
    }

    // ======================================================
    //  HR এর কাছে আসা TL-এর leave review (শুধু HR guard)
    // ======================================================

    public function pendingTlLeaves()
    {
        abort_unless(auth('Hr')->check(), 403);

        $leaves = Leave::where('requester_type', 'tl')
            ->where('hr_status', 'pending')
            ->with(['leaveType', 'requester'])
            ->latest()
            ->paginate(10);

        return view($this->getView('leave.tl_review'), compact('leaves'));
    }

    public function recommend(Request $request, Leave $leave)
    {
        abort_unless(auth('Hr')->check(), 403);
        abort_if($leave->requester_type !== 'tl' || $leave->hr_status !== 'pending', 403);

        $request->validate(['hr_note' => 'nullable|string|max:500']);

        $leave->update([
            'hr_status' => 'recommended',
            'hr_note'   => $request->hr_note,
        ]);

        $tlName = optional($leave->requester)->name ?? 'A team lead';
        $this->notifySuperAdmin($leave, $tlName . ' (Team Lead), recommended by HR');

        return back()->with('success', 'Leave recommended and forwarded to Super Admin.');
    }

    public function rejectTlLeave(Request $request, Leave $leave)
    {
        abort_unless(auth('Hr')->check(), 403);
        abort_if($leave->requester_type !== 'tl' || $leave->hr_status !== 'pending', 403);

        $request->validate(['hr_note' => 'required|string|max:500']);

        $leave->update([
            'hr_status' => 'rejected',
            'hr_note'   => $request->hr_note,
            'status'    => 'rejected', // HR reject করলে এখানেই শেষ, Super Admin পর্যন্ত যাবে না
        ]);

        return back()->with('success', 'Leave request rejected.');
    }

    // ======================================================
    //  Super Admin এর final approve/reject (শুধু Admin guard,
    //  HR guard থেকে অ্যাক্সেসযোগ্য না)
    // ======================================================

    public function pending()
    {
        abort_if(auth('Hr')->check(), 403, 'Only Super Admin can access this page.');

        $leaves = Leave::where('status', 'pending')
            ->where(function ($q) {
                $q->where('requester_type', 'hr')
                  ->orWhere(function ($q2) {
                      $q2->where('requester_type', 'tl')
                         ->where('hr_status', 'recommended');
                  });
            })
            ->with(['leaveType', 'requester'])
            ->latest()
            ->paginate(10);

        return view('admin.leave.pending', compact('leaves'));
    }

    public function approve(Request $request, Leave $leave)
    {
        abort_if(auth('Hr')->check(), 403, 'Only Super Admin can approve leave requests.');

        $this->authorizeFinalDecision($leave);

        $request->validate(['admin_note' => 'nullable|string|max:500']);

        $leave->update([
            'status'     => 'approved',
            'admin_note' => $request->admin_note,
        ]);

        $this->notifyRequester($leave, 'approved');

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, Leave $leave)
    {
        abort_if(auth('Hr')->check(), 403, 'Only Super Admin can reject leave requests.');

        $this->authorizeFinalDecision($leave);

        $request->validate(['admin_note' => 'required|string|max:500']);

        $leave->update([
            'status'     => 'rejected',
            'admin_note' => $request->admin_note,
        ]);

        $this->notifyRequester($leave, 'rejected');

        return back()->with('success', 'Leave request rejected.');
    }

    // ======================================================
    //  helpers
    // ======================================================

    protected function authorizeFinalDecision(Leave $leave): void
    {
        abort_if($leave->status !== 'pending', 403, 'This leave request is already finalized.');

        if ($leave->requester_type === 'tl') {
            abort_if(
                $leave->hr_status !== 'recommended',
                403,
                'This TL leave request has not been recommended by HR yet.'
            );
        }
    }

    protected function notifySuperAdmin(Leave $leave, string $requesterLabel): void
    {
        // একজনই Super Admin ধরে নিয়ে role দিয়ে খুঁজছি
        User::role('Admin')->get()->each(function ($admin) use ($leave, $requesterLabel) {
            LeaveNotification::create([
                'leave_id'       => $leave->id,
                'recipient_type' => 'super_admin',
                'recipient_id'   => $admin->id,
                'type'           => 'leave_request',
                'message'        => $requesterLabel . ' has a leave request awaiting your approval.',
            ]);
        });
    }

    protected function notifyRequester(Leave $leave, string $decision): void
    {
        LeaveNotification::create([
            'leave_id'       => $leave->id,
            'recipient_type' => $leave->requester_type, // employee/tl/hr
            'recipient_id'   => $leave->requester_id,
            'type'           => 'leave_' . $decision,
            'message'        => "Your leave request has been {$decision} by Super Admin.",
        ]);
    }

    protected function calculateDuration($from, $to): int
    {
        $from = Carbon::parse($from);
        $to   = Carbon::parse($to);
        $duration = 0;
        $current = $from->copy();
        while ($current <= $to) {
            if (!$current->isWeekend()) $duration++;
            $current->addDay();
        }
        return $duration;
    }
}