<?php

use App\Models\AttendancePolicy;
use Carbon\Carbon;

// app/Services/AttendanceService.php
class AttendanceService
{
    public function computeStatus(
        AttendancePolicy $policy,
        Carbon $checkInAt
    ): string {
        $policyStart = Carbon::parse($checkInAt->toDateString() . ' ' . $policy->work_start_time);
        $lateBy      = $checkInAt->diffInMinutes($policyStart, false); // positive = late

        if ($lateBy > $policy->late_threshold_minutes) {
            return 'late';
        }

        return 'present';
    }

    public function computeWorkingMinutes(Carbon $checkIn, Carbon $checkOut): int
    {
        return (int) $checkOut->diffInMinutes($checkIn);
    }

    public function isWithinRadius(
        float $officeLat, float $officeLng,
        float $userLat,   float $userLng,
        int   $radiusMeters
    ): bool {
        $earthRadius = 6371000; // metres
        $dLat = deg2rad($userLat - $officeLat);
        $dLng = deg2rad($userLng - $officeLng);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($officeLat)) * cos(deg2rad($userLat)) * sin($dLng / 2) ** 2;

        $distance = $earthRadius * 2 * asin(sqrt($a));

        return $distance <= $radiusMeters;
    }
}