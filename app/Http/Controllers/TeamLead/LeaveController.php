<?php

namespace App\Http\Controllers\TeamLead;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\LeaveNotification;
use App\Models\HrAdmin;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index()
    {
        $tl = auth('Tl')->user();

        $leaves = Leave::where('requester_type', 'tl')
            ->where('requester_id', $tl->id)
            ->with('leaveType')
            ->latest()
            ->paginate(10);

        return view('teamlead.leave.index', compact('leaves'));
    }

    public function create()
    {
        $leaveTypes = LeaveType::where('is_active', 1)->get();
        return view('team_lead.leave.create', compact('leaveTypes'));
    }

    public function store(Request $request)
    {
        $tl = auth('tl')->user();

        $existingPending = Leave::where('requester_type', 'tl')
            ->where('requester_id', $tl->id)
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
    'employee_id'    => $tl->employee_id, // duplicate লাইন বাদ দেওয়া হলো
    'requester_type' => 'tl',
    'requester_id'   => $tl->id,
    'leave_type_id'  => $validated['leave_type_id'],
    'from_date'      => $validated['from_date'],
    'to_date'        => $validated['to_date'],
    'duration'       => $duration,
    'reason'         => $validated['reason'],
    'attachment'     => $attachmentPath,
    'tl_status'      => 'recommended', // ← নতুন: TL এর নিজের leave, recommend ধাপ প্রযোজ্য না, তাই সরাসরি HR queue তে যাবে
    'tl_note'        => 'Self-applied by Team Lead — no recommendation needed.',
    'status'         => 'pending',
    'hr_status'      => 'pending',
]);

        // সব active HR কে notify
        HrAdmin::role('Hr_admin')->get()->each(function ($hr) use ($leave, $tl) {
            LeaveNotification::create([
                'leave_id'       => $leave->id,
                'recipient_type' => 'hr',
                'recipient_id'   => $hr->id,
                'type'           => 'leave_request',
                'message'        => $tl->name . ' (Team Lead) has submitted a leave request awaiting your recommendation.',
            ]);
        });

        return redirect()->route('team_lead.dashboard')
            ->with('success', 'Leave request submitted successfully.');
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