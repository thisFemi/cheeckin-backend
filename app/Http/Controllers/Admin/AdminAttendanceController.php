<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OverrideAttendanceRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
          $user = $request->user();
        if( !$user->canDo('view_all_attendance')){
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $query = AttendanceRecord::where('organization_id', $request->user()->organization_id)
            ->with(['user', 'policy'])
            ->latest('date');

        if ($request->filled('user_id'))  $query->where('user_id', $request->user_id);
        if ($request->filled('month'))    $query->whereMonth('date', $request->month);
        if ($request->filled('year'))     $query->whereYear('date', $request->year);
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('department')) {
            $query->whereHas('user', fn($q) => $q->where('department', $request->department));
        }

        return response()->json([
            'message' => 'Attendance records retrieved successfully',
            'data' => [
                'records' => AttendanceRecordResource::collection($query->paginate(50)),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $record = AttendanceRecord::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->with(['user', 'policy'])
            ->firstOrFail();

        return response()->json(
            
        [
            'message' => 'Attendance record retrieved successfully',
            'data' => [
            'record' => new AttendanceRecordResource($record)]]);
    }

    public function override(OverrideAttendanceRequest $request, int $id): JsonResponse
    {
            $user = $request->user();
        if( !$user->canDo('edit_attendance')){
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $record = AttendanceRecord::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        $previousStatus = $record->status;

        $record->update([
            'status'        => $request->status,
            'admin_note'    => $request->admin_note,
            'is_overridden' => true,
        ]);

        return response()->json([
            'message'         => 'Attendance status overridden.',
            'previous_status' => $previousStatus,
            'new_status'      => $request->status,
            'record'          => new AttendanceRecordResource($record->fresh(['user', 'policy'])),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;
        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year',  now()->year);

        $records = AttendanceRecord::where('organization_id', $orgId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalEmployees = User::where('organization_id', $orgId)
            ->where('user_type', 'employee')
            ->where('employment_status', 'active')
            ->count();

        return response()->json([
                'message' => 'Attendance summary retrieved successfully',
                'data' => [
            'month'           => $month,
            'year'            => $year,
            'total_employees' => $totalEmployees,
            'summary' => [
                'present'  => $records->get('present', 0),
                'absent'   => $records->get('absent', 0),
                'late'     => $records->get('late', 0),
                'half_day' => $records->get('half_day', 0),
                'on_leave' => $records->get('on_leave', 0),
            ],
        ]]);
    }

}
