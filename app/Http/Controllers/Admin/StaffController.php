<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignLeaveRequest;
use App\Http\Requests\Admin\AssignPolicyRequest;
use App\Http\Requests\Admin\CreateStaffRequest;
use App\Http\Requests\Admin\DeassignLeaveRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Http\Resources\AttendancePolicyResource;
use App\Http\Resources\UserResource;
use App\Models\AttendancePolicy;
use App\Models\Role;
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
            'require_password_reset' =>true,
            
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
    public function assignRole(Request $request, int $id): JsonResponse{
   $user = $request->user();
       if (!$user->canDo('manage_staff')) {
        return response()->json(['message' => 'Forbidden. You do not have permission to manage staff.'], 403);
    }
         $staff = User::where('id', $id)
            ->where('organization_id', $user->organization_id)
            ->where('user_type', 'employee')
            ->firstOrFail();
        
    $previousRole = $staff->role?->name;

    $staff->update(['role_id' => $request->role_id]);

    $newRole = Role::with('permissions')->find($request->role_id);
   

   return response()->json([
        'message'       => "Role assigned to {$staff->first_name} {$staff->last_name}.",
        'data'=>[
        'previous_role' => $previousRole,
        'new_role'      => [
            'id'          => $newRole->id,
            'name'        => $newRole->name,
            'permissions' => $newRole->permissions
                                ->where('pivot.allowed', true)
                                ->pluck('slug'),
        ],
    ]]);
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
            
        $existingAssignment = UserAttendancePolicy::where('user_id', $staff->id)->first();
  if ($existingAssignment) {
        $currentPolicy = $existingAssignment->attendancePolicy;

        return response()->json([
            'message'        => "This staff member is already assigned to a policy. Please deassign them first before assigning a new one.",
            'current_policy' => [
                'id'   => $currentPolicy->id,
                'name' => $currentPolicy->name,
            ],
            //'hint' => "Call .../api/admin/staff/{$staff->id}/deassign-policy to remove the current policy.",
        ], 422);
    }
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
        public function deassignPolicy(Request $request, int $id): JsonResponse
    {
            $user = $request->user();
            if( !$user->canDo('manage_users')){
                return response()->json(['message' => 'Forbidden'], 403);
            }
        $staff = User::where('id', $id)
            ->where('organization_id',  $user->organization_id)
            ->where('user_type', 'employee')
            ->firstOrFail();

        $policy = UserAttendancePolicy::where('user_id', $staff->id)->first();

        if (!$policy) {
            return response()->json([
                'message' => 'This staff member has no attendance policy assigned.',
            ], 422);
        }

        $policyName = $policy->attendancePolicy->name;
        $policy->delete();

        return response()->json([
            'message' => "Attendance policy '{$policyName}' removed from {$staff->first_name} {$staff->last_name}.",
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

    public function deassignLeave(DeassignLeaveRequest $request, int $id): JsonResponse
    {
            $user = $request->user();
            if( !$user->canDo('manage_users')){
                return response()->json(['message' => 'Forbidden'], 403);
            }

        $staff = User::where('id', $id)
            ->where('organization_id',  $user->organization_id)
            ->where('user_type', 'employee')
            ->firstOrFail();

        $year         = $request->integer('year', now()->year);
        $leaveTypeIds = $request->leave_type_ids;

        // Load matching entitlements for this staff in one query
        $entitlements = UserLeaveEntitlement::where('user_id', $staff->id)
            ->where('year', $year)
            ->whereIn('leave_type_id', $leaveTypeIds)
            ->get();

        if ($entitlements->isEmpty()) {
            return response()->json([
                'message' => 'No matching leave entitlements found for this staff member.',
            ], 422);
        }

        // Load leave type names for the response summary
        $leaveTypes = LeaveType::whereIn('id', $entitlements->pluck('leave_type_id'))
            ->get()
            ->keyBy('id');

        $removed = [];

        foreach ($entitlements as $entitlement) {
            $leaveType = $leaveTypes->get($entitlement->leave_type_id);
            $removed[] = [
                'leave_type_id'   => $entitlement->leave_type_id,
                'leave_type_name' => $leaveType?->name,
                'leave_type_code' => $leaveType?->code,
                'year'            => $year,
            ];
            $entitlement->delete();
        }

        return response()->json([
            'message'  => 'Leave entitlements removed.',
            "data" => [
            'staff_id' => $staff->id,
            'year'     => $year,
            'removed'  => $removed,
        ]]);
    }


    }
