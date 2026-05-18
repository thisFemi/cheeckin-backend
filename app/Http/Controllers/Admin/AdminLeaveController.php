<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectLeaveRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Notifications\LeaveStatusNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResource;

class AdminLeaveController extends Controller
{
    
public function index(Request $request): JsonResponse
    {
        $query = LeaveRequest::where('organization_id', $request->user()->organization_id)
            ->with(['user', 'leaveType', 'approvedBy'])
            ->latest();

        if ($request->filled('status'))  $query->where('status', $request->status);
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('leave_type_id')) $query->where('leave_type_id', $request->leave_type_id);
        if ($request->filled('month'))   $query->whereMonth('start_date', $request->month);
        if ($request->filled('year'))    $query->whereYear('start_date', $request->year);

        return response()->json([
            'message' => 'Leave requests retrieved successfully',
            'data' => [
                'leave_requests' => LeaveRequestResource::collection($query->paginate(20)),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->with(['user', 'leaveType', 'approvedBy'])
            ->firstOrFail();

        return response()->json([
            'message' => 'Leave request retrieved successfully',
            'data' => [
                'leave_request' => new LeaveRequestResource($leaveRequest),
            ],
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
            $user = $request->user();
        if( !$user->canDo('approve_or_reject_leave')){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $leaveRequest = LeaveRequest::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->where('status', 'pending')
            ->firstOrFail();

        $leaveRequest->update([
            'status'      => 'approved',
            'approved_by' => $request->user()->id,
            'actioned_at' => now(),
        ]);

        // Mark those days as on_leave in attendance records
        $this->markAttendanceAsOnLeave($leaveRequest);

        // Notify employee
        $leaveRequest->user->notify(new LeaveStatusNotification($leaveRequest, 'approved'));

        return response()->json([
            'message'       => 'Leave request approved.',
            'data'          => [
                 
            'leave_request' => new LeaveRequestResource($leaveRequest->fresh(['user', 'leaveType'])),
        ]]);
    }

    public function reject(RejectLeaveRequest $request, int $id): JsonResponse
    {
            $user = $request->user();
        if( !$user->canDo('approve_or_reject_leave')){
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $leaveRequest = LeaveRequest::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->where('status', 'pending')
            ->firstOrFail();

        $leaveRequest->update([
            'status'           => 'rejected',
            'approved_by'      => $request->user()->id,
            'rejection_reason' => $request->rejection_reason,
            'actioned_at'      => now(),
        ]);

        // Notify employee
        $leaveRequest->user->notify(new LeaveStatusNotification($leaveRequest, 'rejected'));

        return response()->json([
            'message'       => 'Leave request rejected.',
            'data'          => [
            'leave_request' => new LeaveRequestResource($leaveRequest->fresh(['user', 'leaveType'])),
        ]]);
    }

        private function markAttendanceAsOnLeave(LeaveRequest $leaveRequest): void
            {
                $current = Carbon::parse($leaveRequest->start_date);
                $end     = Carbon::parse($leaveRequest->end_date);

                while ($current->lte($end)) {
                    if ($current->isWeekday()) {
                        AttendanceRecord::updateOrCreate(
                            [
                                'user_id' => $leaveRequest->user_id,
                                'date'    => $current->toDateString(),
                            ],
                            [
                                'organization_id' => $leaveRequest->organization_id,
                                'status'          => 'on_leave',
                                'admin_note'      => "Approved leave: {$leaveRequest->leaveType->name}",
                            ]
                        );
                    }
                    $current->addDay();
                }
            }

}
