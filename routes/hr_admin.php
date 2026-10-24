<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\HrAdmin\HrAdminController; 
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\LeaveController;
use App\Http\Controllers\ProfileController;



Route::get('/hr_admin/dashboard', [HrAdminController::class, 'dashboard'])->name('hr_admin.dashboard');
 Route::get('/hr_admin/profile', [ProfileController::class, 'profile'])->name('hr_admin.profile');
  Route::get('/hr_admin/profile/edit', [ProfileController::class, 'edit'])->name('hr_profile.edit');
  Route::put('/hr_admin/profile', [ProfileController::class, 'update'])->name('hr_profile.update');
Route::post('/hr_admin/logout', [AdminAuthController::class, 'logout'])->name('hr_admin.logout');

// Employee Management Routes for HR Admin

Route::get('/hr_admin/employee-directory', [HrAdminController::class, 'employeeDirectory'])->name('hr_admin.employee_directory');
Route::get('/hr_admin/employee/create',[EmployeeController::class,'create'])->name('hr_admin.employee.create');
Route::get('/hr_admin/employee',[EmployeeController::class,'index'])->name('hr_admin.employee.index');
Route::post('/hr_admin/employee',[EmployeeController::class,'store'])->name('hr_admin.employee.store');
Route::get('/hr_admin/employee/{employee}', [EmployeeController::class, 'show'])->name('hr_admin.employee.show');
Route::get('/hr_admin/employee/{employee}/export-csv', [EmployeeController::class, 'empexportCsv'])
    ->name('hr_admin.employee.export.csv');
Route::get('/hr_admin/employee/{employee}/edit', [EmployeeController::class, 'edit'])->name('hr_admin.employee.edit');
Route::put('/hr_admin/employee/{employee}', [EmployeeController::class, 'update'])->name('hr_admin.employee.update');
Route::delete('/hr_admin/employee/{employee}', [EmployeeController::class, 'destroy'])->name('hr_admin.employee.destroy');

Route::get('/hr_admin/leave',[LeaveController::class,'create'])->name('hr_admin.leave.create');
 Route::post('/hr_admin/leave',       [LeaveController::class, 'store'])->name('leave.store');

  Route::get('/leave/history', [HrAdminController::class, 'leaveHistory'])->name('hr_admin.leave.history');
  Route::get('/leave/history/export-csv', [HrAdminController::class, 'leaveHistoryExportCsv'])->name('hr_admin.leave.history.export.csv');
 
    // // HR এর কাছে আসা TL এর leave review (recommend / reject)
    // Route::get('/hr_admin/leave/tl-review',                  [LeaveController::class, 'pendingTlLeaves'])->name('leave.tl_review');
    // Route::post('/hr_admin/leave/{leave}/recommend',         [LeaveController::class, 'recommend'])->name('leave.recommend');
    // Route::post('/hr_admin/leave/{leave}/reject-tl',         [LeaveController::class, 'rejectTlLeave'])->name('leave.reject_tl');

Route::get('/hrm/leave/tl-review', [HrAdminController::class, 'tl_review'])->name('hrm.leave.tl_review');
Route::patch('/hrm/leave/{leave}/tl-recommend', [HrAdminController::class, 'tl_recommend'])->name('hrm.leave.tl_recommend');
Route::patch('/hrm/leave/{leave}/tl-not-recommend', [HrAdminController::class, 'tl_not_recommend'])->name('hrm.leave.tl_not_recommend');

Route::get('/employee/export-csv', [EmployeeController::class, 'exportCsv'])->name('hr_admin.employee.export-csv');


 Route::get('/hr_admin/employee-leave', [HrAdminController::class, 'employee_leave'])->name('hr_admin.employee_leave.index');
Route::get('/leave/{leave}', [HrAdminController::class, 'show_leave'])
    ->name('hr.leave.show');
    Route::delete('/leave/{leave}', [HrAdminController::class, 'destroy_leave'])->name('hr.leave.destroy');

 Route::get('/leave/export-csv', [HrAdminController::class, 'exportLeaveCsv'])->name('hr_admin.leave.export-csv');
// Team Lead Assignment Route for HR Admin
Route::get('/hr_admin/teamlead-assignment', [HrAdminController::class, 'teamLeadAssignment'])->name('hr_admin.teamlead_assignment');
Route::post('/hr_admin/tl-assignment/{employee}/toggle', [EmployeeController::class, 'toggleTeamLead'])->name('hr_admin.teamlead_assignment.assign');
    Route::delete('/hr_admin/tl-request/{id}/cancel', [EmployeeController::class, 'cancelTlRequest'])->name('hr_admin.tl-request.cancel');


// Hr approval
Route::patch('/leave/{leave}/approve', [HrAdminController::class, 'approve'])->name('hr.leave.approve');
Route::patch('/leave/{leave}/reject',  [HrAdminController::class, 'reject'])->name('hr.leave.reject');

// HR Notifications
Route::patch('/hr_admin/notifications/{id}/read', [HrAdminController::class, 'markNotificationRead'])
    ->name('hr_admin.notifications.read');

Route::patch('/hr_admin/notifications/mark-all-read', [HrAdminController::class, 'markAllNotificationsRead'])
    ->name('hr_admin.notifications.mark-all-read');

Route::get('/hr_admin/notifications', [HrAdminController::class, 'notificationsIndex'])
    ->name('hr_admin.notifications.index');

        Route::get('/hr_admin/employee-activity', [HrAdminController::class, 'employee_activity'])->name('hr_admin.employee_activity.index');