<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
  protected $fillable = [
        'user_id', 'organization_id', 'leave_type_id', 'approved_by',
        'start_date', 'end_date', 'total_days', 'reason',
        'document', 'status', 'rejection_reason', 'actioned_at',
    ];

    protected $casts=[
        'start_date'=>'date',
        'end_date'=>'date',
        'actioned_at'=>'datetime',
    ];

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);   
    }
    public function leaveType(): BelongsTo{
        return $this->belongsTo(LeaveType::class);
    }
    public function approvedBy(): BelongsTo{
        return $this->belongsTo(User::class, 'approved_by');
    }
}
