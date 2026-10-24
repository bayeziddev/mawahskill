<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
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

   

    public function index()
    {
        $employees = Employee::with('department')->latest()->paginate(8);
        $departments = Department::orderBy('name')->get();
        return view($this->getView('employee.index'), compact('employees', 'departments'));
    }

    public function create() 
    {
        $departments = Department::orderBy('name')->get();
        return view($this->getView('employee.create'), compact('departments'));
    }

  public function store(Request $request)
{
    $validated = $request->validate([
        'name'                           => 'required|string|max:255',
        'email'                          => 'required|email|unique:employees,email',
        'phone'                          => 'nullable|string|max:255',
        'employee_id'                    => 'nullable|string|max:255|unique:employees,employee_id',
        'nid'                            => 'required|string|max:255|unique:employees,nid',
        'blood_group' => [
    'required',
    'string',
    Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])
],
        'profile_picture'                => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'date_of_birth'                  => 'nullable|date',
        'gender'                         => 'nullable|in:male,female,other',
        'address'                        => 'nullable|string|max:255',
        'emergency_contact_name'         => 'nullable|string|max:255',
        'emergency_contact_phone'        => 'nullable|string|max:255',
        'emergency_contact_relationship' => 'nullable|string|max:255',
        'department_id'                  => 'required|exists:departments,id',
        'designation'                    => 'required|string|max:255',
        'role'                           => 'nullable|string|max:255',
        'hire_date'                      => 'nullable|date',
        'password'                       => 'nullable|string|min:8',
        'status'                         => 'nullable|in:pending,active,inactive',
    ]);

    if ($request->hasFile('profile_picture')) {
        $validated['profile_picture'] = $request->file('profile_picture')->store('employees', 'public');
    }

    if (!empty($validated['password'])) {
        $validated['password'] = Hash::make($validated['password']);
    }

    if (auth('Hr')->check()) {
        $validated['status'] = 'pending';
    } else {
        $validated['status'] = $validated['status'] ?? 'active';
    }

    $employee = Employee::create($validated);

    if (isset($validated['role']) && $validated['role'] === 'hr_admin') {
        $hrAdmin = \App\Models\HrAdmin::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
            'role'     => 'hr_admin',
        ]);

        $hrAdmin->assignRole('hr_admin');
    }
    if (isset($validated['role']) && $validated['role'] === 'team_lead') {
        $teamLead = \App\Models\Tl::create([
              'employee_id' => $employee->id,
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
            'role'     => 'team_lead',
        ]);

        $teamLead->assignRole('team_lead');
    }
   if (isset($validated['role']) && $validated['role'] === 'employee') {
   $employee= \App\Models\EmployeeUser::create([
        'employee_id'   => $employee->id,
        'name'          => $validated['name'],
        'email'         => $validated['email'],
        'password'      => $validated['password'],
        'role'          => 'employee',
        'department_id' => $validated['department_id'],
    ]);

}

    

    if (auth('Hr')->check()) {
        \App\Models\Notification::create([
            'type'         => 'employee_creation',
            'employee_id'  => $employee->id,
            'requested_by' => auth('Hr')->id(),
            'message'      => auth('Hr')->user()->name . ' has created a new employee: ' . $validated['name'],
            'status'       => 'pending',
        ]);
    }

    return view($this->getView('employee.index'))->with('success', 'Employee created successfully.');
}

 public function show(Employee $employee)
{
    $employee->load('department');

    // ================= Leave balance for this employee =================
    $leaveTypes = LeaveType::where('is_active', 1)->get();

    $usedByType = Leave::where('employee_id', $employee->id)
        ->where('status', 'approved')
        ->selectRaw('leave_type_id, SUM(duration) as used_days')
        ->groupBy('leave_type_id')
        ->pluck('used_days', 'leave_type_id');

    $annual = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'annual'));
    $sick   = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'sick'));
    $casual = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'casual'));

    $annualUsed = $annual ? ($usedByType[$annual->id] ?? 0) : 0;
    $sickUsed   = $sick   ? ($usedByType[$sick->id]   ?? 0) : 0;
    $casualUsed = $casual ? ($usedByType[$casual->id]  ?? 0) : 0;

    $annualBalance = $annual ? max(0, $annual->days_allowed - $annualUsed) : 0;
    $casualBalance = $casual ? max(0, $casual->days_allowed - $casualUsed) : 0;
    $sickBalance   = $sick   ? max(0, $sick->days_allowed - $sickUsed)     : 0;

    $totalAllowed    = $leaveTypes->sum('days_allowed');
    $totalUsed       = $usedByType->sum();
    $remainingLeaves = max(0, $totalAllowed - $totalUsed);

    // ================= Leave history (paginated) =================
    $leaves = Leave::where('employee_id', $employee->id)
        ->with('leaveType')
        ->latest()
        ->paginate(10, ['*'], 'leave_page'); // custom page name to avoid conflict if other paginators exist on same page

    return view($this->getView('employee.show'), compact(
        'employee', 'leaves', 'leaveTypes',
        'annual', 'sick', 'casual',
        'annualBalance', 'casualBalance', 'sickBalance',
        'annualUsed', 'sickUsed', 'casualUsed',
        'remainingLeaves'
    ));
}

