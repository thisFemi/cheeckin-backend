<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignLeaveRequest;
use App\Http\Requests\Admin\AssignPolicyRequest;
use App\Http\Requests\Admin\CreateStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Http\Resources\AttendancePolicyResource;
use App\Http\Resources\UserResource;
use App\Models\AttendancePolicy;
use App\Models\User;
use App\Models\UserAttendancePolicy;
use App\Models\Organization;
use App\Models\LeaveType;
use App\Models\UserLeaveEntitlement;
use App\Notifications\StaffWelcomeNotification;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StaffController extends Controller
{
     public function index(Request $request): JsonResponse
    {
        $query = User::where('organization_id', $request->user()->organization_id)
            ->where('user_type', 'employee')
            ->with(['role', 'attendancePolicy'])
            ->latest();

        // Filters
        if ($request->filled('department'))
            $query->where('department', $request->department);

        if ($request->filled('status'))
            $query->where('employment_status', $request->status);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhere('email',      'like', "%{$search}%")
                  ->orWhere('employee_id','like', "%{$search}%");
            });
        }

        return response()->json([
            'message' => 'Staff retrieved successfully',
            'data' => [
                'staff' => UserResource::collection($query->paginate(20)),
            ]
        ]);
    }

    public function store(CreateStaffRequest $request): JsonResponse
    {
      // dd($request->all(), $request->getContent());
           $user = $request->user();
        if( !$user->canDo('manage_users')){
            return response()->json(['message' => 'Forbidden'], 403);
        }
      
        // Generate a temporary password
        $tempPassword = Str::random(10);

        $staff = User::create([
            'organization_id'   => $user->organization_id,
            'first_name'        => $request->first_name,
            'last_name'         => $request->last_name,
            'email'             => $request->email,
            'password'          => Hash::make($tempPassword),
            'phone'             => $request->phone,
            'employee_id'       => $request->employee_id,
            'department'        => $request->department,
            'position'          => $request->position,
            'joined_date'       => $request->joined_date,
            'role_id'           => $request->role_id,
            'user_type'         => 'employee',
            'face_template'     => null,    // Always null — set by employee on first login
            'employment_status' => 'active',
            'requires_face_setup' => $request->requires_face_setup??true, 
            
        ]);

        // Attach attendance policy if provided
        if ($request->filled('attendance_policy_id')) {
            UserAttendancePolicy::updateOrCreate(
                ['user_id' => $staff->id],
                ['attendance_policy_id' => $request->attendance_policy_id]
            );
        }

        // Attach leave types for current year if provided
        if ($request->filled('leave_type_ids')) {
            $year = now()->year;
            foreach ($request->leave_type_ids as $leaveTypeId) {
                $leaveType = LeaveType::find($leaveTypeId);
                if ($leaveType) {
                    UserLeaveEntitlement::updateOrCreate(
                        ['user_id' => $staff->id, 'leave_type_id' => $leaveTypeId, 'year' => $year],
                        ['entitled_days' => $leaveType->days_per_year]
                    );
                }
            }
        }

        // Send welcome email with org code + temp password
        $organization = Organization::find($user->organization_id);
        $staff->notify(new StaffWelcomeNotification($organization, $tempPassword));

        return response()->json([
            'message' => 'Staff account created. Welcome email sent.',
            'data' => [
                'staff' => new UserResource($staff->load('role'))
            ]
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $staff = User::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->where('user_type', 'employee')
            ->with(['role', 'attendancePolicy', 'leaveEntitlements.leaveType'])
            ->firstOrFail();

        return response()->json([
          'message' => 'Staff account retrieved successfully',
              'data' => [    
        'staff' => new UserResource($staff)]]);
    }

     public function update(UpdateStaffRequest $request, int $id): JsonResponse
    {
              $user = $request->user();
        if( !$user->canDo('manage_users')){
            return response()->json(['message' => 'Forbidden'], 403);
        }
      
        $staff = User::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->where('user_type', 'employee')
            ->firstOrFail();

        $staff->update($request->validated());

        return response()->json([
            'message' => 'Staff updated.',
            'data'=>[
            'staff'   => new UserResource($staff->fresh(['role'])),
        ]]);
    }
     public function destroy(Request $request, int $id): JsonResponse
    {
              $user = $request->user();
        if( !$user->canDo('manage_users')){
            return response()->json(['message' => 'Forbidden'], 403);
        }
      
        $staff = User::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->where('user_type', 'employee')
            ->firstOrFail();

        // Soft deactivate rather than hard delete to preserve history
        $staff->update(['employment_status' => 'inactive']);
        $staff->tokens()->delete(); // Force logout
        $staff->delete();           // Soft delete via SoftDeletes trait

        return response()->json(['message' => 'Staff account deactivated.']);
    }

     public function assignPolicy(AssignPolicyRequest $request, int $id): JsonResponse
    {
              $user = $request->user();
        if( !$user->canDo('manage_users')){
            return response()->json(['message' => 'Forbidden'], 403);
        }
      
        $staff = User::where('id', $id)
            ->where('organization_id', $user->organization_id)
            ->where('user_type', 'employee')
            ->firstOrFail();

        UserAttendancePolicy::updateOrCreate(
            ['user_id' => $staff->id],
            ['attendance_policy_id' => $request->attendance_policy_id]
        );

        $policy = AttendancePolicy::find($request->attendance_policy_id);

        return response()->json([
            'message' => "Attendance policy '{$policy->name}' assigned to {$staff->first_name}.",
            'policy'  => new AttendancePolicyResource($policy),
        ]);
    }


public function assignLeave(AssignLeaveRequest $request, int $id): JsonResponse
{
        $user = $request->user();
        if( !$user->canDo('manage_users')){
            return response()->json(['message' => 'Forbidden'], 403);
        }

    $staff = User::where('id', $id)
        ->where('organization_id', $user->organization_id)
        ->where('user_type', 'employee')
        ->firstOrFail();

    $year    = now()->year;
    $summary = [];

    // Load all requested leave types in one query instead of one per loop
    $leaveTypeIds = collect($request->leave_types)->pluck('leave_type_id');

    $leaveTypes = LeaveType::whereIn('id', $leaveTypeIds)
        ->where('organization_id',  $user->organization_id)
        ->get()
        ->keyBy('id'); // key by ID so we can access them directly

    foreach ($request->leave_types as $entry) {
        $leaveType = $leaveTypes->get($entry['leave_type_id']);

        // Skip silently if leave type not found or belongs to another org
        if (!$leaveType) continue;
         $entitledDays = $leaveType->days_per_year ?? 0;


        UserLeaveEntitlement::updateOrCreate(
            [
                'user_id'       => $staff->id,
                'leave_type_id' => $leaveType->id,
                'year'          => $year,
            ],
            [
                'entitled_days' => $entitledDays,
            ]
        );

        $summary[] = [
            'leave_type_id'   => $leaveType->id,
            'leave_type_name' => $leaveType->name,
            'leave_type_code' => $leaveType->code,
            'entitled_days'   => $entitledDays,
        ];
    }

    if (empty($summary)) {
        return response()->json([
            'message' => 'No valid leave types found. Please check the leave_type_ids.',
        ], 422);
    }

    return response()->json([
        'message'  => 'Leave entitlements assigned.',
        'data' => [
            'staff_id' => $staff->id,
            'year'     => $year,
            'assigned' => $summary,
        ],
    ]);
}


}
