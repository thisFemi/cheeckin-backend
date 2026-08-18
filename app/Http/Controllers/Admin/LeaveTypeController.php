<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Concerns\ChecksPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateLeaveTypeRequest;
use App\Http\Requests\Admin\UpdateLeaveTypeRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    use ChecksPermissions;

    public function index(Request $request): JsonResponse
    {
        $types = LeaveType::where('organization_id', $request->user()->organization_id)
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Leave types retrieved successfully',
            'data' => [
                'leave_types' => LeaveTypeResource::collection($types)]]);
    }

    public function store(CreateLeaveTypeRequest $request): JsonResponse
    {

        if ($error = $this->requirePermission($request, Permission::MANAGE_LEAVE)) {
            return $error;
        }

        $leaveType = LeaveType::create([
            ...$request->validated(),
            'organization_id' => $request->user()->organization_id,
        ]);

        return response()->json([
            'message' => 'Leave type created.',
            'data' => [
                'leave_type' => new LeaveTypeResource($leaveType), ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {

        $leaveType = LeaveType::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        return response()->json(['leave_type' => new LeaveTypeResource($leaveType)]);
    }

    public function update(UpdateLeaveTypeRequest $request, int $id): JsonResponse
    {
        if ($error = $this->requirePermission($request, Permission::MANAGE_LEAVE)) {
            return $error;
        }

        $leaveType = LeaveType::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        $leaveType->update($request->validated());

        return response()->json([
            'message' => 'Leave type updated.',
            'data' => [
                'leave_type' => new LeaveTypeResource($leaveType->fresh()),
            ],
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {

        if ($error = $this->requirePermission($request, Permission::MANAGE_LEAVE)) {
            return $error;
        }

        $leaveType = LeaveType::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        // Block deletion if leave requests exist under this type
        if (LeaveRequest::where('leave_type_id', $id)->exists()) {
            return response()->json([
                'message' => 'Cannot delete. Leave requests exist under this type. Deactivate it instead.',
            ], 422);
        }

        $leaveType->delete();

        return response()->json(['message' => 'Leave type deleted.']);
    }
}
