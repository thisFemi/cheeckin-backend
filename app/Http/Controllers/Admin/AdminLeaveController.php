<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectLeaveRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Notifications\LeaveStatusNotification;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class AdminLeaveController extends Controller
{
     public function __construct(
        private LeaveService $leaveService
    ) {}
    
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

         $leaveRequests = $query->paginate(20);

          $data = $leaveRequests->through(function ($leaveRequest) {
        $year      = $leaveRequest->start_date->year;
        $employee  = $leaveRequest->user;
        $leaveType = $leaveRequest->leaveType;

        $remaining = $this->leaveService->remainingDays($employee, $leaveType, $year);

         $leaveRequest->employee_balance = [
            'entitled_days'       => $this->leaveService->entitledDays($employee, $leaveType, $year),
            'used_days'           => $this->leaveService->usedDays($employee->id, $leaveType->id, $year),
            'remaining_days'      => $remaining,
            'will_exceed_balance' => $leaveRequest->total_days > $remaining,
        ];

        return $leaveRequest;
    });


        return response()->json([
            'message' => 'Leave requests retrieved successfully',
            'data' => [
                'leave_requests' => $data,
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->with(['user', 'leaveType', 'approvedBy'])
            ->firstOrFail();
            
        $year      = $leaveRequest->start_date->year;
        $employee  = $leaveRequest->user;
        $leaveType = $leaveRequest->leaveType;

        $entitledDays  = $this->leaveService->entitledDays($employee, $leaveType, $year);
        $usedDays      = $this->leaveService->usedDays($employee->id, $leaveType->id, $year);
        $remainingDays = $this->leaveService->remainingDays($employee, $leaveType, $year);

        

        return response()->json([
            'message' => 'Leave request retrieved successfully',
            'data' => [
                'leave_request' => new LeaveRequestResource($leaveRequest),
            'employee_balance' => [
            'leave_type'     => $leaveType->name,
            'year'           => $year,
            'entitled_days'  => $entitledDays,
            'used_days'      => $usedDays,
            'remaining_days' => $remainingDays,
            'requested_days' => $leaveRequest->total_days,
            // Will approving this leave exceed their balance?
            'will_exceed_balance' => $leaveRequest->total_days > $remainingDays,
        ],
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
