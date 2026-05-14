<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
protected $fillable = [
       'organization_id', 'role_id', 'first_name', 'last_name', 'email',
        'password', 'phone', 'avatar', 'face_template', 'requires_face_setup', 'user_type',
        'employment_status', 'employee_id', 'department', 'position', 'joined_date',
    ];

protected $hidden = ['password', 'remember_token', 'face_template'];

 protected $casts = [
        'email_verified_at' => 'datetime',
        'joined_date'       => 'date',
        'password'          => 'hashed',
    ];


    public function organization(): BelongsTo{
        return $this->belongsTo(Organization::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
public function userAttendancePolicy(): HasOne
{
    return $this->hasOne(UserAttendancePolicy::class);
}
public function attendancePolicy(): HasOneThrough
{
    return $this->hasOneThrough(
        AttendancePolicy::class,        // final model we want
        UserAttendancePolicy::class,    // pivot model in between
        'user_id',                      // FK on user_attendance_policies → users
        'id',                           // FK on attendance_policies → id
        'id',                           // local key on users
        'attendance_policy_id'          // local key on user_attendance_policies
    );
}

public function attendanceRecords(): HasMany
{
    return $this->hasMany(AttendanceRecord::class);
}

public function leaveRequests(): HasMany{
    return $this->hasMany(LeaveRequest::class);
} 

public function leaveEntitlements(): HasMany{
    return $this->hasMany(UserLeaveEntitlement::class);

}

public function isOwner():bool{return $this->user_type=='owner';}
public function isAdmin():bool{return $this->user_type=='admin';}
public function isEmployee():bool{return $this->user_type=='employee';}



public function hasPermission(string $slug): bool
{
    if (!$this->role) return false;

    return $this->role->permissions()
        ->where('slug', $slug)
        ->exists();
}


public function canDo(string $permission): bool
{
    // Owner always allowed
    if ($this->isOwner()) {
        return true;
    }

    // Admin always allowed
    if ($this->isAdmin()) {
        return true;
    }

    // Otherwise check permission
    return $this->hasPermission($permission);
}
}
