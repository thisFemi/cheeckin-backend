<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    
  protected $fillable = [
        'user_id', 'organization_id', 'attendance_policy_id', 'date',
        'check_in_at', 'check_out_at',
        'check_in_latitude', 'check_in_longitude',
        'check_out_latitude', 'check_out_longitude',
        'check_in_face_image', 'check_out_face_image',
        'check_in_face_verified', 'check_out_face_verified',
        'status', 'working_minutes', 'admin_note', 'is_overridden',
    ];

    protected $casts=[
          'date'                    => 'date',
        'check_in_at'             => 'datetime',
        'check_out_at'            => 'datetime',
        'check_in_face_verified'  => 'boolean',
        'check_out_face_verified' => 'boolean',
        'is_overridden'           => 'boolean',
    ];

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function policy (): BelongsTo{
        return $this->belongsTo(AttendancePolicy::class,'attendance_policy_id' );
    }
    
}
