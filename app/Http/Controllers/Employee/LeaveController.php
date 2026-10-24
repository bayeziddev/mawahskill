<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\LeaveNotification;
use App\Models\Tl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    public function index()
    {
        $leaves = Leave::where('employee_id', auth('employee')->id())
            ->with('leaveType')
            ->latest()
            ->paginate(10);

        return view('employee.leave.index', compact('leaves'));
    }

    public function create()
    {
        $employeeId = auth('employee')->id();

        $leaveTypes = LeaveType::where('is_active', 1)->get();

        $usedByType = Leave::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->selectRaw('leave_type_id, SUM(duration) as used_days')
            ->groupBy('leave_type_id')
            ->pluck('used_days', 'leave_type_id');

        $pendingByType = Leave::where('employee_id', $employeeId)
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

        return view('employee.leave.create', compact(
            'leaveTypes', 'remainingLeaves',
            'annual', 'sick', 'casual',
            'annualBalance', 'annualUsed', 'casualUsed',
            'usedByType', 'pendingByType'
        ));
    }

    public function store(Request $request)
    {
        $employee = auth('employee')->user();

        // check pending
        $existingPending = Leave::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingPending) {
            return back()
                ->withInput()
                ->with('error', 'You already have a pending leave request. Please wait until it is approved or rejected before submitting a new one.');
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'from_date'     => 'required|date',
            'to_date'       => 'required|date|after_or_equal:from_date',
            'reason' => [
                'required',
                'string',
                'max:1000',
                function ($attribute, $value, $fail) {
                    $wordCount = str_word_count(trim($value));
                    if ($wordCount > 5) {
                        $fail('The reason must contain at least 5 words.');
                    }
                },
            ],
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'public');
        }

        $from     = \Carbon\Carbon::parse($validated['from_date']);
        $to       = \Carbon\Carbon::parse($validated['to_date']);
        $duration = 0;
        $current  = $from->copy();
        while ($current <= $to) {
            if (!$current->isWeekend()) $duration++;
            $current->addDay();
        }

        $leave = Leave::create([
            'employee_id'   => $employee->id,
             'requester_type' => 'employee',   // ← নতুন লাইন
    'requester_id'   => $employee->id, // ← নতুন লাইন
            'leave_type_id' => $validated['leave_type_id'],
            'from_date'     => $validated['from_date'],
            'to_date'       => $validated['to_date'],
            'duration'      => $duration,
            'reason'        => $validated['reason'],
            'attachment'    => $attachmentPath,
            'status'        => 'pending',
            'tl_status'     => 'pending',
        ]);

        // ── Department এর hod_id থেকে সরাসরি TL খুঁজুন ──
        // (Tl model এর উপর নির্ভর না করে — departments.hod_id ই source of truth)
        $department = $employee->department;

        if ($department && $department->hod_id) {
            LeaveNotification::create([
                'leave_id'       => $leave->id,
                'recipient_type' => 'tl',
                'recipient_id'   => $department->hod_id, // employee id সরাসরি
                'type'           => 'leave_request',
                'message'        => $employee->name . ' has submitted a leave request for your review.',
            ]);
        }

        return redirect()
            ->route('employee.dashboard')
            ->with('success', 'Leave request submitted successfully.');
    }


    public function edit(Leave $leave)
    {
        $employee = auth('employee')->user();

        // অন্য কারো leave edit করার চেষ্টা ঠেকান
        abort_if($leave->employee_id !== $employee->id, 403);

        $this->authorizeEditWindow($leave);

        $leaveTypes = LeaveType::where('is_active', 1)->get();

        return view('employee.leave.edit', compact('leave', 'leaveTypes'));
    }

    protected function authorizeEditWindow(Leave $leave)
    {
        if ($leave->status !== 'pending') {
            abort(403, 'Only pending leave requests can be edited.');
        }

        if ($leave->to_date && \Carbon\Carbon::parse($leave->to_date)->isPast()) {
            abort(403, 'Cannot edit a leave request for past dates.');
        }

        return true;
    }

    public function update(Request $request, Leave $leave)
{
    $employee = auth('employee')->user();

    abort_if($leave->employee_id !== $employee->id, 403);

    $this->authorizeEditWindow($leave);

    $validated = $request->validate([
        'leave_type_id' => 'required|exists:leave_types,id',
        'from_date'     => 'required|date',
        'to_date'       => 'required|date|after_or_equal:from_date',
        'reason' => [
            'required',
            'string',
            'max:1000',
            function ($attribute, $value, $fail) {
                $wordCount = str_word_count(trim($value));
                if ($wordCount > 5) {
                    $fail('The reason must contain at least 5 words.');
                }
            },
        ],
        'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
    ]);

    if ($request->hasFile('attachment')) {
        // পুরনো attachment থাকলে মুছে ফেলুন
        if ($leave->attachment) {
            \Storage::disk('public')->delete($leave->attachment);
        }
        $validated['attachment'] = $request->file('attachment')->store('leave_attachments', 'public');
    }

    $from     = \Carbon\Carbon::parse($validated['from_date']);
    $to       = \Carbon\Carbon::parse($validated['to_date']);
    $duration = 0;
    $current  = $from->copy();
    while ($current <= $to) {
        if (!$current->isWeekend()) $duration++;
        $current->addDay();
    }
    $validated['duration'] = $duration;

    $leave->update($validated);

    return redirect()
        ->route('employee.dashboard')
        ->with('success', 'Leave request updated successfully.');
}
}
