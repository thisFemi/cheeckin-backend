<?php

namespace App\Enums;

enum Permission: string
{
    //Staff management
    case MANAGE_USERS = 'manage_users';

    //Attendance
    case CHECK_IN_OUT = 'check_in_out';
    case VIEW_ALL_ATTENDANCE = 'view_all_attendance';
    case EDIT_ATTENDANCE = 'edit_attendance';

    //Leave
    case APPLY_LEAVE = 'apply_leave';
    case MANAGE_LEAVE = 'manage_leave';
    case APPROVE_OR_REJECT_LEAVE = 'approve_or_reject_leave';

    //Polices
    case MANAGE_POLICIES = 'manage_policies';

    //Roles and permissions
    case ASSIGN_ROLES = 'assign_roles';

    //Reports
    case VIEW_REPORTS = 'view_reports';



public static function all(): array
{
    return array_column(self::cases(), 'value');
}

public static function list(): array{
    return [
    self::MANAGE_USERS->value =>"Manage Users", 
    self::CHECK_IN_OUT->value => "Check In/Out",
    self::VIEW_ALL_ATTENDANCE->value => "View All Attendance", 
    self::EDIT_ATTENDANCE->value => "Edit Attendance", 
    self::APPLY_LEAVE->value => "Apply for Leave", 
    self::MANAGE_LEAVE->value => "Manage Leave Requests", 
    self::APPROVE_OR_REJECT_LEAVE->value => "Approve or Reject Leave Requests", 
    self::MANAGE_POLICIES->value => "Manage Attendance Policies", 
    self::ASSIGN_ROLES->value => "Assign Roles and Permissions", 
    self::VIEW_REPORTS->value => "View Reports"];
}
}