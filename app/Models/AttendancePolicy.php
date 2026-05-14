<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePolicy extends Model
{
     protected $fillable = [
        'organization_id', 'name', 'work_start_time', 'work_end_time',
        'late_threshold_minutes', 'early_checkout_threshold_minutes',
        'allow_remote', 'office_latitude', 'office_longitude',
        'location_radius_meters', 'require_face_capture', 'is_active',
    ];

    protected $casts=[
        'allow_remote'=>'boolean',
        'require_face_capture'=>'boolean',
        'is_active'=>'boolean',
        'office_latitude'=>'decimal:7',
        'office_longitude'=>'decimal:7',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);   
    }

    public function userAttendancePolicies(): HasMany
    {
        return $this->hasMany(UserAttendancePolicy::class);
    }

      public function users(): HasMany
    {
        return $this->hasMany(UserAttendancePolicy::class)->with('user');
    }
}
