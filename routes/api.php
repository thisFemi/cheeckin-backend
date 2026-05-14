<?php

use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminLeaveController;
use App\Http\Controllers\Admin\AttendancePolicyController;
use App\Http\Controllers\Admin\LeaveTypeController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Employee\EmployeeAttendanceController;
use App\Http\Controllers\Employee\EmployeeLeaveController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Owner\AdminController;
use App\Http\Controllers\Owner\OrganizationController;
use App\Http\Controllers\Owner\RoleController;

use Illuminate\Support\Facades\Route;

Route::get('hello', function(){
 print("Server Running... ");
});

//-Public
Route::prefix('auth')->group(function(){

    Route::post('register/owner',    [AuthController::class,'registerOwner']);                                     //Done
    Route::post('login',             [AuthController::class,'login']);                                             //Done
    Route::post('forgot-password',   [AuthController::class,'forgotPassword']);                                    //Done
    Route::post('reset-password',    [AuthController::class,'resetPassword']);                                     //Done
    });


//-Authenticated
Route::middleware('auth:sanctum')->group(function(){
    Route::post('auth/logout', [AuthController::class,'logout']);                                                   //Done

//-Owner    

    Route::middleware('owner')->prefix('owner')->group(function(){
        Route::get('organization',      [OrganizationController::class, 'show'] );                                   //Done   
        Route::put('organization',      [OrganizationController::class,'update']);                                   //Done
        Route::get('organization/code', [OrganizationController::class,'orgCode']);                                  //Done
        Route::post('organization/regenerate-code', [OrganizationController::class,'regenerateOrgCode']);            //Done

        
        Route::apiResource('admins', AdminController::class);                                                        //Done
                                                     

        });


//-Admin

    Route::middleware('admin')->prefix('admin')->group(function(){
       //   Route::post('roles',  [RoleController::class, 'showTest']);  
        Route::apiResource('roles',  RoleController::class);                                                         //Done                                             
        Route::apiResource('attendance-policies',    AttendancePolicyController::class);                             //Done
        Route::apiResource('leave-types',            LeaveTypeController::class);                                    //Done

        Route::apiResource('staff', StaffController::class);
        //Route::post('register/staff', [AuthController::class,'registerEmployee']);   //Done
        Route::post('staff/{id}/assign-policy',  [StaffController::class,'assignPolicy']);
        Route::post('staff/{id}/assign-leave',   [StaffController::class,'assignLeave']);


        Route::get('attendance',              [AdminAttendanceController::class,'index']);
        Route::get('attendance/summary',      [AdminAttendanceController::class,'summary']);
        Route::get('attendance/{id}',         [AdminAttendanceController::class,'show']);
        Route::patch('attendance/{id}',       [AdminAttendanceController::class,'patch']);
        
        
        
        Route::get('leave-requests',                 [AdminLeaveController::class,'index']);
        Route::get('leave-requests/{id}',            [AdminLeaveController::class,'show']); 
        Route::patch('leave-requests/{id}/approve',  [AdminLeaveController::class,'approve']);
        Route::patch('leave-requests/{id}/reject',   [AdminLeaveController::class,'reject']);
        
        });

    //-Employee
    
    Route::middleware('employee')->prefix('employee')->group(function(){
        Route::post('attendance/check-in',      [EmployeeAttendanceController::class,'checkIn'] );
        Route::post('attendance/check-out',     [EmployeeAttendanceController::class,'checkOut'] );
        Route::get('attendance/today',          [EmployeeAttendanceController::class,'today']);
        Route::get('attendance/history',        [EmployeeAttendanceController::class,'history']);
        Route::get('attendance',                [EmployeeAttendanceController::class,'index']);
        Route::get('attendance/{id}',           [EmployeeAttendanceController::class,'show']);
    
        Route::get('leave-types',            [EmployeeLeaveController::class,'leaveTypes']);
        Route::get('leave-balance',          [EmployeeLeaveController::class,'leaveBalance']);
        Route::apiResource('leave-requests', EmployeeLeaveController::class)->only(['index','store','show','destroy']);


        Route::put('profile',                   [ProfileController::class,'update']);
        Route::put('profile/password',          [ProfileController::class,'changePasword']);
        Route::get('notifications',             [ProfileController::class,'notifications']);
        Route::get('profile',                   [ProfileController::class,'show']);
        Route::patch('notifications/{id}/read', [ProfileController::class,'markNotificationRead']);
        });



});