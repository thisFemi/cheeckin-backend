<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    
protected $fillable=[
    'name', 'email', 'phone', 'address', 'logo','org_code', 'is_active'
];
public function users(): HasMany{
    return $this->hasMany(User::class);
}
public function admins(): HasMany
{
    return $this->hasMany(User::class)->where('user_type', 'admin');
}

public function employees(): HasMany
{
    return $this->hasMany(User::class)->where('user_type', 'employee');
}

public function owner(): HasOne
{
    return $this->hasOne(User::class)->where('user_type', 'owner');
}

public function attendancePolicies(): HasMany{
    return $this->hasMany(AttendancePolicy::class); 
}

public function leaveTypes(): HasMany
{
    return $this->hasMany(LeaveType::class);
}

}
