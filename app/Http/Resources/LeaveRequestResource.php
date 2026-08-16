<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'status'           => $this->status,
            'start_date'       => $this->start_date->toDateString(),
            'end_date'         => $this->end_date->toDateString(),
            'total_days'       => $this->total_days,
            'reason'           => $this->reason,
            'document'         => $this->document,
            'rejection_reason' => $this->rejection_reason,
            'actioned_at'      => $this->actioned_at?->toIso8601String(),
            'created_at'       => $this->created_at->toIso8601String(),
            'user'             => new UserResource($this->whenLoaded('user')),
            'leave_type'       => new LeaveTypeResource($this->whenLoaded('leaveType')),
            'approved_by'      => new UserResource($this->whenLoaded('approvedBy')),
            // Conditionally include balance if it was attached
            'employee_balance' => $this->when(
                isset($this->employee_balance),
                $this->employee_balance
            ),
        ];
    }
}