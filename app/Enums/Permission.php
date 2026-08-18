<?php

namespace App\Enums;

enum Permission: string
{
    // Staff management
    case MANAGE_STAFF = 'manage_staff';

    // Attendance
    case CHECK_IN_OUT = 'check_in_out';
    case VIEW_ALL_ATTENDANCE = 'view_all_attendance';
    case EDIT_ATTENDANCE = 'edit_attendance';

    // Leave
    case APPLY_LEAVE = 'apply_leave';
    case MANAGE_LEAVE = 'manage_leave';
    case APPROVE_OR_REJECT_LEAVE = 'approve_or_reject_leave';

    // Policies
    case MANAGE_POLICIES = 'manage_policies';

    // Roles and permissions
    case MANAGE_ROLES = 'manage_roles';

    // Reports
    case VIEW_REPORTS = 'view_reports';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function list(): array
    {
        return [
            self::MANAGE_STAFF->value            => 'Manage Staff',

            self::CHECK_IN_OUT->value             => 'Check In/Out',
            self::VIEW_ALL_ATTENDANCE->value      => 'View All Attendance',
            self::EDIT_ATTENDANCE->value          => 'Edit Attendance',

            self::APPLY_LEAVE->value              => 'Apply for Leave',
            self::MANAGE_LEAVE->value             => 'Manage Leave Requests',
            self::APPROVE_OR_REJECT_LEAVE->value  => 'Approve or Reject Leave Requests',

            self::MANAGE_POLICIES->value          => 'Manage Attendance Policies',

            self::MANAGE_ROLES->value             => 'Manage Roles',

            self::VIEW_REPORTS->value              => 'View Reports',
        ];
    }
}