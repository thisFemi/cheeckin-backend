<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ChecksPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAttendancePolicyRequest;
use App\Http\Requests\Admin\UpdateAttendancePolicyRequest;
use App\Http\Resources\AttendancePolicyResource;
use App\Models\AttendancePolicy;
use App\Models\UserAttendancePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\Permission;
class AttendancePolicyController extends Controller
{
    use ChecksPermissions;
    /**
     * Display a listing of the resource.
     */
     public function index(Request $request): JsonResponse
    {
        $user=$request->user();

        if ($error = $this->requirePermission($request, Permission::MANAGE_POLICIES)) return $error;

        
        
        $policies = AttendancePolicy::where('organization_id',   $user->organization_id)
            ->latest()
            ->get();

        return response()->json([
            "message" => 'Attendance policies retrieved successfully',
             'data' => [
            'policies' => AttendancePolicyResource::collection($policies),]
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateAttendancePolicyRequest $request)
    {
         $user=$request->user();
      if ($error = $this->requirePermission($request, Permission::MANAGE_POLICIES)) return $error;

        

       $policy = AttendancePolicy::create([
            ...$request->validated(),
            'organization_id' =>   $user->organization_id,
        ]);

        return response()->json([
            'message' => 'Attendance policy created.',
            'data' => [
                'policy' => new AttendancePolicyResource($policy),
            ]
        ], 201);
    }
        
     
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
       if ($error = $this->requirePermission($request, Permission::MANAGE_POLICIES)) return $error;

        
        $policy = AttendancePolicy::where('id', $id)
            ->where('organization_id',   $user->organization_id)
            ->firstOrFail();

        return response()->json([
        'message' => 'Attendance policy retrieved successfully',
         'data' => [
             
        'policy' => new AttendancePolicyResource($policy)]]);
    }

  public function update(UpdateAttendancePolicyRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($error = $this->requirePermission($request, Permission::MANAGE_POLICIES)) return $error;

        $policy = AttendancePolicy::where('id', $id)
            ->where('organization_id',   $user->organization_id)
            ->firstOrFail();

        $policy->update($request->validated());

        return response()->json([
            'message' => 'Policy updated.',
            'data'=> [
            'policy'  => new AttendancePolicyResource($policy->fresh()),
        ]]);
    }

    
  public function destroy(Request $request, int $id): JsonResponse
    {
        if ($error = $this->requirePermission($request, Permission::MANAGE_POLICIES)) return $error;

        $policy = AttendancePolicy::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        // Prevent deletion if staff are currently assigned to this policy
        $staffCount = UserAttendancePolicy::where('attendance_policy_id', $id)->count();
        if ($staffCount > 0) {
            return response()->json([
                'message' => "Cannot delete. {$staffCount} staff member(s) are assigned to this policy.",
            ], 422);
        }

        $policy->delete();

        return response()->json(['message' => 'Policy deleted.']);
    }
}
