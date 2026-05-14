<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLeaveEntitlement extends Model
{
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'entitled_days',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
