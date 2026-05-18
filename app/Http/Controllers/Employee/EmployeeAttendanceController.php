<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\CheckInRequest;
use App\Http\Requests\Employee\CheckOutRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;

use App\Services\AttendanceService;

use App\Services\FaceVerificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;


class EmployeeAttendanceController extends Controller
{
     public function __construct(
        private AttendanceService      $attendanceService,
        private FaceVerificationService $faceService,
    ) {}

    public function checkIn(CheckInRequest $request): JsonResponse{
        $user = $request->user();
        if( !$user->canDo('check_in_out')){
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $today  = now()->toDateString();
        $policy = $user->attendancePolicy;
          if (AttendanceRecord::where('user_id', $user->id)->where('date', $today)->whereNotNull('check_in_at')->exists()) {
            return response()->json(['message' => 'Already checked in today.'], 422);
        }

         if ($policy && !$policy->allow_remote) {
            $withinRadius = $this->attendanceService->isWithinRadius(
                $policy->office_latitude, $policy->office_longitude,
                $request->latitude, $request->longitude,
                $policy->location_radius_meters
            );
            if (!$withinRadius) {
                return response()->json(['message' => 'You are not within the office location.'], 422);
            }
        }

        $faceResult = ['verified' => true, 'path' => null];
        if ($policy?->require_face_capture && $request->face_image) {
            $faceResult = $this->faceService->verify($user, $request->face_image, 'check_in');
            if (!$faceResult['verified']) {
                return response()->json(['message' => 'Face verification failed.'], 422);
            }
        }
         $checkInAt = now();
        $status    = $policy
            ? $this->attendanceService->computeStatus($policy, $checkInAt)
            : 'present';

         $record = AttendanceRecord::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            [
                'organization_id'        => $user->organization_id,
                'attendance_policy_id'   => $policy?->id,
                'check_in_at'            => $checkInAt,
                'check_in_latitude'      => $request->latitude,
                'check_in_longitude'     => $request->longitude,
                'check_in_face_image'    => $faceResult['path'],
                'check_in_face_verified' => $faceResult['verified'],
                'status'                 => $status,
            ]
        );   
         return response()->json([
            'message'   => 'Checked in successfully.',
                'data'      => [
                    'attendance_record' => new AttendanceRecordResource($record),
                ],
          
        ], 201);
    }
    public function checkOut(CheckOutRequest $request):JsonResponse{
        $user = $request->user();
        if( !$user->canDo('check_in_out')){
            return response()->json(['message' => 'Forbidden'], 403);
        }

         $record = AttendanceRecord::where('user_id', $user->id)
            ->where('date', now()->toDateString())
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->firstOrFail();
        $faceResult = ['verified' => true, 'path' => null];
        if ($record->policy?->require_face_capture && $request->face_image) {
            $faceResult = $this->faceService->verify($user, $request->face_image, 'check_out');
        }

        $checkOutAt     = now();
        $workingMinutes = $this->attendanceService->computeWorkingMinutes(
            Carbon::parse($record->check_in_at), $checkOutAt
        );

         $record->update([
            'check_out_at'            => $checkOutAt,
            'check_out_latitude'      => $request->latitude,
            'check_out_longitude'     => $request->longitude,
            'check_out_face_image'    => $faceResult['path'],
            'check_out_face_verified' => $faceResult['verified'],
            'working_minutes'         => $workingMinutes,
        ]);
        
         return response()->json([
            'message'   => 'Checked out successfully.',
                'data'      => [
                    'working_minutes' => $workingMinutes,
                    'record' => new AttendanceRecordResource($record),
                ],
        ]);
    }
}