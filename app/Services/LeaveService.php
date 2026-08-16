<?php
namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserLeaveEntitlement;
use Carbon\Carbon;

 
class LeaveService
{


    /**
     * How many days has this employee used for a given leave type this year?
     */
    public function usedDays(int $userId, int $leaveTypeId, int $year): int
    {
        return LeaveRequest::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->whereYear('start_date', $year)
            ->whereIn('status', ['approved', 'pending'])
            ->sum('total_days');
    }

    /**
     * Entitled days for the user (custom entitlement overrides leave type default).
     */
    public function entitledDays(User $user, LeaveType $leaveType, int $year): int
    {
        $entitlement = UserLeaveEntitlement::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        return $entitlement?->entitled_days ?? $leaveType->days_per_year;
    }

    /**
     * Remaining days.
     */
    public function remainingDays(User $user, LeaveType $leaveType, int $year): int
    {
        $entitled = $this->entitledDays($user, $leaveType, $year);
        $used     = $this->usedDays($user->id, $leaveType->id, $year);

        return max(0, $entitled - $used);
    }

    /**
     * Count working days between two dates (Mon–Fri, can be extended with holidays).
     */
    public function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $days = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            if ($current->isWeekday()) $days++;
            $current->addDay();
        }
        return $days;
    }
}