public function empexportCsv(Employee $employee)
{
    $employee->load('department');

    $leaveTypes = LeaveType::where('is_active', 1)->get();

    $usedByType = Leave::where('employee_id', $employee->id)
        ->where('status', 'approved')
        ->selectRaw('leave_type_id, SUM(duration) as used_days')
        ->groupBy('leave_type_id')
        ->pluck('used_days', 'leave_type_id');

    $annual = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'annual'));
    $sick   = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'sick'));
    $casual = $leaveTypes->first(fn($t) => str_contains(strtolower($t->name), 'casual'));

    $annualUsed = $annual ? ($usedByType[$annual->id] ?? 0) : 0;
    $sickUsed   = $sick   ? ($usedByType[$sick->id]   ?? 0) : 0;
    $casualUsed = $casual ? ($usedByType[$casual->id]  ?? 0) : 0;

    $annualBalance = $annual ? max(0, $annual->days_allowed - $annualUsed) : 0;
    $casualBalance = $casual ? max(0, $casual->days_allowed - $casualUsed) : 0;
    $sickBalance   = $sick   ? max(0, $sick->days_allowed - $sickUsed)     : 0;

    $totalAllowed    = $leaveTypes->sum('days_allowed');
    $totalUsed       = $usedByType->sum();
    $remainingLeaves = max(0, $totalAllowed - $totalUsed);

    $leaves = Leave::where('employee_id', $employee->id)
        ->with('leaveType')
        ->latest()
        ->get();

    $fileName = 'employee_' . $employee->employee_id . '_report.csv';

    $headers = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$fileName\"",
    ];

    $callback = function () use (
        $employee, $leaves,
        $annualUsed, $sickUsed, $casualUsed,
        $annualBalance, $casualBalance, $sickBalance,
        $remainingLeaves
    ) {
        $file = fopen('php://output', 'w');

        // UTF-8 BOM (Excel-এ বাংলা/স্পেশাল ক্যারেক্টার ঠিকভাবে দেখানোর জন্য)
        fputs($file, "\xEF\xBB\xBF");

        // ===== Profile Section =====
        fputcsv($file, ['EMPLOYEE PROFILE']);
        fputcsv($file, [
            'Employee ID', 'Name', 'Email', 'Phone', 'Department',
            'Designation', 'Role', 'Status', 'Hire Date', 'Gender',
            'Blood Group', 'Address',
        ]);
        fputcsv($file, [
            $employee->employee_id,
            $employee->name,
            $employee->email,
            $employee->phone,
            $employee->department->name ?? 'N/A',
            $employee->designation,
            $employee->role,
            $employee->status,
            $employee->hire_date,
            $employee->gender,
            $employee->blood_group,
            $employee->address,
        ]);

        fputcsv($file, []); // ফাঁকা লাইন (সেকশন আলাদা করার জন্য)

        // ===== Leave Summary Section =====
        fputcsv($file, ['LEAVE SUMMARY']);
        fputcsv($file, ['Leave Type', 'Used Days', 'Balance']);
        fputcsv($file, ['Annual', $annualUsed, $annualBalance]);
        fputcsv($file, ['Sick', $sickUsed, $sickBalance]);
        fputcsv($file, ['Casual', $casualUsed, $casualBalance]);
        fputcsv($file, ['Total Remaining', '', $remainingLeaves]);

        fputcsv($file, []); // ফাঁকা লাইন

        // ===== Leave History Section =====
        fputcsv($file, ['LEAVE HISTORY']);
        fputcsv($file, ['Leave Type', 'Start Date', 'End Date', 'Duration', 'Status', 'Reason']);

        foreach ($leaves as $leave) {
            fputcsv($file, [
                $leave->leaveType->name ?? 'N/A',
                $leave->start_date,
                $leave->end_date,
                $leave->duration,
                $leave->status,
                $leave->reason,
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    public function edit(Employee $employee)
    {
        $departments = Department::orderBy('name')->get();
        return view($this->getView('employee.edit'), compact('employee', 'departments'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name'                           => 'required|string|max:255',
            'email'                          => 'required|email|unique:employees,email,' . $employee->id,
            'phone'                          => 'nullable|string|max:255',
            'employee_id'                    => 'nullable|string|max:255|unique:employees,employee_id,' . $employee->id,
            'nid'                            => 'required|string|max:16|unique:employees,nid,' . $employee->id,
             'blood_group'                    => [
            'required',
            'string',
            Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
        ],
            'profile_picture'                => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'date_of_birth'                  => 'nullable|date',
            'gender'                         => 'nullable|in:male,female,other',
            'address'                        => 'nullable|string|max:255',
            'emergency_contact_name'         => 'nullable|string|max:255',
            'emergency_contact_phone'        => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'department_id'                  => 'required|exists:departments,id',
            'designation'                    => 'required|string|max:255',
            'role'                           => 'nullable|string|max:255',
            'hire_date'                      => 'nullable|date',
            'password'                       => 'nullable|string|min:8',
            'status'                         => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = $request->file('profile_picture')->store('employees', 'public');
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $employee->update($validated);

        return redirect()->route($this->getRoute('employee.index'))->with('success', 'Employee updated successfully.');
    }

    

    public function destroy(Employee $employee)
    {
        DB::table('notifications')->where('employee_id', $employee->id)->delete();
    
    $employee->delete();
    
    return redirect()->route($this->getRoute('employee.index'))
        ->with('success', 'Employee deleted successfully.');
    }

    public function exportCsv()
    {

    
        $fileName = 'employees.csv';
        $employees = Employee::with('department')->latest()->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($employees) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Employee ID', 'NID', 'Name', 'Email', 'Phone',
                'Department', 'Designation', 'Role', 'Hire Date', 'Status',
                'Address', 'Emergency Contact Name', 'Emergency Contact Phone',
                'Emergency Contact Relationship', 'Created At',
            ]);

            foreach ($employees as $employee) {
                fputcsv($file, [
                    $employee->employee_id,
                    $employee->nid,
                    $employee->name,
                    $employee->email,
                    $employee->phone,
                    $employee->department->name ?? '',
                    $employee->designation,
                    $employee->role,
                    $employee->hire_date,
                    $employee->status,
                    $employee->address,
                    $employee->emergency_contact_name,
                    $employee->emergency_contact_phone,
                    $employee->emergency_contact_relationship,
                    optional($employee->created_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }

public function toggleTeamLead(Request $request, Employee $employee)
{
    $departmentIds = array_values(array_filter(
        (array) $request->input('department_ids', [])
    ));

    if (auth('Hr')->check()) {
        if (empty($departmentIds)) {
            return back()->with('error', 'Please select at least one department.');
        }

        $existing = \App\Models\Notification::where('type', 'tl_assignment_request')
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return back()->with('info', 'Request already pending for this employee.');
        }

        $departmentNames = \App\Models\Department::whereIn('id', $departmentIds)
            ->pluck('name')
            ->toArray();

       \App\Models\Notification::create([
    'type'           => 'tl_assignment_request',
    'employee_id'    => $employee->id,
    'requested_by'   => auth('Hr')->id(),
    'action'         => $request->input('action', 'assign'), // ← এই লাইনটা যোগ করুন
    'message'        => auth('Hr')->user()->name
        . ' wants to assign ' . $employee->name
        . ' as Team Lead for: ' . implode(', ', $departmentNames),
    'status'         => 'pending',
    'department_ids' => json_encode($departmentIds),
]);

        return back()->with('success', 'Request sent to Admin for approval.');
    }

    $makeTeamLead = $employee->role !== 'team_lead';

    $employee->update([
        'role' => $makeTeamLead ? 'team_lead' : 'employee',
    ]);

    $this->syncTeamLeadRecord($employee, $makeTeamLead);

    \App\Models\Department::where('hod_id', $employee->id)->update(['hod_id' => null]);

    if ($makeTeamLead && !empty($departmentIds)) {
        \App\Models\Department::whereIn('id', $departmentIds)
            ->update(['hod_id' => $employee->id]);
    }

    return back()->with('success', 'Employee role updated successfully.');
}

public function approveTlRequest(int $id)
{
    $notification  = \App\Models\Notification::findOrFail($id);
    $employee      = Employee::findOrFail($notification->employee_id);
    $departmentIds = json_decode($notification->department_ids ?? '[]', true);

    $employee->update(['role' => 'team_lead']);
    $this->syncTeamLeadRecord($employee, true); // ← যোগ করা হলো

    \App\Models\Department::where('hod_id', $employee->id)->update(['hod_id' => null]);
    if (!empty($departmentIds)) {
        \App\Models\Department::whereIn('id', $departmentIds)->update(['hod_id' => $employee->id]);
    }

    $notification->update(['status' => 'approved', 'read_at' => now()]);

    return back()->with('success', 'TL request approved.');
}
public function cancelTlRequest(int $id)
{
    \App\Models\Notification::where('id', $id)
        ->where('status', 'pending')
        ->delete();

    return back()->with('success', 'TL request cancelled.');
}

// public function approveTlRequest(int $id)
// {
//     $notification = \App\Models\Notification::findOrFail($id);
//     $employee = Employee::findOrFail($notification->employee_id);
//     $departmentIds = json_decode($notification->department_ids ?? '[]', true);

//     $employee->update(['role' => 'team_lead']);
//     $this->syncTeamLeadRecord($employee, true);

//     \App\Models\Department::where('hod_id', $employee->id)->update(['hod_id' => null]);
//     if (!empty($departmentIds)) {
//         \App\Models\Department::whereIn('id', $departmentIds)->update(['hod_id' => $employee->id]);
//     }

//     $notification->update(['status' => 'approved', 'read_at' => now()]);

//     return back()->with('success', 'TL request approved.');
// }

public function rejectTlRequest(int $id)
{
    $notification = \App\Models\Notification::findOrFail($id);
    $notification->update(['status' => 'rejected', 'read_at' => now()]);

    return back()->with('success', 'TL request rejected.');
}

private function syncTeamLeadRecord(Employee $employee, bool $makeTeamLead)
{
    if ($makeTeamLead) {
        \App\Models\Tl::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'name'     => $employee->name,
                'email'    => $employee->email,
                'password' => $employee->password ?? Hash::make('changeme123'),
                'role'     => 'team_lead',
            ]
        );
    } else {
        \App\Models\Tl::where('employee_id', $employee->id)->delete();
    }
}
    public function EmployeeCreationIndex(int $id)
        {
            $employee = Employee::findOrFail($id);
            return view($this->getView('employee.employee_request'), compact('employee'));
        }

    public function EmployeeCreationView(Employee $employee)
    {
        $employee = Employee::findOrFail($employee->id);
        return view($this->getView('employee.employee_request_view'), compact('employee'));
    }
    

  

    public function approve(Employee $employee)
    {
         $employee->update(['status' => 'active']);

    \App\Models\Notification::where('employee_id', $employee->id)
        ->where('type', 'employee_creation')
        ->where('status', 'pending')
        ->update(['status' => 'approved', 'read_at' => now()]);

    return back()->with('success', $employee->name . ' approved successfully.');
    }

    public function reject(Employee $employee)
    {
         $employee->update(['status' => 'inactive']);

    \App\Models\Notification::where('employee_id', $employee->id)
        ->where('type', 'employee_creation')
        ->where('status', 'pending')
        ->update(['status' => 'rejected', 'read_at' => now()]);

    return back()->with('success', $employee->name . ' rejected.');
    }
}
