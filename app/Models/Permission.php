<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
 
 protected $fillable = [
        'organization_id',
        'name',
        'slug',
    ];

protected $hidden = ['pivot'];
public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission')->withPivot('allowed');
    }

//Allowed Permissions

//'check_in',
//'check_out',
//'apply_leave',
//'approve_leave',
//'reject_leave',
//'view_all_attendance',
//'edit_attendance',
//'manage_users',
//'assign_roles',
//'view_reports',
//'manage_policies',
}
